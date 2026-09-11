@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400']) }}>
