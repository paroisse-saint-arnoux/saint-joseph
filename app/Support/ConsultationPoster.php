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
 * (numéro, nom, architecte, QR code vers la page du projet), à poser sous la maquette.
 */
class ConsultationPoster
{
    public static function pdf(): string
    {
        $projects = ConsultationProject::ordered()->get()->map(fn (ConsultationProject $p) => [
            'project' => $p,
            'url' => $url = route('consultation.project', $p),
            'qr' => self::dataUri(ConsultationStats::qrCode($url, 'png'), 'image/png'),
            'portrait' => self::portrait($p->architect_photo),
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

    /** Portrait recadré en carré, en JPEG (dompdf ne lit pas toujours le WebP). */
    private static function portrait(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $image = (new ImageManager(new Driver))->read(Storage::disk('public')->path($path))->cover(400, 400, 'top');

        return self::dataUri((string) $image->toJpeg(quality: 88), 'image/jpeg');
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
