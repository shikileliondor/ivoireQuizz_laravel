<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;

/**
 * Règles et préparation des images de la catégorie Logos.
 * L'app affiche les logos sur un fond coloré : seuls PNG et WebP gardent la transparence.
 */
class LogoImage
{
    public const MIMES = ['png', 'webp'];

    /** En dessous, le logo serait flou une fois affiché dans l'app. */
    public const MIN_SIDE = 128;

    /** Au-delà, le fichier est réduit à l'envoi : l'app n'affiche jamais un logo plus grand. */
    public const MAX_SIDE = 512;

    /**
     * Règles de validation d'un fichier logo.
     *
     * @return list<mixed>
     */
    public static function rules(): array
    {
        return ['image', 'mimes:'.implode(',', self::MIMES), 'max:2048', self::minSizeRule()];
    }

    /**
     * Un logo étiré se lit mal : on exige MIN_SIDE px sur le plus grand côté
     * (un logo horizontal peut être peu haut).
     */
    private static function minSizeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $size = $value instanceof UploadedFile ? @getimagesize((string) $value->getRealPath()) : false;

            // Un fichier illisible est déjà refusé par la règle « image ».
            if ($size !== false && max($size[0], $size[1]) < self::MIN_SIDE) {
                $fail('Le fichier « '.$value->getClientOriginalName().' » est trop petit : '.self::MIN_SIDE.' px minimum sur son plus grand côté.');
            }
        };
    }

    /**
     * Réduit le fichier envoyé à MAX_SIDE px sur son plus grand côté, transparence comprise.
     * Sans GD, ou si l'image est illisible, le fichier est gardé tel quel.
     */
    public static function shrink(UploadedFile $file): void
    {
        $path = (string) $file->getRealPath();
        $size = $path !== '' ? @getimagesize($path) : false;
        if ($size === false || max($size[0], $size[1]) <= self::MAX_SIDE || ! function_exists('imagecreatefromstring')) {
            return;
        }

        $isWebp = $size[2] === IMAGETYPE_WEBP;
        if ($isWebp && ! function_exists('imagewebp')) {
            return;
        }

        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            return;
        }
        if (! imageistruecolor($source)) {
            imagepalettetotruecolor($source);
        }

        $ratio = self::MAX_SIDE / max($size[0], $size[1]);
        $width = max(1, (int) round($size[0] * $ratio));
        $height = max(1, (int) round($size[1] * $ratio));

        $target = imagecreatetruecolor($width, $height);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);

        $isWebp ? imagewebp($target, $path, 90) : imagepng($target, $path, 9);
    }
}
