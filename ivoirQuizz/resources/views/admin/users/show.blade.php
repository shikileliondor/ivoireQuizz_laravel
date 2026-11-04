@extends('admin.layouts.app')

@section('title', $user->name)

@section('content')
    @php($fmt = fn ($n) => number_format((int) $n, 0, ',', ' '))
    @php($isSelf = auth()->user()->is($user))

    <x-admin.page-header :title="$user->name" :subtitle="($user->username ? '@'.$user->username.' · ' : '').$user->email" :back="route('admin.users.index')">
        <x-slot:actions>
            <x-admin.enum group="user_status" :value="$user->status" />
            @if ($user->is_admin)
                <x-admin.badge color="orange">Administrateur</x-admin.badge>
            @endif
            <a href="{{ route('admin.notifications.index', ['user' => $user->email]) }}" class="btn btn-secondary"><x-admin.icon name="bell" class="size-4" /> Notifier</a>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary"><x-admin.icon name="pencil" class="size-4" /> Modifier</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat label="Niveau joueur" :value="$user->current_level" :hint="$fmt($user->xp_total).' XP'" icon="bolt" />
        <x-admin.stat label="Score total" :value="$fmt($user->total_score)" :hint="$fmt($user->games_won).' victoire(s) / '.$fmt($user->games_played).' partie(s)'" icon="trophy" tone="green" />
        <x-admin.stat label="Pièces" :value="$fmt($user->coins)" :hint="$fmt($user->gems).' gemme(s)'" icon="gift" tone="purple" />
        <x-admin.stat label="Collection" :value="$user->user_collectibles_count" :hint="$user->user_chests_count.' coffre(s) · '.$user->question_reports_count.' signalement(s)'" icon="sparkles" tone="blue" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card overflow-hidden">
                <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Dernières parties</h2>
                    <a href="{{ route('admin.sessions.index', ['user' => $user->id]) }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">Toutes ({{ $user->game_sessions_count }})</a>
                </header>
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead><tr><th>Date</th><th>Contenu</th><th>Statut</th><th class="text-right">Score</th><th class="text-right">Précision</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($sessions as $session)
                                <tr>
                                    <td class="whitespace-nowrap"><a href="{{ route('admin.sessions.show', $session) }}" class="hover:text-brand-600">{{ $session->created_at->format('d/m/Y H:i') }}</a></td>
                                    <td>{{ $session->level ? $session->level->city?->name.' · '.$session->level->title : ($session->category?->name ?? \App\Support\AdminLabels::label('session_mode', $session->mode)) }}</td>
                                    <td><x-admin.enum group="session_status" :value="$session->status" /></td>
                                    <td class="text-right tabular-nums">{{ $session->score }}</td>
                                    <td class="text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $session->accuracy, 1, ',', ''), '0'), ',') }} %</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-admin.empty message="Aucune partie jouée." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card overflow-hidden">
                <header class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Dernières transactions de récompenses</h2>
                </header>
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead><tr><th>Date</th><th>Type</th><th class="text-right">Montant</th><th>Origine</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                    <td><x-admin.enum group="reward_type" :value="$transaction->type" /></td>
                                    <td @class(['text-right font-medium tabular-nums', 'text-ci-600' => $transaction->amount > 0, 'text-red-600' => $transaction->amount < 0])>{{ $transaction->amount > 0 ? '+' : '' }}{{ $fmt($transaction->amount) }}</td>
                                    <td class="text-slate-500">
                                        @if ($transaction->source_type === 'admin')
                                            <x-admin.badge color="orange">Admin</x-admin.badge>
                                        @endif
                                        {{ $transaction->description ?? $transaction->source_type }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-admin.empty message="Aucune transaction." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">Profil</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    @foreach ([
                        'Code ami' => $user->friend_code,
                        'Téléphone' => $user->phone,
                        'Ville' => $user->city,
                        'Connexion Google' => $user->google_id ? 'Oui' : 'Non',
                        'Inscription' => $user->created_at?->format('d/m/Y H:i'),
                        'Dernière connexion' => $user->last_login_at?->format('d/m/Y H:i'),
                        'Dernière activité' => $user->last_activity_date?->format('d/m/Y'),
                        'Sessions mobiles' => $user->tokens_count,
                    ] as $term => $detail)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ $term }}</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $detail ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($user->bio)
                    <p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-600">{{ $user->bio }}</p>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">Ajuster le solde</h2>
                <p class="mt-1 text-xs text-slate-500">L’opération est enregistrée dans les transactions avec votre identifiant.</p>
                <form method="POST" action="{{ route('admin.users.balance', $user) }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <x-admin.select name="currency" label="Monnaie" :options="['coin' => 'Pièces', 'gem' => 'Gemmes']" value="coin" />
                        <x-admin.input name="amount" label="Montant" type="number" placeholder="ex. 100 ou -50" required />
                    </div>
                    <x-admin.input name="reason" label="Motif" placeholder="Compensation bug du 12/10" required />
                    <x-admin.checkbox name="notify" label="Prévenir le joueur" hint="Notification « Cadeau reçu », seulement pour un ajout." />
                    <button type="submit" class="btn btn-primary w-full">Appliquer</button>
                </form>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">Actions sur le compte</h2>
                @if ($isSelf)
                    <p class="mt-2 text-sm text-slate-500">C’est votre compte : statut, droits et suppression ne sont pas modifiables ici.</p>
                @else
                    <form method="POST" action="{{ route('admin.users.status', $user) }}" class="mt-4 flex items-end gap-2">
                        @csrf
                        @method('PATCH')
                        <x-admin.select name="status" label="Statut" :options="\App\Support\AdminLabels::options('user_status')" :value="$user->status->value" class="flex-1" />
                        <button type="submit" class="btn btn-secondary">Appliquer</button>
                    </form>
                    <p class="mt-1 text-xs text-slate-500">Suspendre ou désactiver révoque aussi les sessions mobiles.</p>

                    <div class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                        <form method="POST" action="{{ route('admin.users.tokens', $user) }}" data-confirm="Déconnecter ce joueur de tous ses appareils ?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary w-full">Déconnecter tous les appareils</button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.admin', $user) }}" data-confirm="{{ $user->is_admin ? 'Retirer les droits administrateur ?' : 'Donner l’accès complet au back-office à ce joueur ?' }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary w-full">
                                <x-admin.icon name="shield" class="size-4" />
                                {{ $user->is_admin ? 'Retirer les droits admin' : 'Nommer administrateur' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Supprimer ce compte ? Ses données personnelles seront anonymisées, c’est irréversible.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-full"><x-admin.icon name="trash" class="size-4" /> Supprimer et anonymiser</button>
                        </form>
                    </div>
                @endif
            </section>
        </aside>
    </div>
@endsection
