<?php

namespace Tests\Feature\Notifications;

use App\Models\Answer;
use App\Models\City;
use App\Models\Friendship;
use App\Models\Level;
use App\Models\PushAutomation;
use App\Models\PushLog;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesFcm;
use Tests\TestCase;

class ActionNotificationTest extends TestCase
{
    use FakesFcm, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->setUpFakeFcm();
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeFcm();
        parent::tearDown();
    }

    public function test_friend_request_uses_the_admin_text_and_can_be_disabled(): void
    {
        $awa = $this->playerWithDevice('awa', ['name' => 'Awa Traoré']);
        $koffi = $this->playerWithDevice('koffi');

        $this->actingAs($awa, 'sanctum')->postJson('/api/friends/add', ['friend_code' => $koffi->friend_code])->assertCreated();

        $this->assertSame(
            ['token' => 'koffi', 'notification' => ['title' => 'Nouvelle demande d’ami', 'body' => 'Awa Traoré veut devenir ton ami sur IvoireQuizz.'], 'data' => ['type' => 'friend_request']],
            $this->messageFor('koffi'),
        );
        $this->assertDatabaseHas('push_logs', ['user_id' => $koffi->id, 'type' => 'friend_request', 'is_reminder' => false]);

        PushAutomation::for('friend_request')->update(['enabled' => false]);
        $yao = $this->playerWithDevice('yao');
        $this->actingAs($awa, 'sanctum')->postJson('/api/friends/add', ['friend_code' => $yao->friend_code])->assertCreated();

        $this->assertNull($this->messageFor('yao'));
    }

    public function test_challenge_result_reaches_the_player_who_finished_first(): void
    {
        $this->createQuestions(10);
        $awa = $this->playerWithDevice('awa', ['name' => 'Awa Traoré']);
        $koffi = $this->playerWithDevice('koffi', ['name' => 'Koffi Kouassi']);
        Friendship::query()->create(['requester_id' => $awa->id, 'receiver_id' => $koffi->id, 'status' => 'accepted']);

        $challenge = $this->actingAs($awa, 'sanctum')->postJson('/api/challenges', ['opponent_id' => $koffi->id])->assertCreated()->json('data');
        $this->assertSame('Nouveau défi ! ⚔️', $this->messageFor('koffi')['notification']['title']);
        $this->assertSame(['type' => 'challenge', 'code' => $challenge['code']], $this->messageFor('koffi')['data']);

        // Awa répond juste partout, Koffi ne répond à rien : Awa gagne 100 à 0.
        $answers = fn (bool $correct) => collect($challenge['questions'])->map(fn (array $question) => [
            'question_id' => $question['id'],
            'answer_id' => $correct ? Answer::query()->where('question_id', $question['id'])->where('is_correct', true)->value('id') : null,
            'response_time' => 5,
        ])->all();
        $this->actingAs($awa, 'sanctum')->postJson("/api/challenges/{$challenge['code']}/submit", ['answers' => $answers(true)])->assertOk();
        $this->assertNull($this->messageFor('awa'), 'pas de résultat tant que Koffi n’a pas joué');

        $this->actingAs($koffi, 'sanctum')->postJson("/api/challenges/{$challenge['code']}/submit", ['answers' => $answers(false)])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertSame(
            ['title' => 'Victoire ! 🏆', 'body' => 'Koffi Kouassi a joué ton défi : tu gagnes 100 à 0.'],
            $this->messageFor('awa')['notification'],
        );
        $this->assertSame(1, $this->messagesFor('koffi'));
    }

    public function test_admin_gift_notifies_only_when_asked_and_for_credits(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $player = $this->playerWithDevice('player');

        $this->post(route('admin.users.balance', $player), ['currency' => 'coin', 'amount' => 1500, 'reason' => 'Concours', 'notify' => '1'])->assertSessionHasNoErrors();
        $this->post(route('admin.users.balance', $player), ['currency' => 'gem', 'amount' => 5, 'reason' => 'Sans prévenir'])->assertSessionHasNoErrors();
        $this->post(route('admin.users.balance', $player), ['currency' => 'coin', 'amount' => -10, 'reason' => 'Retrait', 'notify' => '1'])->assertSessionHasNoErrors();

        Http::assertSentCount(1);
        $this->assertSame('Tu as reçu 1 500 pièces de l’équipe IvoireQuizz.', $this->messageFor('player')['notification']['body']);
    }

    public function test_reporters_are_thanked_when_their_report_is_fixed(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $question = $this->createQuestions(1)[0];
        $reporters = collect(['first', 'second', 'rejected'])->mapWithKeys(fn (string $token) => [$token => $this->playerWithDevice($token, ['name' => ucfirst($token).' Joueur'])]);
        $reports = $reporters->map(fn (User $user) => QuestionReport::query()->create(['user_id' => $user->id, 'question_id' => $question->id, 'reason' => 'typo']));
        $reports['rejected']->update(['status' => 'rejected']);

        $this->patch(route('admin.reports.update', $reports['first']), ['status' => 'fixed', 'apply_to_question' => '1'])->assertRedirect();

        $this->assertSame('First, la question que tu as signalée a été corrigée.', $this->messageFor('first')['notification']['body']);
        $this->assertNotNull($this->messageFor('second'));
        $this->assertNull($this->messageFor('rejected'));

        // Repasser en « Corrigé » ne remercie pas une deuxième fois.
        $this->patch(route('admin.reports.update', $reports['first']), ['status' => 'fixed'])->assertRedirect();
        $this->assertSame(1, $this->messagesFor('first'));
        $this->assertSame(2, PushLog::query()->where('type', 'report_fixed')->count());
    }

    public function test_suspended_players_receive_nothing(): void
    {
        $awa = $this->playerWithDevice('awa');
        $suspended = $this->playerWithDevice('suspended', ['status' => 'suspended']);

        $this->actingAs($awa, 'sanctum')->postJson('/api/friends/add', ['friend_code' => $suspended->friend_code]);

        Http::assertNothingSent();
    }

    /** @return list<Question> */
    private function createQuestions(int $count): array
    {
        $region = Region::query()->create(['name' => 'Lagunes', 'slug' => 'lagunes']);
        $city = City::query()->create(['region_id' => $region->id, 'name' => 'Abidjan', 'slug' => 'abidjan']);
        $level = Level::query()->create(['city_id' => $city->id, 'title' => 'Niveau 1', 'slug' => 'niveau-1']);

        return collect(range(1, $count))->map(function (int $index) use ($level): Question {
            $question = Question::query()->create(['level_id' => $level->id, 'question_text' => "Question $index ?"]);
            Answer::query()->create(['question_id' => $question->id, 'answer_text' => 'Bonne', 'is_correct' => true, 'order' => 1]);
            Answer::query()->create(['question_id' => $question->id, 'answer_text' => 'Mauvaise', 'is_correct' => false, 'order' => 2]);

            return $question;
        })->all();
    }
}
