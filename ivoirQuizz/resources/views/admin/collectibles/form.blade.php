@extends('admin.layouts.app')

@php($editing = $collectible->exists)

@section('title', $editing ? 'Modifier '.$collectible->name : 'Nouvel objet de collection')

@section('content')
    <x-admin.page-header :title="$editing ? 'Modifier '.$collectible->name : 'Nouvel objet de collection'" :back="route('admin.collectibles.index')" />

    <form method="POST" action="{{ $editing ? route('admin.collectibles.update', $collectible) : route('admin.collectibles.store') }}" enctype="multipart/form-data" class="card max-w-3xl space-y-5 p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="name" label="Nom" :value="$collectible->name" required />
            <x-admin.input name="slug" label="Identifiant (slug)" :value="$collectible->slug" hint="Laissez vide pour le générer depuis le nom." />
            <x-admin.select name="type" label="Type" :options="\App\Support\AdminLabels::options('collectible_type', $types)" :value="$collectible->type" required />
            <x-admin.select name="rarity" label="Rareté" :options="\App\Support\AdminLabels::options('rarity', $rarities)" :value="$collectible->rarity" required />
            <x-admin.select name="region_id" label="Région" :options="$regions->pluck('name', 'id')->all()" :value="$collectible->region_id" placeholder="Aucune" />
            <x-admin.select name="city_id" label="Ville" :options="$cities->pluck('name', 'id')->all()" :value="$collectible->city_id" placeholder="Aucune" />
        </div>
        <x-admin.textarea name="description" label="Description" :value="$collectible->description" rows="4" />
        <x-admin.image :current="$collectible->image" />
        <x-admin.checkbox name="is_active" label="Objet actif" :checked="$collectible->is_active" />

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.collectibles.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Enregistrer' : 'Créer l’objet' }}</button>
        </div>
    </form>
@endsection
