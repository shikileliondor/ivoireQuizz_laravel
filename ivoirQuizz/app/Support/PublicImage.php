<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicImage
{
    /**
     * URL absolue en https d'un fichier du disque public, ou null si le fichier est absent.
     */
    public static function url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return null;
        }

        $parts = parse_url($disk->url($path));
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
}
