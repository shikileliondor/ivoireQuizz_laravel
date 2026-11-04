@props(['message' => 'Aucun élément pour le moment.'])

<div class="px-6 py-12 text-center">
    <x-admin.icon name="inbox" class="mx-auto size-8 text-slate-300" />
    <p class="mt-2 text-sm text-slate-500">{{ $message }}</p>
    @if (! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
