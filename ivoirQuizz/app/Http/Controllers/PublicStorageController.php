<?php

namespace App\Http\Controllers;

use App\Support\PublicImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sert les fichiers du disque « public » (images des questions, des villes…) sous /storage.
 *
 * L'hébergement est déployé par FTP : le lien symbolique public/storage que crée
 * `php artisan storage:link` n'y existe pas, donc Apache ne trouve pas ces fichiers
 * et passe la requête à Laravel. Si le lien est créé un jour, Apache sert le fichier
 * lui-même et cette route n'est plus appelée.
 */
class PublicStorageController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        abort_unless(PublicImage::exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            // Les envois de l'admin ont un nom aléatoire : un jour de cache évite de repasser par PHP.
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
