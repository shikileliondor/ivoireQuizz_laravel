<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('pseudo', 15)->nullable()->after('name');
        });

        // Comptes existants : premier mot du nom, nettoyé, sinon « Joueur » + id.
        // Les comptes supprimés (anonymisés) gardent un pseudo vide.
        // Logique recopiée de App\Support\Pseudo pour que la migration reste figée.
        DB::table('users')
            ->whereNull('pseudo')
            ->whereNull('deleted_at')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(500, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'pseudo' => $this->pseudoFromName((string) $user->name) ?? 'Joueur'.$user->id,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('pseudo');
        });
    }

    private function pseudoFromName(string $name): ?string
    {
        if (class_exists(Normalizer::class)) {
            $name = Normalizer::normalize($name, Normalizer::FORM_C) ?: $name;
        }

        $words = preg_split('/\s+/u', trim($name), 2, PREG_SPLIT_NO_EMPTY);
        $pseudo = (string) preg_replace('/[^\p{L}\p{N}_-]+/u', '', $words[0] ?? '');
        $pseudo = mb_substr($pseudo, 0, 15);

        return mb_strlen($pseudo) >= 3 ? $pseudo : null;
    }
};
