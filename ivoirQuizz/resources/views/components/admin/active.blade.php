@props(['active'])

@if ($active)
    <x-admin.badge color="green">Actif</x-admin.badge>
@else
    <x-admin.badge color="slate">Inactif</x-admin.badge>
@endif
