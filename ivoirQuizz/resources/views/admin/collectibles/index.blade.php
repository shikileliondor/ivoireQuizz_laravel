@extends('admin.layouts.app')

@section('title', 'Collection')

@section('content')
    <x-admin.page-header title="Objets de collection" subtitle="Personnalités et monuments que les joueurs débloquent dans leur passeport.">
        <x-slot:actions>
            <a href="{{ route('admin.collectibles.create') }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouvel objet</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters :action="route('admin.collectibles.index')">
        <div class="min-w-48 flex-1">
            <label for="q" class="form-label">Recherche</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nom" class="form-input">
        </div>
        <x-admin.select name="type" label="Type" :options="\App\Support\AdminLabels::options('collectible_type', $types)" :value="request('type')" placeholder="Tous" class="w-44" />
        <x-admin.select name="rarity" label="Rareté" :options="\App\Support\AdminLabels::options('rarity', $rarities)" :value="request('rarity')" placeholder="Toutes" class="w-40" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Objet</th><th>Type</th><th>Rareté</th><th>Lieu</th><th class="text-right">Débloqué par</th><th>Statut</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($collectibles as $collectible)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($collectible->image)
                                        <img src="{{ Storage::disk('public')->url($collectible->image) }}" alt="" class="size-9 rounded-md object-cover">
                                    @else
                                        <span class="grid size-9 place-items-center rounded-md bg-slate-100 text-slate-400"><x-admin.icon name="sparkles" class="size-4" /></span>
                                    @endif
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $collectible->name }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ $collectible->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><x-admin.enum group="collectible_type" :value="$collectible->type" /></td>
                            <td><x-admin.enum group="rarity" :value="$collectible->rarity" /></td>
                            <td class="text-sm">{{ $collectible->city?->name ?? $collectible->region?->name ?? '—' }}</td>
                            <td class="text-right tabular-nums">{{ $collectible->user_collectibles_count }} joueur(s)</td>
                            <td><x-admin.active :active="$collectible->is_active" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.collectibles.edit', $collectible) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                <x-admin.delete-button :action="route('admin.collectibles.destroy', $collectible)" label="Archiver" confirm="Archiver {{ $collectible->name }} ?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-admin.empty message="Aucun objet de collection." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $collectibles->links() }}</div>
@endsection
