@extends('admin.layouts.app')

@section('title', 'Modifier '.$user->name)

@section('content')
    <x-admin.page-header :title="'Modifier '.$user->name" subtitle="XP, score et progression sont calculés par le jeu et ne se modifient pas ici." :back="route('admin.users.show', $user)" />

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card max-w-3xl space-y-5 p-6">
        @csrf
        @method('PUT')
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="name" label="Nom" :value="$user->name" required />
            <x-admin.input name="username" label="Pseudo" :value="$user->username" />
            <x-admin.input name="email" label="Email" type="email" :value="$user->email" required />
            <x-admin.input name="phone" label="Téléphone" :value="$user->phone" />
            <x-admin.input name="city" label="Ville" :value="$user->city" />
        </div>
        <x-admin.textarea name="bio" label="Bio" :value="$user->bio" />
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
@endsection
