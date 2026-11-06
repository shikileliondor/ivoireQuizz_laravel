<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\PublicImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait StoresImages
{
    /**
     * Enregistre l'image envoyée sur le disque public et supprime l'ancienne.
     * Retourne le chemin à conserver (inchangé si aucun fichier, null si suppression demandée).
     * Si le fichier n'a pas pu être écrit, l'enregistrement est refusé et l'ancienne image gardée.
     *
     * @throws ValidationException
     */
    protected function storeImage(Request $request, string $field, string $directory, ?string $current): ?string
    {
        if ($request->hasFile($field)) {
            $path = PublicImage::store($request->file($field), $directory, $field);
            $this->deleteImage($current);

            return $path;
        }

        if ($request->boolean('remove_'.$field)) {
            $this->deleteImage($current);

            return null;
        }

        return $current;
    }

    protected function deleteImage(?string $path): void
    {
        if ($path !== null && $path !== '' && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
