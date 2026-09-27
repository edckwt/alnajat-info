@props(['name', 'label', 'value' => null, 'type' => 'text', 'hint' => null, 'dir' => null, 'required' => false, 'icon' => null])
@php($key = str_replace(['[', ']'], ['.', ''], $name))
<div>
    <label class="form-label" for="f-{{ $key }}">{{ $label }}</label>
    @if ($icon)<div class="input-group"><span class="input-group-icon"><x-admin.icon :name="$icon" class="w-4 h-4" /></span>@endif
    <input id="f-{{ $key }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($key, $value) }}"
           @if ($dir) dir="{{ $dir }}" @endif @required($required)
           {{ $attributes->class(['form-input', 'is-invalid' => $errors->has($key)]) }}>
    @if ($icon)</div>@endif
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @error($key)<p class="form-error-text">{{ $message }}</p>@enderror
</div>
