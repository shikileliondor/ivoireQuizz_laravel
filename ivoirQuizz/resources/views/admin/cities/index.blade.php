@extends('admin.layouts.app')

@section('title', 'Villes')

@section('content')
    <x-admin.page-header title="Villes" subtitle="Chaque ville regroupe une série de niveaux de campagne.">
        <x-slot:actions>
            <a href="{{ route('admin.cities.create', request()->only('region')) }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouvelle ville</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters :action="route('admin.cities.index')">
        <div class="min-w-48 flex-1">
            <label for="q" class="form-label">Recherche</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nom de la ville" class="form-input">
        </div>
        <x-admin.select name="region" label="Région" :options="$regions->pluck('name', 'id')->all()" :value="request('region')" placeholder="Toutes" class="w-52" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Ville</th><th>Région</th><th class="w-16">Ordre</th><th class="text-right">Niveaux</th><th class="text-right">XP requis</th><th>Statut</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($cities as $city)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($city->image)
                                        <img src="{{ Storage::disk('public')->url($city->image) }}" alt="" class="size-9 rounded-md object-cover">
                                    @else
                                        <span class="grid size-9 place-items-center rounded-md bg-slate-100 text-slate-400"><x-admin.icon name="building" class="size-4" /></span>
                                    @endif
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $city->name }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ $city->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $city->region?->name ?? '—' }}</td>
                            <td class="text-slate-500 tabular-nums">{{ $city->order }}</td>
                            <td class="text-right tabular-nums"><a href="{{ route('admin.levels.index', ['city' => $city->id]) }}" class="hover:text-brand-600">{{ $city->levels_count }}</a></td>
                            <td class="text-right tabular-nums">{{ number_format($city->required_xp, 0, ',', ' ') }}</td>
                            <td><x-admin.active :active="$city->is_active" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.levels.create', ['city' => $city->id]) }}" class="btn btn-ghost btn-sm" title="Ajouter un niveau"><x-admin.icon name="plus" class="size-4" /><span class="sr-only">Ajouter un niveau</span></a>
                                <a href="{{ route('admin.cities.edit', $city) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                <x-admin.delete-button :action="route('admin.cities.destroy', $city)" confirm="Supprimer la ville {{ $city->name }} ?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-admin.empty message="Aucune ville." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $cities->links() }}</div>
@endsection
