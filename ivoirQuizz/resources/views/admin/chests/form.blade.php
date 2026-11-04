@extends('admin.layouts.app')

@php($editing = $chest->exists)

@section('title', $editing ? 'Modifier '.$chest->name : 'Nouveau coffre')

@section('content')
    <x-admin.page-header :title="$editing ? 'Modifier '.$chest->name : 'Nouveau coffre'" :back="route('admin.chests.index')" />

    <form method="POST" action="{{ $editing ? route('admin.chests.update', $chest) : route('admin.chests.store') }}" enctype="multipart/form-data" class="card max-w-3xl space-y-5 p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="name" label="Nom" :value="$chest->name" required />
            <x-admin.select name="type" label="Type" :options="\App\Support\AdminLabels::options('chest_type')" :value="$chest->type" required />
        </div>

        <div>
            <p class="form-label">Gains (minimum – maximum)</p>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['xp' => 'XP', 'coins' => 'Pièces', 'gems' => 'Gemmes'] as $key => $label)
                    <div class="rounded-lg p-3 ring-1 ring-slate-200">
                        <p class="mb-2 text-xs font-semibold text-slate-500 uppercase">{{ $label }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <x-admin.input :name="'min_'.$key" label="Min" type="number" min="0" :value="$chest->{'min_'.$key} ?? 0" required />
                            <x-admin.input :name="'max_'.$key" label="Max" type="number" min="0" :value="$chest->{'max_'.$key} ?? 0" required />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <x-admin.image :current="$chest->image" />
        <x-admin.checkbox name="is_active" label="Coffre actif" hint="Un coffre inactif n’est plus distribué." :checked="$chest->is_active" />

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.chests.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Enregistrer' : 'Créer le coffre' }}</button>
        </div>
    </form>
@endsection
