@extends('admin.layouts.app')

@php
    $editing = $question->exists;
    $levelOptions = $levels->groupBy(fn ($level) => $level->city?->name ?? 'Sans ville')->map(fn ($group) => $group->pluck('title', 'id')->all())->all();
    $maxAnswers = \App\Http\Requests\Admin\QuestionRequest::MAX_ANSWERS;

    // Lignes de réponses : valeurs renvoyées après une erreur, sinon réponses existantes, complétées jusqu'à 4 lignes minimum.
    $rows = old('answers') !== null
        ? collect(old('answers'))
        : $answers->values()->map(fn ($answer) => ['id' => $answer->id, 'text' => $answer->answer_text]);
    $correct = old('correct', $answers->values()->search(fn ($answer) => $answer->is_correct));
    $lastKey = $rows->keys()->map(fn ($key) => (int) $key)->max() ?? -1;
    $rowCount = max(4, min($maxAnswers, $lastKey + 2));

    // Un logo revient à la grille des logos plutôt qu'à la liste des questions.
    $logosId = $categories->firstWhere('slug', \App\Models\Category::LOGOS)?->id;
    $isLogo = $logosId !== null && (int) old('category_id', $question->category_id) === (int) $logosId;
    $backUrl = $isLogo ? route('admin.logos.index') : route('admin.questions.index');
@endphp

@section('title', $editing ? 'Modifier la question' : 'Nouvelle question')

@section('content')
    <x-admin.page-header :title="$editing ? 'Modifier la question #'.$question->id : ($isLogo ? 'Nouveau logo' : 'Nouvelle question')" :back="$backUrl">
        @if ($editing)
            <x-slot:actions>
                <x-admin.active :active="$question->is_active" />
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    @if ($editing && $question->source_key)
        <div class="mb-5 flex max-w-4xl items-start gap-3 rounded-lg bg-sky-50 px-4 py-3 text-sm text-sky-800 ring-1 ring-sky-600/20">
            <x-admin.icon name="alert" class="size-5 shrink-0" />
            <p>
                Question importée depuis les fichiers de campagne (<span class="font-mono">{{ $question->source_key }}</span>, version {{ $question->source_version }}).
                Vos modifications sont conservées, sauf si le fichier JSON de cette question change : le prochain import créera alors une nouvelle version et désactivera celle-ci.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.questions.update', $question) : route('admin.questions.store') }}" enctype="multipart/form-data" class="max-w-4xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="card space-y-5 p-6">
            <x-admin.textarea name="question_text" label="Question" :value="$question->question_text" rows="3" required />

            <div>
                <p class="form-label">Réponses <span class="text-red-500">*</span></p>
                <p class="mb-3 text-xs text-slate-500">Cochez la bonne réponse. Entre 2 et {{ $maxAnswers }} réponses ; les lignes vides sont ignorées.</p>
                <div class="space-y-2">
                    @for ($i = 0; $i < $rowCount; $i++)
                        @php($row = $rows->get($i, []))
                        <div @class(['flex items-center gap-3 rounded-lg p-2 ring-1', 'bg-ci-50 ring-ci-500/30' => (string) $correct === (string) $i && $correct !== false, 'ring-slate-200' => ! ((string) $correct === (string) $i && $correct !== false)])>
                            <input type="hidden" name="answers[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                            <label class="flex shrink-0 cursor-pointer items-center gap-2 text-xs font-medium text-slate-600">
                                <input type="radio" name="correct" value="{{ $i }}" @checked($correct !== false && (string) $correct === (string) $i) class="size-4 accent-ci-500">
                                <span class="sr-only">Bonne réponse</span>
                                <span class="grid size-6 place-items-center rounded-md bg-slate-100 font-semibold text-slate-600">{{ chr(65 + $i) }}</span>
                            </label>
                            <input
                                type="text"
                                name="answers[{{ $i }}][text]"
                                value="{{ $row['text'] ?? '' }}"
                                maxlength="255"
                                placeholder="Réponse {{ chr(65 + $i) }}"
                                aria-label="Réponse {{ chr(65 + $i) }}"
                                @class(['form-input', 'form-input-error' => $errors->has('answers.'.$i.'.text')])
                            >
                        </div>
                        @error('answers.'.$i.'.text')
                            <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    @endfor
                </div>
                @error('answers')
                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
                @error('correct')
                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-admin.textarea name="explanation" label="Explication" :value="$question->explanation" rows="2" hint="Affichée au joueur après sa réponse." />
        </section>

        <section class="card space-y-5 p-6">
            <h2 class="text-sm font-semibold text-slate-900">Classement</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.select name="level_id" label="Niveau" :options="$levelOptions" :value="$question->level_id" placeholder="Aucun (lancement rapide uniquement)" hint="Sans niveau, la question n’apparaît pas sur la carte : elle se joue seulement via sa catégorie. Un logo n’a jamais de niveau." />
                <x-admin.select name="category_id" label="Catégorie" :options="$categories->pluck('name', 'id')->all()" :value="$question->category_id" placeholder="Aucune" hint="Les questions Logos ne sortent que dans leur catégorie, jamais sur la carte, en mode mixte, défi du jour ou défi sans catégorie. Une question image qui parle de logo doit y être rangée." />
                <x-admin.select name="difficulty" label="Difficulté" :options="\App\Support\AdminLabels::options('difficulty', $difficulties)" :value="$question->difficulty" required />
                <x-admin.select name="type" label="Type" :options="\App\Support\AdminLabels::options('question_type', $types)" :value="$question->type" required />
            </div>
        </section>

        <section class="card space-y-5 p-6">
            <h2 class="text-sm font-semibold text-slate-900">Barème et média</h2>
            <div class="grid gap-5 sm:grid-cols-3">
                <x-admin.input name="points" label="Points" type="number" min="0" max="1000" :value="$question->points" required />
                <x-admin.input name="xp_reward" label="XP" type="number" min="0" max="1000" :value="$question->xp_reward" required />
                <x-admin.input name="time_limit" label="Temps limite (s)" type="number" min="5" max="300" :value="$question->time_limit" required />
            </div>
            <x-admin.image :current="$question->image" contain hint="Obligatoire pour les questions de type image. JPG, PNG ou WebP, 2 Mo maximum. Logos : PNG ou WebP à fond transparent, 128 px minimum ; au-delà de 512 px, l’image est réduite automatiquement." />
            <x-admin.input name="image_source" label="Source de l’image" :value="$question->image_source" maxlength="255" hint="Où et quand l’image a été récupérée, ex. : site officiel orange.ci, novembre 2026. Jamais envoyé à l’app." />
            <x-admin.checkbox name="is_active" label="Question active" hint="Une question inactive n’est plus tirée dans les parties." :checked="$question->is_active" />
        </section>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ $backUrl }}" class="btn btn-secondary">Annuler</a>
            @unless ($editing)
                <button type="submit" name="create_another" value="1" class="btn btn-secondary">Créer et en ajouter une autre</button>
            @endunless
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Enregistrer' : 'Créer la question' }}</button>
        </div>
    </form>
@endsection
