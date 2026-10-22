<?php

namespace Tests\Unit;

use Database\Seeders\Campaigns\CampaignImporter;
use InvalidArgumentException;
use Tests\TestCase;

class CampaignImporterTest extends TestCase
{
    private function bank(string $city): array
    {
        return json_decode(file_get_contents(database_path("seeders/Campaigns/data/{$city}_questions.json")), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_both_campaign_banks_have_fifty_questions_with_expected_points_and_one_correct_answer(): void
    {
        foreach (['korhogo', 'aboisso', 'yopougon'] as $city) {
            $bank = $this->bank($city);
            $this->assertCount(50, $bank);
            (new CampaignImporter)->validateBank($bank, $city, [
                'histoire', 'geographie', 'gastronomie', 'sport', 'culture', 'monuments',
                'personnalites', 'agriculture', 'economie', 'energie', 'artisanat', 'education',
                'musique', 'patrimoine', 'infrastructures',
            ]);

            foreach ($bank as $index => $question) {
                $this->assertSame(intdiv($index, 10) * 5 + 10, $question['points']);
                $this->assertSame(1, count(array_filter($question['options'], fn (array $option): bool => $option['correct'])));
            }
        }
    }

    public function test_duplicate_question_source_keys_are_rejected(): void
    {
        $bank = $this->bank('korhogo');
        $bank[1]['source_key'] = $bank[0]['source_key'];

        $this->expectException(InvalidArgumentException::class);
        (new CampaignImporter)->validateBank($bank, 'korhogo', ['geographie', 'histoire', 'artisanat', 'culture', 'patrimoine', 'musique', 'agriculture', 'education', 'economie', 'gastronomie', 'sport', 'monuments', 'personnalites', 'energie', 'infrastructures']);
    }

    public function test_duplicate_numbers_questions_option_ids_and_multiple_correct_answers_are_rejected(): void
    {
        $importer = new CampaignImporter;
        $categories = ['geographie', 'histoire', 'artisanat', 'culture', 'patrimoine', 'musique', 'agriculture', 'education', 'economie', 'gastronomie', 'sport', 'monuments', 'personnalites', 'energie', 'infrastructures'];

        foreach ([
            function (array &$bank): void {
                $bank[1]['number'] = $bank[0]['number'];
            },
            function (array &$bank): void {
                $bank[1]['question'] = $bank[0]['question'];
            },
            function (array &$bank): void {
                $bank[0]['options'][1]['source_key'] = $bank[0]['options'][0]['source_key'];
            },
            function (array &$bank): void {
                foreach ($bank[0]['options'] as &$option) {
                    $option['correct'] = false;
                }
                unset($option);
                $bank[0]['options'][0]['correct'] = true;
                $bank[0]['options'][1]['correct'] = true;
            },
        ] as $mutate) {
            $bank = $this->bank('korhogo');
            $mutate($bank);
            try {
                $importer->validateBank($bank, 'korhogo', $categories);
                $this->fail('The invalid campaign data should be rejected.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_invalid_categories_points_and_answer_count_are_rejected(): void
    {
        $importer = new CampaignImporter;
        $categories = ['geographie', 'histoire', 'artisanat', 'culture', 'patrimoine', 'musique', 'agriculture', 'education', 'economie', 'gastronomie', 'sport', 'monuments', 'personnalites', 'energie', 'infrastructures'];

        $bank = $this->bank('korhogo');
        $bank[0]['category'] = 'missing-category';
        try {
            $importer->validateBank($bank, 'korhogo', $categories);
            $this->fail('An unknown category should be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $bank = $this->bank('korhogo');
        $bank[0]['points'] = 999;
        try {
            $importer->validateBank($bank, 'korhogo', $categories);
            $this->fail('Unexpected points should be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $bank = $this->bank('korhogo');
        array_pop($bank[0]['options']);
        $this->expectException(InvalidArgumentException::class);
        $importer->validateBank($bank, 'korhogo', $categories);
    }
}
