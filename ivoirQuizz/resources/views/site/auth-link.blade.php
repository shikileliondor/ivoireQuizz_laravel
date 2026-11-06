@extends('site.layout')

@section('content')
    <section class="mx-auto max-w-xl px-4 py-24 text-center sm:px-6">
        <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-stone-200 sm:p-12">
            <h1 class="font-display text-3xl font-extrabold text-ink">{{ $title }}</h1>
            <p class="mt-4 leading-relaxed text-stone-600">{{ $message }}</p>
            <a href="{{ config('site.play_store_url', '/') }}" class="btn btn-primary mt-8">Ouvrir IvoirQuizz</a>
        </div>
    </section>
@endsection
