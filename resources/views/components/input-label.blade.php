@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1.5 block text-sm font-semibold text-sky-700']) }}>
    {{ $value ?? $slot }}
</label>
