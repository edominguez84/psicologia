{{--
    Renderiza una sección personalizada creada desde /admin/custom-sections.
    Recibe $section (instancia de App\Models\CustomSection).
--}}
@php $hasImage = (bool) $section->image_path; @endphp
<section class="section bg-white">
    <div class="container-x grid gap-12 {{ $hasImage ? 'md:grid-cols-[1.1fr_0.9fr] md:items-center' : '' }}">
        <div class="{{ $hasImage ? '' : 'max-w-2xl' }}">
            <h2 class="text-3xl sm:text-4xl">{{ $section->title }}</h2>
            <div class="prose-soft mt-4">
                <p>{!! nl2br(e($section->body)) !!}</p>
            </div>
        </div>

        @if ($hasImage)
            <div class="mx-auto w-full max-w-md overflow-hidden rounded-[2rem] border border-paper-200 shadow-xl">
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::url($section->image_path) }}"
                    alt="{{ $section->title }}"
                    class="aspect-[4/3] w-full object-cover"
                >
            </div>
        @endif
    </div>
</section>
