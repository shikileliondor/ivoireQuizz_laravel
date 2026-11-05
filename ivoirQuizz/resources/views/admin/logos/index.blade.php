@extends('admin.layouts.app')

@section('title', 'Logos')

@section('content')
    <x-admin.page-header title="Logos" :subtitle="number_format($logos->total(), 0, ',', ' ').' logo(s) trouvé(s)'">
        <x-slot:actions>
            <a href="{{ route('admin.logos.create') }}" class="btn btn-secondary"><x-admin.icon name="upload" class="size-4" /> Import en lot</a>
            <a href="{{ route('admin.questions.create', ['logo' => 1]) }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouveau logo</a>
        </x-slot:actions>
    </x-admin.page-header>

    @unless ($categoryExists)
        <div class="mb-5 flex items-start gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-600/20" role="alert">
            <x-admin.icon name="alert" class="size-5 shrink-0" />
            <p>La catégorie Logos n’existe pas encore. Créez-la dans <a href="{{ route('admin.categories.index') }}" class="font-semibold underline">Catégories</a> avec le slug <span class="font-mono">logos</span>.</p>
        </div>
    @endunless

    @if ($missingSourceCount > 0)
        <div class="mb-5 flex items-start gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-600/20">
            <x-admin.icon name="alert" class="size-5 shrink-0" />
            <p>
                {{ $missingSourceCount }} logo(s) sans source. Notez où et quand chaque image a été récupérée : c’est votre trace si une marque conteste.
                <a href="{{ route('admin.logos.index', ['source' => 'missing']) }}" class="font-semibold underline">Les afficher</a>
            </p>
        </div>
    @endif

    <div class="mb-5 rounded-lg bg-white px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200">
        <p><span class="font-semibold text-slate-800">Désactiver</span> retire un logo de l’app sans le supprimer : on peut le réactiver.</p>
        <p class="mt-1"><span class="font-semibold text-slate-800">Archiver</span> le retire définitivement et efface son fichier du serveur. C’est la réponse à une demande de retrait d’une marque.</p>
    </div>

    <x-admin.filters :action="route('admin.logos.index')">
        <div class="min-w-56 flex-1">
            <label for="q" class="form-label">Marque</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nom de la marque" class="form-input">
        </div>
        <x-admin.select name="status" label="Statut" :options="['active' => 'Actifs', 'inactive' => 'Inactifs']" :value="request('status')" placeholder="Tous" class="w-36" />
        <x-admin.select name="source" label="Source" :options="['missing' => 'Source manquante']" :value="request('source')" placeholder="Toutes" class="w-48" />
    </x-admin.filters>

    @if ($logos->isEmpty())
        <div class="card">
            <x-admin.empty message="Aucun logo ne correspond à ces critères." />
        </div>
    @else
        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($logos as $logo)
                @php($url = $logo->image && Storage::disk('public')->exists($logo->image) ? Storage::disk('public')->url($logo->image) : null)
                @php($brand = $logo->answers->first()?->answer_text)
                <li class="card flex flex-col overflow-hidden">
                    {{-- Damier : un fond qui n'est pas transparent se voit tout de suite. --}}
                    <a href="{{ route('admin.questions.edit', $logo) }}" class="grid aspect-square place-items-center p-4" style="background: repeating-conic-gradient(#f1f5f9 0 25%, #fff 0 50%) 50% / 16px 16px" title="Modifier">
                        @if ($url)
                            <img src="{{ $url }}" alt="{{ $brand }}" loading="lazy" class="max-h-full max-w-full object-contain">
                        @else
                            <span class="text-center text-xs font-medium text-red-600">Image manquante : ce logo n’est pas proposé aux joueurs.</span>
                        @endif
                    </a>
                    <div class="flex flex-1 flex-col gap-2 border-t border-slate-100 p-3">
                        <p class="truncate text-sm font-semibold text-slate-900" title="{{ $brand }}">{{ $brand ?? 'Aucune bonne réponse' }}</p>
                        <div class="flex flex-wrap items-center gap-1">
                            <form method="POST" action="{{ route('admin.questions.toggle', $logo) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="{{ $logo->is_active ? 'Désactiver' : 'Activer' }}" class="cursor-pointer">
                                    <x-admin.active :active="$logo->is_active" />
                                </button>
                            </form>
                            @if (blank($logo->image_source))
                                <x-admin.badge color="amber">Source manquante</x-admin.badge>
                            @endif
                        </div>
                        @if (filled($logo->image_source))
                            <p class="line-clamp-2 text-xs text-slate-500" title="{{ $logo->image_source }}">{{ $logo->image_source }}</p>
                        @endif
                        <div class="mt-auto flex items-center justify-between gap-1 pt-1">
                            <a href="{{ route('admin.questions.edit', $logo) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only">Modifier</span></a>
                            <x-admin.delete-button :action="route('admin.questions.destroy', $logo)" label="Archiver" confirm="Archiver ce logo ? Il disparaît de l’app et son fichier est effacé du serveur. Cette action est définitive." />
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-4">{{ $logos->links() }}</div>
@endsection
