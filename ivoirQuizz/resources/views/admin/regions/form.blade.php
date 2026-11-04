@extends('admin.layouts.app')

@php($editing = $region->exists)

@section('title', $editing ? 'Modifier '.$region->name : 'Nouvelle région')

@section('content')
    <x-admin.page-header :title="$editing ? 'Modifier '.$region->name : 'Nouvelle région'" :back="route('admin.regions.index')" />

    <form method="POST" action="{{ $editing ? route('admin.regions.update', $region) : route('admin.regions.store') }}" enctype="multipart/form-data" class="card max-w-3xl space-y-5 p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="name" label="Nom" :value="$region->name" required />
            <x-admin.input name="slug" label="Identifiant (slug)" :value="$region->slug" hint="Laissez vide pour le générer depuis le nom." />
            <x-admin.input name="order" label="Ordre" type="number" min="0" :value="$region->order" required />
            <x-admin.input name="required_xp" label="XP requis pour débloquer" type="number" min="0" :value="$region->required_xp ?? 0" required />
        </div>
        <x-admin.textarea name="description" label="Description" :value="$region->description" />
        <x-admin.image :current="$region->image" />
        <x-admin.checkbox name="is_active" label="Région active" hint="Une région inactive n’apparaît plus sur la carte du jeu." :checked="$region->is_active" />

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.regions.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Enregistrer' : 'Créer la région' }}</button>
        </div>
    </form>
@endsection
