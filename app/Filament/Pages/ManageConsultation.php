<?php

namespace App\Filament\Pages;

use App\Models\ConsultationProject;
use App\Models\Setting;
use App\Support\Consultation;
use App\Support\ConsultationStats;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/** Suivi de la consultation sur les projets d'architecture : statistiques, commentaires, QR codes, export Excel. */
class ManageConsultation extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Résultats';

    protected static string|\UnitEnum|null $navigationGroup = 'Consultation';

    protected static ?string $title = 'Consultation sur les projets';

    protected static ?string $slug = 'consultation';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.manage-consultation';

    /** Filtre des commentaires par projet (vide = tous). */
    public ?int $commentProject = null;

    public function getViewData(): array
    {
        return [
            'open' => Consultation::isOpen(),
            'totals' => ConsultationStats::totals(),
            'rows' => ConsultationStats::projects(),
            'comments' => ConsultationStats::comments(100, $this->commentProject),
            'projects' => ConsultationProject::ordered()->get(),
            'startUrl' => route('consultation.start'),
            'qr' => fn (string $url) => ConsultationStats::qrCode($url),
        ];
    }

    protected function getHeaderActions(): array
    {
        $open = Consultation::isOpen();

        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => response()->download(ConsultationStats::export(), 'consultation-projets-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend()),
            Action::make('openApp')
                ->label('Ouvrir l\'application')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('gray')
                ->url(route('consultation.start'), shouldOpenInNewTab: true),
            Action::make('toggle')
                ->label($open ? 'Clore la consultation' : 'Rouvrir la consultation')
                ->icon($open ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                ->color($open ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalDescription($open
                    ? 'Les personnes qui scannent le QR code verront « La consultation est terminée » et ne pourront plus voter. Les réponses sont conservées.'
                    : 'Les paroissiens pourront de nouveau donner et modifier leur avis.')
                ->action(function () use ($open) {
                    Setting::put('consultation', [...Setting::get('consultation', []), 'open' => ! $open]);
                    Notification::make()->title($open ? 'Consultation close' : 'Consultation rouverte')->success()->send();
                }),
        ];
    }

    /** QR code en PNG haute définition, à imprimer (général ou d'un projet). */
    #[Exposed]
    public function downloadQr(?int $projectId = null)
    {
        $project = $projectId ? ConsultationProject::findOrFail($projectId) : null;
        $url = $project ? route('consultation.project', $project) : route('consultation.start');
        $name = 'qr-consultation'.($project ? '-'.$project->position.'-'.Str::slug($project->name) : '').'.png';

        return response()->streamDownload(fn () => print (ConsultationStats::qrCode($url, 'png')), $name, ['Content-Type' => 'image/png']);
    }
}
