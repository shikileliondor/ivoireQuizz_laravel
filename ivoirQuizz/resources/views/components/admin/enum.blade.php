@props(['group', 'value'])

@php($value = $value instanceof \BackedEnum ? $value->value : $value)

<x-admin.badge :color="\App\Support\AdminLabels::color($group, $value)" {{ $attributes }}>
    {{ \App\Support\AdminLabels::label($group, $value) }}
</x-admin.badge>
