@extends('admin.layouts.app')

@section('title', 'Notifications push')

@section('content')
    @php
        $fmt = fn ($n) => number_format((int) $n, 0, ',', ' ');
        $audience = old('audience', $prefillUser ? 'user' : 'me');
        $audiences = [
            'me' => ['Mes appareils (test)', $stats['mine'].' appareil(s) — à faire avant un envoi groupé'],
            'all' => ['Tous les joueurs', $fmt($stats['devices']).' appareil(s) de comptes actifs'],
            'active' => ['Joueurs actifs récemment', 'Ont joué dans les N derniers jours'],
            'inactive' => ['Joueurs inactifs', 'N’ont pas joué depuis N jours — pour les faire revenir'],
            'user' => ['Un joueur précis', 'Par email, pseudo ou code ami'],
        ];
        $labels = array_map(fn ($entry) => $entry[0], $audiences);
    @endphp

    <x-admin.page-header title="Notifications push" subtitle="Message envoyé sur les téléphones via Firebase Cloud Messaging." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat label="Appareils joignables" :value="$fmt($stats['devices'])" hint="Comptes actifs uniquement" icon="bolt" />
        <x-admin.stat label="Joueurs joignables" :value="$fmt($stats['players'])" icon="users" tone="green" />
        <x-admin.stat label="Android / iOS" :value="$fmt($stats['android']).' / '.$fmt($stats['ios'])" icon="chart" tone="blue" />
        <x-admin.stat label="Mes appareils" :value="$stats['mine']" :hint="$stats['mine'] ? 'Prêts pour un envoi test' : 'Connectez-vous à l’app avec ce compte pour tester'" icon="shield" tone="purple" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <form method="POST" action="{{ route('admin.notifications.store') }}" class="card space-y-5 p-6 lg:col-span-3"
              data-confirm="Envoyer cette notification maintenant ? Elle ne pourra pas être annulée.">
            @csrf
            <h2 class="text-sm font-semibold text-slate-900">Nouveau message</h2>

            <fieldset>
                <legend class="form-label">Destinataires <span class="text-red-500">*</span></legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($audiences as $value => [$label, $hint])
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2.5 ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-400">
                            <input type="radio" name="audience" value="{{ $value }}" @checked($audience === $value) class="mt-0.5 accent-brand-500">
                            <span>
                                <span class="block text-sm font-medium text-slate-800">{{ $label }}</span>
                                <span class="block text-xs text-slate-500">{{ $hint }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('audience')
                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="grid gap-5 sm:grid-cols-3">
                <x-admin.input name="days" label="Nombre de jours (N)" type="number" min="1" max="365" :value="7" hint="Pour actifs / inactifs." />
                <x-admin.input name="user" label="Joueur" :value="$prefillUser" placeholder="email, pseudo ou code" hint="Pour « un joueur précis »." />
                <x-admin.select name="platform" label="Plateforme" :options="['android' => 'Android', 'ios' => 'iOS']" placeholder="Toutes" />
            </div>

            <x-admin.input name="title" label="Titre" maxlength="100" placeholder="Nouveau défi à Korhogo !" required />
            <x-admin.textarea name="body" label="Message" rows="3" maxlength="500" placeholder="5 nouvelles questions t’attendent. Viens défendre ta place au classement." required hint="Restez court : le téléphone affiche environ 2 lignes." />

            <div class="flex justify-end border-t border-slate-100 pt-5">
                <button type="submit" class="btn btn-primary"><x-admin.icon name="bolt" class="size-4" /> Envoyer maintenant</button>
            </div>
        </form>

        <aside class="space-y-4 lg:col-span-2">
            <section class="card p-5 text-sm text-slate-600">
                <h2 class="text-sm font-semibold text-slate-900">Bon à savoir</h2>
                <ul class="mt-2 list-inside list-disc space-y-1.5">
                    <li>Testez d’abord sur <strong>vos appareils</strong> avant un envoi à tous.</li>
                    <li>Les comptes suspendus ou désactivés ne reçoivent pas les envois groupés.</li>
                    <li>Les appareils dont l’app a été désinstallée sont détectés et retirés automatiquement.</li>
                    <li>L’envoi est immédiat et ne peut pas être annulé.</li>
                </ul>
            </section>
        </aside>
    </div>

    <section class="card mt-6 overflow-hidden">
        <header class="border-b border-slate-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Historique des envois</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Date</th><th class="w-[40%]">Message</th><th>Destinataires</th><th class="text-right">Appareils</th><th class="text-right">Reçues</th><th class="text-right">Échecs</th><th>Par</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($history as $notification)
                        <tr>
                            <td class="whitespace-nowrap">{{ $notification->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <p class="font-medium text-slate-900">{{ $notification->title }}</p>
                                <p class="line-clamp-2 text-xs text-slate-500">{{ $notification->body }}</p>
                            </td>
                            <td class="text-sm">
                                {{ $labels[$notification->audience] ?? $notification->audience }}
                                @if (! empty($notification->filters['days']))
                                    <span class="text-xs text-slate-500">({{ $notification->filters['days'] }} j)</span>
                                @endif
                                @if (! empty($notification->filters['platform']))
                                    <span class="text-xs text-slate-500">· {{ $notification->filters['platform'] === 'ios' ? 'iOS' : 'Android' }}</span>
                                @endif
                            </td>
                            <td class="text-right tabular-nums">{{ $notification->devices_count }}</td>
                            <td class="text-right font-medium text-ci-600 tabular-nums">{{ $notification->sent_count }}</td>
                            <td class="text-right tabular-nums">
                                {{ $notification->failed_count }}
                                @if ($notification->removed_count)
                                    <span class="block text-xs text-slate-400">{{ $notification->removed_count }} retiré(s)</span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-500">{{ $notification->admin?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-admin.empty message="Aucune notification envoyée pour le moment." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">{{ $history->links() }}</div>
@endsection
