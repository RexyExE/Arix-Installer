@props([
    'id',
    'value' => null,
    'options' => [],
])

<div class="input-field">
    <div class="options-wrapper">
        @foreach ($options as $option)
            <label class="layouts-option">
                <input 
                    type="radio"
                    name="{{ $id }}" 
                    value="{{ $option['value'] }}" 
                    {{ (string) $value === (string) $option['value'] ? 'checked' : '' }}
                >
                <div class="label-card">
                    <img src="{{ asset($option['img']) }}" alt="{{ $option['value'] }}">
                </div>
                <span>{{ $option['label'] }}</span>
            </label>
        @endforeach
    </div>
</div>