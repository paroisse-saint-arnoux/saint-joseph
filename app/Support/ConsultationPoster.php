<?php

namespace App\Support;

use App\Models\ConsultationProject;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Affichettes de la consultation : un PDF avec une page A5 paysage par projet
 * (numéro de la maquette, photo de la maquette, QR code vers la page du projet), à poser sous la maquette.
 * Projets anonymes : ni nom de projet ou d'agence, ni architecte (décision du 28/11/2026).
 */
class ConsultationPoster
{
    public static function pdf(): string
    {
        $projects = ConsultationProject::ordered()->get()->map(fn (ConsultationProject $p) => [
            'project' => $p,
            'url' => $url = route('consultation.project', $p),
            'qr' => self::dataUri(ConsultationStats::qrCode($url, 'png'), 'image/png'),
            'photo' => self::photo($p->model_photo),
        ]);

        $html = view('consultation.pdf.posters', [
            'projects' => $projects,
            'fonts' => resource_path('fonts/pdf'),
            'logo' => self::logo(),
        ])->render();

        File::ensureDirectoryExists($fontCache = storage_path('framework/cache/dompdf'));

        $options = new Options([
            'chroot' => [resource_path('fonts/pdf')],
            'fontDir' => $fontCache,
            'fontCache' => $fontCache,
            'tempDir' => $fontCache,
            'isRemoteEnabled' => false,
            'defaultFont' => 'Hanken Grotesk',
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }

    // Zone de la photo de la maquette sur l'affichette, en millimètres : de 12 mm à 118 mm du bord gauche,
    // le cadre du QR code commençant à 126 mm (210 − 12 de marge − 62 − 2 × 5 de marge intérieure).
    public const PHOTO_WIDTH = 106;

    public const PHOTO_HEIGHT = 80;

    /**
     * Photo de la maquette, en JPEG (dompdf ne lit pas toujours le WebP), avec ses dimensions en mm
     * pour tenir entière dans la zone prévue (dompdf gère mal object-fit et max-height).
     *
     * @return array{src: string, width: float, height: float}|null
     */
    private static function photo(?string $path): ?array
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $image = (new ImageManager(new Driver))->read(Storage::disk('public')->path($path))->scaleDown(1600, 1600);
        $scale = min(self::PHOTO_WIDTH / $image->width(), self::PHOTO_HEIGHT / $image->height());

        return [
            'src' => self::dataUri((string) $image->toJpeg(quality: 85), 'image/jpeg'),
            'width' => round($image->width() * $scale, 1),
            'height' => round($image->height() * $scale, 1),
        ];
    }

    /** Le logo du site est clair (pour fonds sombres) : on le recolore en bleu marine pour le fond parchemin. */
    private static function logo(): string
    {
        $image = imagecreatefrompng(public_path('images/logo-saint-joseph.png'));
        $image = imagescale($image, 360);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        for ($x = 0, $w = imagesx($image), $h = imagesy($image); $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;
                $alpha = $alpha > 120 ? 127 : $alpha; // le redimensionnement laisse un voile presque transparent, visible dans le PDF
                imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, 0x00, 0x20, 0x4E, $alpha));
            }
        }

        ob_start();
        imagepng($image);

        return self::dataUri(ob_get_clean(), 'image/png');
    }

    private static function dataUri(string $data, string $mime): string
    {
        return 'data:'.$mime.';base64,'.base64_encode($data);
    }
}
