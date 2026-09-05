@php
    $hr = $hr ?? false;
@endphp
<div class="input-field {{ $hr ? 'hr' : '' }}">
    <label for="{{ $id }}">{{ $label }}</label>
    <textarea 
        id="{{ $id }}" 
        name="{{ $id }}"
        rows="5"
    >{{ old($id, $value) }}</textarea>
    @if (!empty($helpText))
        <small>{{ $helpText }}</small>
    @endif
</div>