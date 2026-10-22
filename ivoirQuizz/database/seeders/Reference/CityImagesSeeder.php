<?php

namespace Database\Seeders\Reference;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CityImagesSeeder extends Seeder
{
    private const IMAGES = ['aboisso', 'korhogo', 'yopougon'];

    public function run(): void
    {
        $disk = Storage::disk('public');

        foreach (self::IMAGES as $slug) {
            $relativePath = 'cities/'.$slug.'.jpg';
            $sourcePath = database_path('seeders/Reference/images/'.$slug.'.jpg');

            if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
                throw new RuntimeException("Image de ville manquante ou illisible : {$sourcePath}");
            }

            if (! $disk->exists($relativePath)) {
                $contents = file_get_contents($sourcePath);
                if ($contents === false || ! $disk->put($relativePath, $contents)) {
                    throw new RuntimeException("Impossible de copier l'image vers le disque public : {$relativePath}");
                }
            }

            $city = City::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (! $city) {
                throw new RuntimeException("La ville active {$slug} doit être créée par CitiesSeeder avant ses images.");
            }

            if ($city->image !== $relativePath) {
                $city->forceFill(['image' => $relativePath])->save();
            }
        }
    }
}
