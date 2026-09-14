@props(['dark' => false, 'class' => 'h-9 w-auto'])

@php
    $logo = app(\App\Services\SiteSettingsService::class)->get('logo');
    $wordmarkColor = $dark ? 'text-on-dark' : 'text-heading';
@endphp

@if (!empty($logo['path']))
    <span class="flex min-w-0 items-center gap-2.5">
        <img
            src="{{ \Illuminate\Support\Facades\Storage::url($logo['path']) }}"
            alt="{{ config('site.name') }}"
            class="{{ $class }} shrink-0 rounded-full object-cover"
        >
        <span class="truncate font-serif text-base leading-none sm:text-lg {{ $wordmarkColor }}">{{ config('site.name') }}</span>
    </span>
@else
    <span class="flex min-w-0 items-center gap-2.5">
        {{-- Marca por defecto: una hoja/onda de calma, en tonos celeste pastel. --}}
        <svg viewBox="0 0 40 40" class="{{ $class }} aspect-square shrink-0" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="20" cy="20" r="19" fill="{{ $dark ? '#ffffff1a' : 'var(--color-sky-100)' }}" stroke="{{ $dark ? '#ffffff33' : 'var(--color-sky-200)' }}" stroke-width="1" />
            <path d="M12 23c2.5-8.5 9-13 17-11.5-1 8-7 13.5-15.5 14.5-3.2.4-4.8-.7-1.5-3Z" fill="var(--color-sky-500)" />
            <path d="M13.5 26.5c4.5 2.3 12.5 1.8 16-4.8" stroke="{{ $dark ? '#ffffff' : 'var(--color-sky-800)' }}" stroke-width="1.8" stroke-linecap="round" fill="none" />
            <circle cx="26" cy="14" r="1.6" fill="{{ $dark ? '#ffffff' : 'var(--color-sky-700)' }}" />
        </svg>
        <span class="truncate font-serif text-base leading-none sm:text-lg {{ $wordmarkColor }}">{{ config('site.name') }}</span>
    </span>
@endif
