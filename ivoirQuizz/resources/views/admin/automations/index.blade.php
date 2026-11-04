@extends('admin.layouts.app')

@section('title', 'Notifications automatiques')

@section('content')
    @php
        $when = function (\App\Models\PushAutomation $automation): string {
            $definition = $automation->definition();
            if ($definition['category'] === \App\Services\Notifications\PushCatalog::ACTION) {
                return 'Immédiat';
            }
            if (($hour = $automation->setting('hour')) !== null) {
                return 'Chaque jour à '.$hour.' h';
            }

            return ($definition['every'] ?? 60) >= 60 ? 'Vérifié chaque heure' : 'Vérifié toutes les '.$definition['every'].' min';
        };
    @endphp

    <x-admin.page-header title="Notifications automatiques" subtitle="Rappel du matin, relances et notifications liées aux actions des joueurs. Les textes et réglages sont modifiables." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @foreach ([
                ['Relances', 'Pour faire revenir les joueurs. Limitées par les règles ci-contre.', $reminders],
                ['Actions', 'Réponses à ce qu’a fait une autre personne : toujours envoyées, sans plafond.', $actions],
            ] as [$heading, $intro, $automations])
                <section class="card overflow-hidden">
                    <header class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-900">{{ $heading }}</h2>
                        <p class="text-xs text-slate-500">{{ $intro }}</p>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead><tr><th class="w-[48%]">Notification</th><th>Quand</th><th class="text-right">Envoyées (7 j)</th><th>Statut</th><th></th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($automations as $key => $automation)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.automations.edit', $key) }}" class="font-medium text-slate-900 hover:text-brand-600">{{ $automation->definition()['label'] }}</a>
                                            <p class="text-xs text-slate-500">{{ $automation->definition()['description'] }}</p>
                                        </td>
                                        <td class="whitespace-nowrap text-sm">{{ $when($automation) }}</td>
                                        <td class="text-right tabular-nums">{{ number_format((int) ($counts[$key] ?? 0), 0, ',', ' ') }}</td>
                                        <td><x-admin.active :active="$automation->enabled" /></td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.automations.edit', $key) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>

        <aside class="space-y-4">
            @include('admin.notifications._scheduler')

            <form method="POST" action="{{ route('admin.automations.settings') }}" class="card space-y-4 p-5">
                @csrf
                @method('PUT')
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Règles des relances</h2>
                    <p class="mt-1 text-xs text-slate-500">Une relance bloquée par le plafond repart au prochain passage si elle a encore un sens (coffre, défi) ; sinon elle est abandonnée.</p>
                </div>
                @foreach (\App\Services\Notifications\PushCatalog::GLOBAL_SETTINGS as $name => $setting)
                    <x-admin.input :name="$name" :label="$setting['label']" type="number" :min="$setting['min']" :max="$setting['max']" :value="$globals[$name]" required />
                @endforeach
                <p class="text-xs text-slate-500">Pendant les heures calmes, aucune relance ne part (heure d’Abidjan).</p>
                <button type="submit" class="btn btn-secondary w-full">Enregistrer</button>
            </form>
        </aside>
    </div>
@endsection
