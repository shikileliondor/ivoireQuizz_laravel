<?php

namespace Database\Seeders\Local;

use App\Models\Answer;
use App\Models\Category;
use App\Models\City;
use App\Models\Friendship;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalChallengeDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('LocalChallengeDemoSeeder est réservé aux environnements local et testing.');
        }

        $password = config('seeding.local_demo_password');
        if (! is_string($password) || $password === '') {
            throw new \RuntimeException('Définir LOCAL_DEMO_PASSWORD avant de lancer LocalChallengeDemoSeeder.');
        }

        $region = Region::updateOrCreate(['slug' => 'demo-local'], ['name' => 'D?mo locale', 'is_active' => true]);
        $city = City::updateOrCreate(['region_id' => $region->id, 'slug' => 'duel-local'], ['name' => 'Duel local', 'is_active' => true]);
        $level = Level::updateOrCreate(['city_id' => $city->id, 'slug' => 'questions-demo'], ['title' => 'Questions de d?monstration', 'difficulty' => 'easy', 'is_active' => true]);
        $category = Category::updateOrCreate(['slug' => 'defi-demo-local'], ['name' => 'D?fi de d?monstration', 'is_active' => true]);

        $users = [];
        foreach ([['D?mo Awa', 'awa@demo.local', 'DEMO1A'], ['D?mo Koffi', 'koffi@demo.local', 'DEMO2K']] as [$name, $email, $friendCode]) {
            $users[] = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make($password),
                'friend_code' => $friendCode,
                'avatar_id' => 1,
            ]);
        }

        Friendship::updateOrCreate(
            ['requester_id' => $users[0]->id, 'receiver_id' => $users[1]->id],
            ['status' => 'accepted']
        );

        $questions = [
            ['Quel est le nom de la capitale politique de la C?te d?Ivoire ?', 'Yamoussoukro', 'Bouak?'],
            ['Quelles sont les couleurs du drapeau ivoirien ?', 'Orange, blanc et vert', 'Rouge, jaune et noir'],
            ['Quel oc?an borde la C?te d?Ivoire au sud ?', 'Oc?an Atlantique', 'Oc?an Indien'],
            ['Quelle est la monnaie utilis?e en C?te d?Ivoire ?', 'Franc CFA (XOF)', 'Cedi'],
            ['Quelle ville est le principal port du pays ?', 'Abidjan', 'Korhogo'],
            ['Quel est l?hymne national ivoirien ?', 'L?Abidjanaise', 'La Marseillaise'],
            ['Quel pays partage une fronti?re ? l?est avec la C?te d?Ivoire ?', 'Ghana', 'S?n?gal'],
            ['Quel grand parc national se trouve dans le sud-ouest ivoirien ?', 'Parc national de Ta?', 'Parc national du Niokolo-Koba'],
            ['Quel est le premier mois de la saison des pluies principale dans le sud ivoirien ?', 'Mai', 'Janvier'],
            ['Quelle est la langue officielle de la C?te d?Ivoire ?', 'Fran?ais', 'Portugais'],
        ];

        foreach ($questions as $index => [$text, $correct, $incorrect]) {
            $question = Question::updateOrCreate(
                ['category_id' => $category->id, 'question_text' => $text],
                ['level_id' => $level->id, 'type' => 'text', 'difficulty' => 'easy', 'is_active' => true, 'time_limit' => 20]
            );
            Answer::updateOrCreate(['question_id' => $question->id, 'answer_text' => $correct], ['is_correct' => true, 'order' => 1]);
            Answer::updateOrCreate(['question_id' => $question->id, 'answer_text' => $incorrect], ['is_correct' => false, 'order' => 2]);
        }
    }
}
