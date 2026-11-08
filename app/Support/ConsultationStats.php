<?php

namespace App\Support;

use App\Models\ConsultationParticipant;
use App\Models\ConsultationProject;
use App\Models\ConsultationVote;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/** Résultats de la consultation : statistiques de l'admin, export Excel et QR codes à imprimer. */
class ConsultationStats
{
    public const CRITERIA = ['aesthetic', 'spiritual', 'convivial'];

    /** @return array{participants: int, voters: int, complete: int, votes: int, comments: int} */
    public static function totals(): array
    {
        $activeIds = ConsultationProject::ordered()->pluck('id');
        $overall = ConsultationVote::query()->whereNotNull('overall')->whereIn('project_id', $activeIds);

        return [
            'participants' => ConsultationParticipant::count(),
            'voters' => (clone $overall)->distinct()->count('participant_id'),
            'complete' => $activeIds->isEmpty() ? 0 : (clone $overall)->select('participant_id')->groupBy('participant_id')
                ->havingRaw('count(*) >= ?', [$activeIds->count()])->get()->count(),
            'votes' => (clone $overall)->count(),
            'comments' => ConsultationVote::query()->whereIn('project_id', $activeIds)->whereNotNull('comment')->count(),
        ];
    }

    /**
     * Une ligne par projet : nombre d'avis, moyenne et répartition de l'avis général, moyenne de chaque critère.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function projects(): Collection
    {
        $votes = ConsultationVote::query()->get()->groupBy('project_id');

        return ConsultationProject::ordered()->get()->map(function (ConsultationProject $project) use ($votes) {
            $projectVotes = $votes->get($project->id, collect());
            $overall = $projectVotes->pluck('overall')->filter(fn ($v) => $v !== null);
            $distribution = collect([-2, -1, 0, 1, 2])->mapWithKeys(fn ($v) => [$v => $overall->filter(fn ($o) => $o === $v)->count()]);

            return [
                'project' => $project,
                'count' => $overall->count(),
                'average' => $overall->isEmpty() ? null : round($overall->avg(), 2),
                'favorable' => $overall->isEmpty() ? null : (int) round(100 * $overall->filter(fn ($v) => $v > 0)->count() / $overall->count()),
                'distribution' => $distribution->all(),
                'criteria' => collect(self::CRITERIA)->mapWithKeys(function ($c) use ($projectVotes) {
                    $values = $projectVotes->pluck($c)->filter(fn ($v) => $v !== null);

                    return [$c => ['average' => $values->isEmpty() ? null : round($values->avg(), 2), 'count' => $values->count()]];
                })->all(),
                'comments' => $projectVotes->whereNotNull('comment')->count(),
            ];
        });
    }

    /** @return Collection<int, ConsultationVote> derniers commentaires */
    public static function comments(int $limit = 50, ?int $projectId = null): Collection
    {
        return ConsultationVote::query()
            ->with(['participant', 'project'])
            ->whereNotNull('comment')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /** Classeur Excel : synthèse par projet, toutes les réponses, participants. Renvoie le chemin du fichier temporaire. */
    public static function export(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'consultation').'.xlsx';
        $bold = (new Style)->setFontBold();
        $wrap = (new Style)->setShouldWrapText();
        $labels = fn (string $scale) => Consultation::SCALES[$scale]['short'];

        $writer = new Writer;
        $writer->openToFile($path);

        // Synthèse
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Synthèse');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
        $sheet->setColumnWidth(32, 1);
        $sheet->setColumnWidth(24, 2);
        $writer->addRow(Row::fromValues([
            'Projet', 'Architecte', 'Nombre d\'avis', 'Avis général (moyenne, -2 à +2)', '% favorables (+1 ou +2)',
            'Nb -2', 'Nb -1', 'Nb 0', 'Nb +1', 'Nb +2',
            ...collect(self::CRITERIA)->map(fn ($c) => $labels($c).' (moyenne)'),
            'Commentaires',
        ], $bold));
        foreach (self::projects() as $row) {
            $writer->addRow(Row::fromValues([
                $row['project']->name, $row['project']->architect, $row['count'], $row['average'], $row['favorable'],
                ...array_values($row['distribution']),
                ...collect(self::CRITERIA)->map(fn ($c) => $row['criteria'][$c]['average']),
                $row['comments'],
            ]));
        }

        // Réponses
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Réponses');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
        $sheet->setColumnWidth(30, 2);
        $sheet->setColumnWidth(28, 3);
        $sheet->setColumnWidth(70, 9);
        $writer->addRow(Row::fromValues([
            'Date', 'Email', 'Projet', 'N°', $labels('overall'), ...collect(self::CRITERIA)->map($labels), 'Avis général (libellé)', 'Commentaire',
        ], $bold));
        ConsultationVote::query()->with(['participant', 'project'])
            ->join('consultation_projects', 'consultation_projects.id', '=', 'consultation_votes.project_id')
            ->orderBy('consultation_votes.participant_id')->orderBy('consultation_projects.position')
            ->select('consultation_votes.*')
            ->each(function (ConsultationVote $vote) use ($writer, $wrap) {
                $writer->addRow(Row::fromValues([
                    $vote->updated_at->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                    $vote->participant->email,
                    $vote->project->name,
                    $vote->project->position,
                    $vote->overall,
                    ...collect(self::CRITERIA)->map(fn ($c) => $vote->{$c}),
                    $vote->overall === null ? null : Consultation::SCALES['overall']['steps'][$vote->overall],
                    $vote->comment,
                ], $wrap));
            });

        // Participants
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Participants');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
        $sheet->setColumnWidth(34, 1);
        $writer->addRow(Row::fromValues(['Email', 'Première visite', 'Projets notés', 'Inscrit dans Brevo'], $bold));
        ConsultationParticipant::query()
            ->withCount(['votes' => fn ($q) => $q->whereNotNull('overall')])
            ->orderBy('created_at')
            ->each(fn (ConsultationParticipant $p) => $writer->addRow(Row::fromValues([
                $p->email, $p->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i'), $p->votes_count, $p->brevo_synced_at ? 'oui' : 'non',
            ])));

        $writer->close();

        return $path;
    }

    /** QR code (SVG pour l'écran, PNG haute définition pour l'impression). */
    public static function qrCode(string $url, string $format = 'svg'): string
    {
        $options = new QROptions([
            'outputType' => $format === 'png' ? QROutputInterface::GDIMAGE_PNG : QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'scale' => 24,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($url);
    }
}
