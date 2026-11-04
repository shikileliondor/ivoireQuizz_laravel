@extends('admin.layouts.app')

@php($editing = $league->exists)

@section('title', $editing ? 'Modifier '.$league->name : 'Nouvelle ligue')

@section('content')
    <x-admin.page-header :title="$editing ? 'Modifier '.$league->name : 'Nouvelle ligue'" :back="route('admin.leagues.index')" />

    <form method="POST" action="{{ $editing ? route('admin.leagues.update', $league) : route('admin.leagues.store') }}" class="card max-w-2xl space-y-5 p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="name" label="Nom" :value="$league->name" required />
            <x-admin.input name="slug" label="Identifiant (slug)" :value="$league->slug" hint="Laissez vide pour le générer depuis le nom." />
            <x-admin.input name="rank_order" label="Rang" type="number" min="1" :value="$league->rank_order" hint="1 = ligue la plus basse." required />
            <x-admin.input name="icon" label="Icône" :value="$league->icon" />
            <div>
                <label for="field-color" class="form-label">Couleur</label>
                <div class="flex items-center gap-2">
                    <input type="color" value="{{ old('color', $league->color ?: '#F77F00') }}" class="h-9 w-12 cursor-pointer rounded-md border-0 p-0" oninput="document.getElementById('field-color').value = this.value.toUpperCase()" aria-label="Choisir la couleur">
                    <input id="field-color" name="color" type="text" value="{{ old('color', $league->color) }}" placeholder="#F77F00" @class(['form-input font-mono', 'form-input-error' => $errors->has('color')])>
                </div>
                @error('color')
                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <x-admin.checkbox name="is_active" label="Ligue active" :checked="$league->is_active" />

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.leagues.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Enregistrer' : 'Créer la ligue' }}</button>
        </div>
    </form>
@endsection
