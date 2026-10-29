<?php

namespace App\Support;

use App\Models\Article;
use App\Models\LibraryCopy;
use App\Models\LibraryImage;
use App\Models\LibraryUsage;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Jenssegers\ImageHash\Implementations\PerceptualHash;
use RuntimeException;
use ZipArchive;

/**
 * Bibliothèque d'images (sources).
 *
 * Ajout avec détection des doublons : même fichier (sha256), ou même image dans un autre format,
 * une autre taille ou une autre compression (empreintes perceptuelles dHash + pHash, mêmes proportions).
 * Si l'image ajoutée est de meilleure qualité (plus de pixels), elle remplace la source existante
 * (titre et description conservés) ; les pages gardent leur copie, signalée comme ancienne version.
 *
 * Les pages n'utilisent que des copies retraitées (LibraryCopy). Une nouvelle version (replaceSource) ne
 * touche pas les pages : on remplace ensuite l'image page par page (replaceInPage), avec une nouvelle copie
 * à une nouvelle adresse (pas d'ancienne image en cache dans les navigateurs).
 */
class Library
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];

    public const THUMB_WIDTH = 480;

    // Seuils de ressemblance (distance de Hamming sur 64 bits) : volontairement stricts pour ne jamais
    // confondre deux photos différentes d'une même scène.
    private const MAX_DHASH_DISTANCE = 5;

    private const MAX_PHASH_DISTANCE = 8;

    private const MAX_RATIO_GAP = 0.02;

    /**
     * Ajoute un fichier déposé : une image, ou un document Word (.docx) dont on ajoute toutes les images.
     *
     * @return array<int, LibraryResult>
     */
    public static function addUpload(UploadedFile $file, ?string $title = null, ?string $alt = null): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'docx') {
            return self::addFromDocx($file->getRealPath(), $file->getClientOriginalName());
        }

        return [self::add($file->getRealPath(), $file->getClientOriginalName(), $title, $alt)];
    }

    /** Variante qui n'interrompt jamais l'envoi en cours (formulaires de l'admin, API). */
    public static function addQuietly(string $path, string $originalName, ?string $title = null, ?string $alt = null): ?LibraryResult
    {
        try {
            return self::add($path, $originalName, $title, $alt);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function add(string $path, string $originalName, ?string $title = null, ?string $alt = null): LibraryResult
    {
        [$bytes, $image] = self::read($path, $originalName);

        $sha256 = hash('sha256', $bytes);
        if ($existing = LibraryImage::firstWhere('sha256', $sha256)) {
            return self::keep($existing, $alt);
        }

        $attributes = self::attributes($bytes, $image, $originalName);
        $similar = self::findSimilar($image->width() / $image->height(), $attributes['dhash'], $attributes['phash']);

        if ($similar) {
            if (! self::isBetter($image->width() * $image->height(), strlen($bytes), $similar)) {
                return self::keep($similar, $alt);
            }

            self::swapFiles($similar, $bytes, $image, $originalName, $attributes + ['alt' => $similar->alt ?: $alt]);

            return new LibraryResult($similar, LibraryResult::REPLACED);
        }

        $created = LibraryImage::create([
            ...$attributes,
            ...self::storeFiles($bytes, $originalName, $image),
            'title' => $title ?: self::titleFromName($originalName),
            'alt' => $alt,
            'user_id' => auth()->id(),
        ]);

        return new LibraryResult($created, LibraryResult::ADDED);
    }

    /**
     * Nouvelle version d'une image (choisie par l'utilisateur, sans contrôle de ressemblance) : remplace la source.
     * Les pages qui l'utilisent passent en « ancienne version » ; on les met à jour avec replaceInPage().
     *
     * @return int nombre de pages qui utilisent encore l'ancienne version
     */
    public static function replaceSource(LibraryImage $libraryImage, string $path, string $originalName): int
    {
        [$bytes, $image] = self::read($path, $originalName);

        $sha256 = hash('sha256', $bytes);
        $other = LibraryImage::where('sha256', $sha256)->whereKeyNot($libraryImage->id)->first();
        if ($other) {
            throw new InvalidArgumentException("Ce fichier est déjà dans la bibliothèque (« {$other->title} »).");
        }

        self::swapFiles($libraryImage, $bytes, $image, $originalName, self::attributes($bytes, $image, $originalName));

        return $libraryImage->usages()->get()->unique(fn (LibraryUsage $u) => $u->page_type.$u->article_id)->count();
    }

    /**
     * Retouche de l'original en pleine résolution (outil de recadrage de la fiche) : miroir, rotation par quarts
     * de tour puis recadrage, dans cet ordre (celui de Cropper.js). Comme une nouvelle version : les pages
     * passent en « ancienne version ». L'original précédent est gardé pour pouvoir annuler (undoEdit).
     *
     * @param  array{x?: float, y?: float, width?: float, height?: float, rotate?: float, scaleX?: float, scaleY?: float}  $edit
     */
    public static function edit(LibraryImage $libraryImage, array $edit): void
    {
        $image = (new ImageManager(new Driver))->read($libraryImage->sourceAbsolutePath());

        if (($edit['scaleX'] ?? 1) < 0) {
            $image->flop();
        }
        if (($edit['scaleY'] ?? 1) < 0) {
            $image->flip();
        }
        // Quarts de tour seulement ; Cropper tourne dans le sens horaire, Intervention dans l'autre sens.
        $rotate = ((int) round(($edit['rotate'] ?? 0) / 90) * 90) % 360;
        if ($rotate !== 0) {
            $image->rotate(-$rotate);
        }

        if (isset($edit['width'], $edit['height'])) {
            $x = max(0, (int) round($edit['x'] ?? 0));
            $y = max(0, (int) round($edit['y'] ?? 0));
            $width = min((int) round($edit['width']), $image->width() - $x);
            $height = min((int) round($edit['height']), $image->height() - $y);
            if ($width < 10 || $height < 10) {
                throw new InvalidArgumentException('La zone à recadrer est trop petite.');
            }
            if ($width < $image->width() || $height < $image->height()) {
                $image->crop($width, $height, $x, $y);
            }
        }

        // Même format que l'original, qualité élevée (c'est une source).
        $bytes = (string) match ($libraryImage->mime) {
            'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp' => $image->toPng(),
            'image/webp' => $image->toWebp(quality: 92),
            default => $image->toJpeg(quality: 92),
        };
        $extension = match ($libraryImage->mime) {
            'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $name = pathinfo((string) $libraryImage->original_name, PATHINFO_FILENAME) ?: 'image';
        $decoded = (new ImageManager(new Driver))->read($bytes);

        // L'original actuel devient « l'original précédent » (le plus ancien est effacé).
        $previous = $libraryImage->previous_source_path;
        $current = $libraryImage->source_path;
        $oldThumb = $libraryImage->thumb_path;
        $libraryImage->update([
            ...self::attributes($bytes, $decoded, $libraryImage->original_name ?? $name.'.'.$extension),
            ...self::storeFiles($bytes, $name.'.'.$extension, $decoded),
            'previous_source_path' => $current,
        ]);
        Storage::disk(LibraryImage::SOURCE_DISK)->delete(array_filter([$previous]));
        Storage::disk('public')->delete($oldThumb);
    }

    /** Annule la dernière retouche : l'original précédent redevient l'original. */
    public static function undoEdit(LibraryImage $libraryImage): void
    {
        $previous = $libraryImage->previous_source_path;
        if (! $previous || ! Storage::disk(LibraryImage::SOURCE_DISK)->exists($previous)) {
            throw new InvalidArgumentException('Aucune retouche à annuler.');
        }

        $bytes = Storage::disk(LibraryImage::SOURCE_DISK)->get($previous);
        $image = (new ImageManager(new Driver))->read($bytes);
        $oldSource = $libraryImage->source_path;
        $oldThumb = $libraryImage->thumb_path;
        $thumb = 'bibliotheque/'.(Str::slug($libraryImage->title) ?: 'image').'-'.Str::random(8).'.webp';
        Storage::disk('public')->put($thumb, (string) (clone $image)->scaleDown(width: self::THUMB_WIDTH)->toWebp(quality: 75));

        $libraryImage->update([
            ...self::attributes($bytes, $image, (string) $libraryImage->original_name),
            'source_path' => $previous,
            'previous_source_path' => null,
            'thumb_path' => $thumb,
        ]);
        Storage::disk(LibraryImage::SOURCE_DISK)->delete($oldSource);
        Storage::disk('public')->delete($oldThumb);
    }

    /** Copie retraitée (redimensionnée, WebP, sans métadonnées) d'une source, pour une page. */
    public static function copyForArticle(LibraryImage $image, string $directory = 'articles', int $maxWidth = Images::MAX_WIDTH): string
    {
        $path = Images::store($image->sourceAbsolutePath(), $directory, $maxWidth, Str::slug($image->title) ?: 'image');
        self::registerCopy($image, $path, $maxWidth);

        return $path;
    }

    /** Déclare un fichier du disque public comme copie d'une image de la bibliothèque. */
    public static function registerCopy(LibraryImage $image, string $path, int $maxWidth = Images::MAX_WIDTH, ?string $sourceSha256 = null): LibraryCopy
    {
        return LibraryCopy::updateOrCreate(
            ['path' => $path],
            ['library_image_id' => $image->id, 'max_width' => $maxWidth, 'source_sha256' => $sourceSha256 ?? $image->sha256],
        );
    }

    /**
     * Remplace l'image dans une page : nouvelle copie faite à partir de la version actuelle de la source,
     * à une nouvelle adresse, puis mise à jour des références de cette page seulement (couverture, galerie,
     * texte, ou valeurs de l'accueil). L'ancienne copie est effacée si plus aucune page ne l'utilise.
     *
     * @param  int|null  $width  largeur maximale ; null = largeur actuelle de la copie dans cette page
     */
    public static function replaceInPage(LibraryUsage $usage, ?int $width = null): LibraryCopy
    {
        $old = $usage->copy;
        $new = self::copyAtWidth($old->image, dirname($old->path), $width ?? self::currentWidth($old));
        $newPath = $new->path;

        DB::transaction(function () use ($usage, $old, $newPath) {
            if ($usage->page_type === 'home') {
                Setting::put('home', self::replaceInArray(Setting::get('home', []), $old->path, $newPath));
            } elseif ($article = $usage->article) {
                $article->og_image = $article->og_image === $old->path ? $newPath : $article->og_image;
                $article->gallery = array_map(
                    fn ($item) => ($item['path'] ?? null) === $old->path ? ['path' => $newPath] + $item : $item,
                    $article->gallery ?? []);
                $article->body = str_replace($old->path, $newPath, (string) $article->body);
                $article->save();
            }
        });

        // L'index des utilisations a été recalculé à l'enregistrement de la page.
        if (! $old->usages()->exists()) {
            Storage::disk('public')->delete($old->path);
            $old->delete();
        }

        return $new;
    }

    /** Nouvelle copie d'une source, à une nouvelle adresse, d'au plus $width pixels de large. */
    public static function copyAtWidth(LibraryImage $image, string $directory, int $width): LibraryCopy
    {
        $path = trim($directory, '/').'/'.(Str::slug($image->title) ?: 'image').'-'.Str::random(6).'.webp';
        Images::write($image->sourceAbsolutePath(), $path, $width);

        return self::registerCopy($image, $path, $width);
    }

    /**
     * Images du corps Markdown avec une largeur d'affichage, ![description](…/storage/chemin){width=400} :
     * si le fichier n'a pas la bonne taille (2 × la largeur affichée, pour les écrans haute densité, sans
     * dépasser l'original ni 1920 px), une nouvelle copie est faite depuis la bibliothèque et l'adresse
     * est mise à jour. Les images qui ne viennent pas de la bibliothèque sont laissées telles quelles.
     */
    public static function resizeBodyImages(string $body): string
    {
        $pattern = '#!\[[^\]]*\]\(\s*<?[^)\s>]*/storage/([^)\s>?]+)[^)]*\)\{([^}]*)\}#';

        return preg_replace_callback($pattern, function (array $match) {
            if (! preg_match('/\bwidth\s*=\s*"?(\d+)/', $match[2], $width)) {
                return $match[0];
            }

            $path = urldecode($match[1]);
            $copy = LibraryCopy::with('image')->firstWhere('path', $path);
            if (! $copy) {
                return $match[0];
            }

            $target = min(2 * (int) $width[1], $copy->image->width, Images::MAX_WIDTH);
            if (abs(self::currentWidth($copy) - $target) <= max(16, $target * 0.1)) {
                return $match[0];
            }

            // L'ancienne copie, si plus rien ne l'utilise, sera effacée par « bibliotheque:nettoyer ».
            return str_replace($match[1], self::copyAtWidth($copy->image, dirname($path), $target)->path, $match[0]);
        }, $body);
    }

    /** Largeur en pixels du fichier d'une copie (sa taille d'affichage dans la page). */
    public static function currentWidth(LibraryCopy $copy): int
    {
        $size = @getimagesize(Storage::disk('public')->path($copy->path));

        return $size ? (int) $size[0] : $copy->max_width;
    }

    private static function replaceInArray(array $values, string $search, string $replace): array
    {
        return array_map(fn ($value) => match (true) {
            is_array($value) => self::replaceInArray($value, $search, $replace),
            $value === $search => $replace,
            default => $value,
        }, $values);
    }

    /**
     * Supprime une image de la bibliothèque. Refusé si une page en ligne l'utilise (article publié, accueil) ;
     * les brouillons qui l'utilisent la perdent simplement.
     *
     * @return array<int, string> titres des brouillons modifiés
     */
    public static function delete(LibraryImage $image): array
    {
        $usages = $image->usages()->with('article')->get();
        if ($usages->contains(fn ($usage) => $usage->isPublic())) {
            throw new RuntimeException('Cette image est utilisée par une page en ligne : retirez-la d\'abord de cette page.');
        }

        $paths = $image->copies()->pluck('path')->all();
        $drafts = $usages->pluck('article')->filter()->unique('id');

        DB::transaction(function () use ($image, $paths, $drafts) {
            foreach ($drafts as $article) {
                self::removeFromArticle($article, $paths);
            }
            $image->delete();
        });

        Storage::disk('public')->delete($paths);

        return $drafts->pluck('title')->all();
    }

    /** @return array<int, LibraryResult> */
    public static function addFromDocx(string $path, string $originalName): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException("« {$originalName} » n'est pas un document Word lisible.");
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'word/media/') && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true)) {
                $entries[] = $name;
            }
        }
        // image1, image2… image10 : ordre d'insertion dans le document.
        natsort($entries);

        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $results = [];
        foreach (array_values($entries) as $n => $entry) {
            $tmp = tempnam(sys_get_temp_dir(), 'docx');
            file_put_contents($tmp, $zip->getFromName($entry));
            try {
                $extension = pathinfo($entry, PATHINFO_EXTENSION);
                $results[] = self::add($tmp, "{$base} - image ".($n + 1).".{$extension}", "{$base} – image ".($n + 1));
            } catch (InvalidArgumentException) {
                // Format non lisible (EMF, WMF…) : ignoré.
            } finally {
                @unlink($tmp);
            }
        }
        $zip->close();

        return $results;
    }

    /** @return array{0: string, 1: string} */
    public static function fingerprints(string $bytes): array
    {
        return [
            (new ImageHash(new DifferenceHash))->hash($bytes)->toBits(),
            (new ImageHash(new PerceptualHash))->hash($bytes)->toBits(),
        ];
    }

    public static function distance(string $bits1, string $bits2): int
    {
        // XOR caractère par caractère de deux chaînes de 0/1 : chaque différence donne "\x01".
        return substr_count($bits1 ^ $bits2, "\x01");
    }

    /** @return array{0: string, 1: Image} */
    private static function read(string $path, string $originalName): array
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false || $bytes === '') {
            throw new InvalidArgumentException('Fichier illisible.');
        }

        try {
            return [$bytes, (new ImageManager(new Driver))->read($bytes)];
        } catch (\Throwable) {
            throw new InvalidArgumentException("« {$originalName} » n'est pas une image lisible (formats acceptés : JPG, PNG, WebP, GIF).");
        }
    }

    private static function attributes(string $bytes, Image $image, string $originalName): array
    {
        [$dhash, $phash] = self::fingerprints($bytes);

        return [
            'original_name' => $originalName,
            'mime' => (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'image/jpeg',
            'width' => $image->width(),
            'height' => $image->height(),
            'size' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
            'dhash' => $dhash,
            'phash' => $phash,
        ];
    }

    private static function swapFiles(LibraryImage $libraryImage, string $bytes, Image $image, string $originalName, array $attributes): void
    {
        $old = [$libraryImage->source_path, $libraryImage->thumb_path];
        $libraryImage->update([...$attributes, ...self::storeFiles($bytes, $originalName, $image)]);
        Storage::disk(LibraryImage::SOURCE_DISK)->delete($old[0]);
        Storage::disk('public')->delete($old[1]);
    }

    /** Retire d'un brouillon toutes les références aux fichiers $paths (couverture, galerie, corps). */
    private static function removeFromArticle(Article $article, array $paths): void
    {
        if (in_array($article->og_image, $paths, true)) {
            $article->og_image = null;
            $article->og_image_alt = null;
        }

        $article->gallery = array_values(array_filter($article->gallery ?? [], fn ($item) => ! in_array($item['path'] ?? null, $paths, true)));

        $body = (string) $article->body;
        foreach ($paths as $path) {
            $quoted = preg_quote($path, '#');
            $body = preg_replace('#!\[[^\]]*\]\([^)\s]*'.$quoted.'[^)]*\)[ \t]*\n?#', '', $body);
            $body = preg_replace('#<img\b[^>]*'.$quoted.'[^>]*>#i', '', $body);
        }
        $article->body = $body;

        $article->save();
    }

    private static function findSimilar(float $ratio, string $dhash, string $phash): ?LibraryImage
    {
        return LibraryImage::query()
            ->get(['id', 'width', 'height', 'size', 'dhash', 'phash'])
            ->filter(fn (LibraryImage $i) => abs($i->width / $i->height - $ratio) / $ratio <= self::MAX_RATIO_GAP
                && self::distance($i->dhash, $dhash) <= self::MAX_DHASH_DISTANCE
                && self::distance($i->phash, $phash) <= self::MAX_PHASH_DISTANCE)
            ->sortBy(fn (LibraryImage $i) => self::distance($i->dhash, $dhash) + self::distance($i->phash, $phash))
            ->map(fn (LibraryImage $i) => LibraryImage::find($i->id))
            ->first();
    }

    /** Meilleure qualité = nettement plus de pixels, ou autant de pixels dans un fichier plus riche (moins compressé). */
    private static function isBetter(int $pixels, int $size, LibraryImage $current): bool
    {
        if ($pixels > $current->pixels() * 1.05) {
            return true;
        }

        return $pixels >= $current->pixels() * 0.95 && $size > $current->size * 1.2;
    }

    private static function keep(LibraryImage $image, ?string $alt): LibraryResult
    {
        if ($alt && ! $image->alt) {
            $image->update(['alt' => $alt]);
        }

        return new LibraryResult($image, LibraryResult::DUPLICATE);
    }

    /** @return array{source_path: string, thumb_path: string} */
    private static function storeFiles(string $bytes, string $originalName, Image $image): array
    {
        $slug = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'image';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: 'jpg';
        $source = 'bibliotheque/'.now()->format('Y/m').'/'.$slug.'-'.Str::random(8).'.'.$extension;
        Storage::disk(LibraryImage::SOURCE_DISK)->put($source, $bytes);

        $thumb = 'bibliotheque/'.$slug.'-'.Str::random(8).'.webp';
        Storage::disk('public')->put($thumb, (string) (clone $image)->scaleDown(width: self::THUMB_WIDTH)->toWebp(quality: 75));

        return ['source_path' => $source, 'thumb_path' => $thumb];
    }

    private static function titleFromName(string $name): string
    {
        return Str::ucfirst(trim(preg_replace('/[_-]+/', ' ', pathinfo($name, PATHINFO_FILENAME)))) ?: 'Image';
    }
}
