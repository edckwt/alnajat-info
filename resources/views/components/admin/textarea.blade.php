@props(['name', 'label', 'value' => null, 'rows' => 3, 'hint' => null, 'dir' => null])
@php($key = str_replace(['[', ']'], ['.', ''], $name))
<div>
    <label class="form-label" for="f-{{ $key }}">{{ $label }}</label>
    <textarea id="f-{{ $key }}" name="{{ $name }}" rows="{{ $rows }}" @if ($dir) dir="{{ $dir }}" @endif
              {{ $attributes->class(['form-textarea', 'is-invalid' => $errors->has($key)]) }}>{{ old($key, $value) }}</textarea>
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @error($key)<p class="form-error-text">{{ $message }}</p>@enderror
</div>
