@extends('admin.layouts.app')

@section('title', 'Importer des logos')

@section('content')
    <x-admin.page-header title="Importer des logos" :back="route('admin.logos.index')" />

    @unless ($categoryExists)
        <div class="mb-5 flex max-w-3xl items-start gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-600/20" role="alert">
            <x-admin.icon name="alert" class="size-5 shrink-0" />
            <p>La catégorie Logos n’existe pas encore. Créez-la dans <a href="{{ route('admin.categories.index') }}" class="font-semibold underline">Catégories</a> avec le slug <span class="font-mono">logos</span> avant d’importer.</p>
        </div>
    @endunless

    <form method="POST" action="{{ route('admin.logos.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
        @csrf

        <section class="card space-y-5 p-6">
            <div class="space-y-1 text-sm text-slate-600">
                <p>Chaque fichier devient une question. <span class="font-semibold text-slate-800">Le nom du fichier est la bonne réponse</span>, tel qu’il s’affichera : <span class="font-mono">MTN.png</span>, <span class="font-mono">Orange.png</span>, <span class="font-mono">Moov_Africa.png</span> (le « _ » devient une espace).</p>
                <p>Les mauvaises réponses sont tirées parmi les autres marques de la catégorie. Une marque déjà présente est ignorée.</p>
            </div>

            <div>
                <label for="field-logos" class="form-label">Fichiers <span class="text-red-500">*</span></label>
                <input
                    id="field-logos"
                    name="logos[]"
                    type="file"
                    multiple
                    required
                    accept="image/png,image/webp"
                    class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                >
                <p class="mt-1 text-xs text-slate-500">
                    PNG ou WebP à fond transparent, {{ \App\Support\LogoImage::MIN_SIDE }} px minimum sur le plus grand côté, 2 Mo maximum.
                    Au-delà de {{ \App\Support\LogoImage::MAX_SIDE }} px, l’image est réduite automatiquement. {{ \App\Http\Requests\Admin\LogoImportRequest::MAX_FILES }} fichiers par envoi.
                </p>
                @error('logos')
                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
                @foreach ($errors->get('logos.*') as $messages)
                    @foreach ($messages as $message)
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @endforeach
                @endforeach
            </div>

            <x-admin.input name="question_text" label="Question" :value="\App\Models\Question::LOGO_QUESTION_TEXT" maxlength="1000" required />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.select name="difficulty" label="Difficulté" :options="\App\Support\AdminLabels::options('difficulty', $difficulties)" value="easy" required />
                <x-admin.input name="image_source" label="Source des images" maxlength="255" hint="Où et quand les images ont été récupérées, ex. : sites officiels des marques, novembre 2026. Jamais envoyé à l’app." />
            </div>

            <x-admin.checkbox name="is_active" label="Activer tout de suite" hint="Sinon, les logos restent inactifs le temps de vérifier les mauvaises réponses proposées." />
        </section>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('admin.logos.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary"><x-admin.icon name="upload" class="size-4" /> Importer</button>
        </div>
    </form>
@endsection
