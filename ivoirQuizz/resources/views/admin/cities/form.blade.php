@extends('admin.layouts.app')

@php($editing = $city->exists)

@section('title', $editing ? 'Modifier '.$city->name : 'Nouvelle ville')

@section('content')
    <x-admin.page-header :title="$editing ? 'Modifier '.$city->name : 'Nouvelle ville'" :back="route('admin.cities.index')" />

    <form method="POST" action="{{ $editing ? route('admin.cities.update', $city) : route('admin.cities.store') }}" enctype="multipart/form-data" class="card max-w-3xl space-y-5 p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.select name="region_id" label="Région" :options="$regions->pluck('name', 'id')->all()" :value="$city->region_id" placeholder="Choisir une région" required />
            <x-admin.input name="name" label="Nom" :value="$city->name" required />
            <x-admin.input name="slug" label="Identifiant (slug)" :value="$city->slug" hint="Laissez vide pour le générer. Sert aussi d’image par défaut : cities/{slug}.jpg" />
            <x-admin.input name="order" label="Ordre dans la région" type="number" min="0" :value="$city->order" required />
            <x-admin.input name="required_xp" label="XP requis pour débloquer" type="number" min="0" :value="$city->required_xp ?? 0" required />
        </div>
        <x-admin.textarea name="description" label="Description" :value="$city->description" />
        <x-admin.image :current="$city->image" hint="Image affichée sur la carte de campagne. JPG, PNG ou WebP, 2 Mo maximum." />
        <x-admin.checkbox name="is_active" label="Ville active" :checked="$city->is_active" />

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.cities.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Enregistrer' : 'Créer la ville' }}</button>
        </div>
    </form>
@endsection
