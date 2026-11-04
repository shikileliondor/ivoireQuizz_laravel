@extends('admin.layouts.app')

@section('title', 'Régions')

@section('content')
    <x-admin.page-header title="Régions" subtitle="Premier niveau de la carte de campagne. L’ordre détermine l’affichage dans l’app.">
        <x-slot:actions>
            <a href="{{ route('admin.regions.create') }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouvelle région</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th class="w-16">Ordre</th><th>Région</th><th class="text-right">Villes</th><th class="text-right">Niveaux</th><th class="text-right">XP requis</th><th>Statut</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($regions as $region)
                        <tr>
                            <td class="text-slate-500 tabular-nums">{{ $region->order }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($region->image)
                                        <img src="{{ Storage::disk('public')->url($region->image) }}" alt="" class="size-9 rounded-md object-cover">
                                    @endif
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $region->name }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ $region->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right tabular-nums"><a href="{{ route('admin.cities.index', ['region' => $region->id]) }}" class="hover:text-brand-600">{{ $region->cities_count }}</a></td>
                            <td class="text-right tabular-nums">{{ $region->levels_count }}</td>
                            <td class="text-right tabular-nums">{{ number_format($region->required_xp, 0, ',', ' ') }}</td>
                            <td><x-admin.active :active="$region->is_active" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.cities.create', ['region' => $region->id]) }}" class="btn btn-ghost btn-sm" title="Ajouter une ville"><x-admin.icon name="plus" class="size-4" /><span class="sr-only">Ajouter une ville</span></a>
                                <a href="{{ route('admin.regions.edit', $region) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                <x-admin.delete-button :action="route('admin.regions.destroy', $region)" confirm="Supprimer la région {{ $region->name }} ?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-admin.empty message="Aucune région." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
