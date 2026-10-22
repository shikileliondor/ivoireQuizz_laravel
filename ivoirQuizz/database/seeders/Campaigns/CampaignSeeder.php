<?php

namespace Database\Seeders\Campaigns;

use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $importer = app(CampaignImporter::class);
        $campaigns = [
            [
                'key' => 'korhogo',
                'region_slug' => 'poro',
                'city_slug' => 'korhogo',
                'file' => database_path('seeders/Campaigns/data/korhogo_questions.json'),
            ],
            [
                'key' => 'aboisso',
                'region_slug' => 'sud-comoe',
                'city_slug' => 'aboisso',
                'file' => database_path('seeders/Campaigns/data/aboisso_questions.json'),
            ],
        ];

        foreach ($campaigns as $campaign) {
            $importer->import($campaign);
        }
    }
}
