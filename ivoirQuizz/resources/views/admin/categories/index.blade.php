@extends('admin.layouts.app')

@section('title', 'Catégories')

@section('content')
    <x-admin.page-header title="Catégories" subtitle="Thèmes des questions (histoire, culture, géographie…).">
        <x-slot:actions>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouvelle catégorie</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Catégorie</th><th>Icône</th><th class="text-right">Questions</th><th>Statut</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="size-3 shrink-0 rounded-full ring-1 ring-slate-900/10" style="background-color: {{ $category->color ?: '#cbd5e1' }}"></span>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $category->name }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ $category->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-500">{{ $category->icon ?: '—' }}</td>
                            <td class="text-right tabular-nums"><a href="{{ route('admin.questions.index', ['category' => $category->id]) }}" class="hover:text-brand-600">{{ $category->questions_count }}</a></td>
                            <td><x-admin.active :active="$category->is_active" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                <x-admin.delete-button :action="route('admin.categories.destroy', $category)" confirm="Supprimer la catégorie {{ $category->name }} ?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty message="Aucune catégorie." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
