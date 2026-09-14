@extends('layouts.app')

@section('title', $pageTitle.' · '.config('site.name'))
@section('meta_description', $pageTitle.' de '.config('site.name').'.')

@section('content')
<section class="section bg-white">
    <div class="container-x max-w-3xl prose-soft">
        <p class="eyebrow">Información legal</p>
        <h1 class="mt-3 text-4xl">{{ $pageTitle }}</h1>

        <div class="mt-6 [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-serif [&_h2]:text-sky-800 [&_h3]:mt-8 [&_h3]:text-xl [&_h3]:font-serif [&_h3]:text-sky-800 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-5 [&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:space-y-2 [&_ol]:pl-5 [&_p]:mt-3 [&_a]:text-sky-700 [&_a]:underline">
            {!! $bodyHtml !!}
        </div>

        <p class="mt-10">
            <a href="/" class="btn btn-ghost">← Volver al inicio</a>
        </p>
    </div>
</section>
@endsection
