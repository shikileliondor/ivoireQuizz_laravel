@extends('admin.layouts.app')

@section('title', 'Ligues')

@section('content')
    <x-admin.page-header title="Ligues" subtitle="Paliers de compétition hebdomadaire, du plus bas au plus haut rang.">
        <x-slot:actions>
            <a href="{{ route('admin.leagues.create') }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouvelle ligue</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th class="w-16">Rang</th><th>Ligue</th><th>Icône</th><th class="text-right">Saisons</th><th>Statut</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($leagues as $league)
                        <tr>
                            <td class="font-semibold text-slate-500 tabular-nums">{{ $league->rank_order }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="size-3 shrink-0 rounded-full ring-1 ring-slate-900/10" style="background-color: {{ $league->color ?: '#cbd5e1' }}"></span>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $league->name }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ $league->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-500">{{ $league->icon ?: '—' }}</td>
                            <td class="text-right tabular-nums">{{ $league->seasons_count }}</td>
                            <td><x-admin.active :active="$league->is_active" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.leagues.edit', $league) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                <x-admin.delete-button :action="route('admin.leagues.destroy', $league)" confirm="Supprimer la ligue {{ $league->name }} ?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-admin.empty message="Aucune ligue." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <section class="card mt-6 overflow-hidden">
        <header class="border-b border-slate-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Saisons récentes</h2>
            <p class="text-xs text-slate-500">Créées et clôturées automatiquement par le jeu.</p>
        </header>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Ligue</th><th>Période</th><th class="text-right">Participants</th><th>Statut</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($seasons as $season)
                        <tr>
                            <td class="font-medium">{{ $season->league?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap">{{ $season->starts_at->format('d/m/Y') }} → {{ $season->ends_at->format('d/m/Y') }}</td>
                            <td class="text-right tabular-nums">{{ $season->members_count }}</td>
                            <td><x-admin.enum group="season_status" :value="$season->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-admin.empty message="Aucune saison pour le moment." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
