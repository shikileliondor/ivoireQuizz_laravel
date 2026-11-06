<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\PathTraversalDetected;

class PublicImage
{
    public const NOT_STORED_MESSAGE = 'L’image n’a pas pu être enregistrée sur le serveur. Réessayez ; si le problème continue, vérifiez les droits d’écriture du dossier storage/app/public.';

    /**
     * URL absolue en https d'un fichier du disque public, ou null si le fichier est absent.
     */
    public static function url(?string $path): ?string
    {
        if (! self::exists($path)) {
            return null;
        }

        $path = trim((string) $path);
        $parts = parse_url(Storage::disk('public')->url($path));
        $host = $parts['host'] ?? parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $urlPath = $parts['path'] ?? '/'.ltrim($path, '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return 'https://'.$host.$port.$urlPath.$query.$fragment;
    }

    /**
     * Le fichier est-il présent sur le disque public ?
     * Les dossiers, fichiers cachés et chemins « .. » sont refusés.
     */
    public static function exists(?string $path): bool
    {
        $path = trim((string) $path);
        if ($path === '' || str_contains('/'.$path, '/.')) {
            return false;
        }

        try {
            return Storage::disk('public')->fileExists($path);
        } catch (PathTraversalDetected) {
            return false;
        }
    }

    /**
     * Enregistre le fichier envoyé sur le disque public et vérifie qu'il y est vraiment :
     * sans cette vérification, un échec d'écriture enregistrerait un chemin vers rien.
     *
     * @throws ValidationException
     */
    public static function store(UploadedFile $file, string $directory, string $field): string
    {
        $path = $file->store($directory, 'public');

        if (! is_string($path) || ! self::exists($path)) {
            throw ValidationException::withMessages([$field => self::NOT_STORED_MESSAGE]);
        }

        return $path;
    }
}
