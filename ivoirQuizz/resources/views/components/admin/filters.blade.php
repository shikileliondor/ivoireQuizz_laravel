@props(['action'])

<form method="GET" action="{{ $action }}" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
    {{ $slot }}
    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary">
            <x-admin.icon name="filter" class="size-4" /> Filtrer
        </button>
        @if (request()->query())
            <a href="{{ $action }}" class="btn btn-secondary">Réinitialiser</a>
        @endif
    </div>
</form>
