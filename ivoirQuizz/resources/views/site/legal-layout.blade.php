@extends('site.layout')

@section('content')
    <article class="mx-auto max-w-3xl px-4 pt-12 sm:px-6 sm:pt-16">
        <p class="text-sm font-semibold tracking-widest text-brand-600 uppercase">@yield('eyebrow', 'Informations légales')</p>
        <h1 class="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">@yield('heading')</h1>
        <p class="mt-4 text-sm text-stone-500">
            Dernière mise à jour : {{ \Illuminate\Support\Carbon::parse(config('site.legal_updated_at'))->locale('fr')->translatedFormat('j F Y') }}
        </p>

        <div class="legal mt-8">
            @yield('body')
        </div>
    </article>
@endsection
