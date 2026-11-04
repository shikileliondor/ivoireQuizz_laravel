@props(['action', 'confirm' => 'Supprimer définitivement cet élément ?', 'label' => 'Supprimer', 'small' => true])

<form method="POST" action="{{ $action }}" data-confirm="{{ $confirm }}" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" @class(['btn btn-ghost text-red-600 hover:bg-red-50 hover:text-red-700', 'btn-sm' => $small])>
        <x-admin.icon name="trash" class="size-4" />
        <span class="sr-only sm:not-sr-only">{{ $label }}</span>
    </button>
</form>
