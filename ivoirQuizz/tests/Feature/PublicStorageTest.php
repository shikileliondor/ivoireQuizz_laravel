<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Support\PublicImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Sans lien public/storage (déploiement FTP), Laravel sert lui-même les fichiers du disque public.
 */
class PublicStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_the_image_url_given_to_the_app_serves_the_file(): void
    {
        Storage::disk('public')->putFileAs('questions', UploadedFile::fake()->image('drapeau.png', 64, 64), 'drapeau.png');
        $question = Question::query()->create(['question_text' => 'Quel drapeau ?', 'type' => 'image', 'image' => 'questions/drapeau.png']);

        $response = $this->get((string) parse_url((string) $question->imageUrl(), PHP_URL_PATH));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('max-age=86400', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertSame(Storage::disk('public')->path('questions/drapeau.png'), $response->baseResponse->getFile()->getPathname());
    }

    public function test_serving_an_image_does_not_start_a_session(): void
    {
        Storage::disk('public')->put('cities/korhogo.jpg', 'jpg');

        $response = $this->get('/storage/cities/korhogo.jpg')->assertOk();

        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_missing_file_returns_404(): void
    {
        $this->get('/storage/questions/introuvable.png')->assertNotFound();
        $this->assertNull(PublicImage::url('questions/introuvable.png'));
    }

    public function test_directories_hidden_files_and_parent_paths_are_refused(): void
    {
        Storage::disk('public')->put('questions/ok.png', 'png');
        Storage::disk('public')->put('.gitignore', '*');

        $this->get('/storage/questions')->assertNotFound();
        $this->get('/storage/.gitignore')->assertNotFound();
        $this->get('/storage/questions/..%2F.gitignore')->assertNotFound();
        $this->get('/storage/questions/..%2F..%2F..%2F.env')->assertNotFound();
        $this->get('/storage/questions/ok.png')->assertOk();
    }
}
