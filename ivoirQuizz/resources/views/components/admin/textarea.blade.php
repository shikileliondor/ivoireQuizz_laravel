@props(['name', 'label', 'value' => null, 'rows' => 3, 'hint' => null, 'required' => false])

@php($id = 'field-'.str_replace(['[', ']', '.'], '-', $name))
@php($errorKey = str_replace(['[', ']'], ['.', ''], $name))

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        {{ $attributes->except('class')->class(['form-input', 'form-input-error' => $errors->has($errorKey)]) }}
    >{{ old($errorKey, $value) }}</textarea>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($errorKey)
        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
