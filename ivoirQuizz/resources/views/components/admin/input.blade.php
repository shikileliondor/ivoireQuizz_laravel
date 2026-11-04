@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])

@php($id = 'field-'.str_replace(['[', ']', '.'], '-', $name))
@php($errorKey = str_replace(['[', ']'], ['.', ''], $name))

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($errorKey, $value) }}"
        @required($required)
        {{ $attributes->except('class')->class(['form-input', 'form-input-error' => $errors->has($errorKey)]) }}
    >
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($errorKey)
        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
