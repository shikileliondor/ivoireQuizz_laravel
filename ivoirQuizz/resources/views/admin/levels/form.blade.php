@extends('admin.layouts.app')

@php($editing = $level->exists)
@php($cityOptions = $cities->groupBy(fn ($city) => $city->region?->name ?? 'Sans région')->map(fn ($group) => $group->pluck('name', 'id')->all())->all())

@section('title', $editing ? 'Modifier '.$level->title : 'Nouveau niveau')

@section('content')
    <x-admin.page-header :title="$editing ? 'Modifier '.$level->title : 'Nouveau niveau'" :back="route('admin.levels.index')">
        @if ($editing)
            <x-slot:actions>
                <a href="{{ route('admin.questions.index', ['level' => $level->id]) }}" class="btn btn-secondary"><x-admin.icon name="question" class="size-4" /> Questions du niveau</a>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.levels.update', $level) : route('admin.levels.store') }}" class="max-w-4xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="card space-y-5 p-6">
            <h2 class="text-sm font-semibold text-slate-900">Identité</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.select name="city_id" label="Ville" :options="$cityOptions" :value="$level->city_id" placeholder="Choisir une ville" required />
                <x-admin.input name="title" label="Titre" :value="$level->title" required />
                <x-admin.input name="slug" label="Identifiant (slug)" :value="$level->slug" hint="Laissez vide pour le générer depuis le titre." />
                <x-admin.input name="order" label="Ordre dans la ville" type="number" min="0" :value="$level->order" required />
            </div>
            <x-admin.textarea name="description" label="Description" :value="$level->description" />
        </section>

        <section class="card space-y-5 p-6">
            <h2 class="text-sm font-semibold text-slate-900">Règles de jeu</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-admin.select name="difficulty" label="Difficulté" :options="\App\Support\AdminLabels::options('difficulty', $difficulties)" :value="$level->difficulty" required />
                <x-admin.input name="questions_count" label="Questions par partie" type="number" min="1" max="100" :value="$level->questions_count" required />
                <x-admin.input name="passing_score" label="Score de réussite (%)" type="number" min="0" max="100" :value="$level->passing_score" required />
                <x-admin.input name="required_xp" label="XP requis" type="number" min="0" :value="$level->required_xp" required />
            </div>
            <x-admin.checkbox name="is_boss" label="Niveau boss" hint="Dernier défi de la ville, mis en avant dans l’app." :checked="$level->is_boss" />
        </section>

        <section class="card space-y-5 p-6">
            <h2 class="text-sm font-semibold text-slate-900">Récompenses</h2>
            <div class="grid gap-5 sm:grid-cols-3">
                <x-admin.input name="xp_reward" label="XP" type="number" min="0" :value="$level->xp_reward" required />
                <x-admin.input name="coins_reward" label="Pièces" type="number" min="0" :value="$level->coins_reward" required />
                <x-admin.input name="gems_reward" label="Gemmes" type="number" min="0" :value="$level->gems_reward" required />
            </div>
            <x-admin.checkbox name="is_active" label="Niveau actif" :checked="$level->is_active" />
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.levels.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Enregistrer' : 'Créer le niveau' }}</button>
        </div>
    </form>
@endsection
