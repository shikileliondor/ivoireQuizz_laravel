@extends('admin.layouts.app')

@php
    $definition = $automation->definition();
    $settings = $automation->settingsWithDefaults();
    $isReminder = $automation->isReminder();
@endphp

@section('title', $definition['label'])

@section('content')
    <x-admin.page-header :title="$definition['label']" :subtitle="$definition['description']" :back="route('admin.automations.index')">
        <x-slot:actions>
            <x-admin.badge :color="$isReminder ? 'purple' : 'blue'">{{ $isReminder ? 'Relance' : 'Action' }}</x-admin.badge>
            <x-admin.active :active="$automation->enabled" />
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.automations.update', $automation->key) }}" class="space-y-6 lg:col-span-2">
            @csrf
            @method('PUT')

            <section class="card space-y-5 p-6">
                <h2 class="text-sm font-semibold text-slate-900">Réglages</h2>
                <x-admin.checkbox name="enabled" label="Activée" :checked="$automation->enabled" hint="Désactivée, plus aucune notification de ce type ne part." />

                @if ($definition['settings'] !== [])
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ($definition['settings'] as $name => $setting)
                            @if (($setting['type'] ?? null) === 'boolean')
                                <x-admin.checkbox :name="'settings['.$name.']'" :label="$setting['label']" :checked="$settings[$name]" :hint="$setting['hint'] ?? null" class="sm:col-span-2" />
                            @else
                                <x-admin.input :name="'settings['.$name.']'" :label="$setting['label']" type="number" :min="$setting['min']" :max="$setting['max']" :value="$settings[$name]" :hint="$setting['hint'] ?? null" required />
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card space-y-6 p-6">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Textes</h2>
                    <p class="mt-1 text-xs text-slate-500">Un texte par ligne : chaque joueur en reçoit un, différent d’un jour à l’autre. Le téléphone en affiche environ deux lignes.</p>
                </div>

                @foreach ($definition['segments'] as $segment => $default)
                    @php($template = $automation->template($segment))
                    <div @class(['space-y-3', 'border-t border-slate-100 pt-5' => ! $loop->first])>
                        @if (count($definition['segments']) > 1)
                            <h3 class="text-sm font-medium text-slate-800">{{ $default['label'] }}</h3>
                        @endif
                        <x-admin.input :name="'templates['.$segment.'][title]'" label="Titre" maxlength="100" :value="$template['title']" required />
                        <x-admin.textarea :name="'templates['.$segment.'][bodies]'" label="Textes" :rows="max(2, count($template['bodies']) + 1)" :value="implode(PHP_EOL, $template['bodies'])" required />
                    </div>
                @endforeach
            </section>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary"><x-admin.icon name="check" class="size-4" /> Enregistrer</button>
            </div>
        </form>

        <aside class="space-y-4">
            <section class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">Variables</h2>
                <p class="mt-1 text-xs text-slate-500">À écrire telles quelles dans les titres et textes.</p>
                <dl class="mt-3 space-y-1.5 text-sm">
                    @foreach ($definition['variables'] as $variable => $meaning)
                        <div class="flex items-baseline justify-between gap-3">
                            <dt><code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700">{{ '{'.$variable.'}' }}</code></dt>
                            <dd class="text-right text-xs text-slate-500">{{ $meaning }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <form method="POST" action="{{ route('admin.automations.test', $automation->key) }}" class="card space-y-3 p-5">
                @csrf
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Tester sur mes appareils</h2>
                    <p class="mt-1 text-xs text-slate-500">Envoie les textes <strong>enregistrés</strong> avec des valeurs d’exemple. Ne compte pas dans le plafond.</p>
                </div>
                @if (count($definition['segments']) > 1)
                    <x-admin.select name="segment" label="Variante" :options="array_map(fn ($segment) => $segment['label'], $definition['segments'])" />
                @else
                    <input type="hidden" name="segment" value="{{ array_key_first($definition['segments']) }}">
                @endif
                <button type="submit" class="btn btn-secondary w-full" @disabled($mine === 0)><x-admin.icon name="bell" class="size-4" /> M’envoyer un test</button>
                @if ($mine === 0)
                    <p class="text-xs text-slate-500">Connectez-vous à l’app avec ce compte admin pour enregistrer un appareil.</p>
                @endif
            </form>

            @if ($isReminder)
                <section class="card p-5 text-xs text-slate-500">
                    Cette relance respecte les <a href="{{ route('admin.automations.index') }}" class="font-medium text-brand-600 hover:text-brand-700">règles communes</a> : plafond par joueur et par jour, et heures calmes.
                    @if ($automation->last_run_at)
                        <span class="mt-2 block">Dernier passage : {{ $automation->last_run_at->format('d/m/Y H:i') }}.</span>
                    @endif
                </section>
            @endif
        </aside>
    </div>
@endsection
