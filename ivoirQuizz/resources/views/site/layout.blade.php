<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ View::hasSection('title') ? View::yieldContent('title').' · '.config('app.name') : config('app.name')." — Le quiz de la Côte d'Ivoire" }}</title>
    <meta name="description" content="@yield('description', "Parcourez la Côte d'Ivoire région par région et testez votre culture ivoirienne : histoire, géographie, personnalités, gastronomie, musique.")">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#f77f00">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', config('app.name').' — Le quiz de la Côte d\'Ivoire')">
    <meta property="og:description" content="@yield('description', "Parcourez la Côte d'Ivoire région par région et testez votre culture ivoirienne.")">
    <meta property="og:url" content="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:700,800|instrument-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Aller au contenu</a>

    @php
        $nav = [
            ['label' => 'Le jeu', 'href' => route('site.home').'#jeu'],
            ['label' => 'Questions fréquentes', 'href' => route('site.home').'#faq'],
            ['label' => 'Confidentialité', 'href' => route('site.privacy')],
        ];
    @endphp

    <header class="sticky top-0 z-40 border-b border-stone-200/70 bg-sand/85 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('site.home') }}" aria-label="{{ config('app.name') }}, accueil">
                @include('site.partials.logo')
            </a>

            <nav class="hidden items-center gap-7 text-sm font-medium text-stone-600 md:flex" aria-label="Navigation principale">
                @foreach ($nav as $item)
                    <a href="{{ $item['href'] }}" class="transition hover:text-ink">{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ config('site.play_store_url') ?: route('site.home').'#telecharger' }}" class="btn btn-primary" @if (config('site.play_store_url')) target="_blank" rel="noopener" @endif>Télécharger</a>
            </nav>

            <button type="button" class="-mr-2 rounded-lg p-2 text-stone-700 md:hidden" aria-expanded="false" aria-controls="menu-mobile" data-menu-toggle>
                <span class="sr-only">Menu</span>
                <x-admin.icon name="menu" class="size-6" />
            </button>
        </div>

        <nav id="menu-mobile" class="border-t border-stone-200 bg-sand px-4 pt-2 pb-4 md:hidden" aria-label="Navigation mobile" hidden data-menu>
            @foreach ($nav as $item)
                <a href="{{ $item['href'] }}" class="block rounded-lg px-2 py-2.5 font-medium text-stone-700 hover:bg-white">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ route('site.account-deletion') }}" class="block rounded-lg px-2 py-2.5 font-medium text-stone-700 hover:bg-white">Supprimer mon compte</a>
        </nav>
    </header>

    <main id="contenu">
        @yield('content')
    </main>

    <footer class="mt-24 bg-ink text-stone-300">
        <div class="h-1.5 bg-[linear-gradient(90deg,#f77f00_0_33.3%,#fff_33.3%_66.6%,#009e60_66.6%)]"></div>
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                @include('site.partials.logo', ['tone' => 'text-white'])
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-stone-400">
                    Le quiz qui fait voyager à travers les régions, les villes et la culture de la Côte d'Ivoire.
                </p>
                @if (config('site.contact_email'))
                    <p class="mt-4 text-sm">
                        <a href="mailto:{{ config('site.contact_email') }}" class="font-medium text-white underline decoration-stone-600 underline-offset-4 hover:decoration-brand-500">{{ config('site.contact_email') }}</a>
                    </p>
                @endif
            </div>

            <div>
                <h2 class="text-xs font-semibold tracking-widest text-stone-500 uppercase">Informations légales</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('site.privacy') }}" class="hover:text-white">Politique de confidentialité</a></li>
                    <li><a href="{{ route('site.cookies') }}" class="hover:text-white">Politique cookies</a></li>
                    <li><a href="{{ route('site.terms') }}" class="hover:text-white">Conditions d'utilisation</a></li>
                    <li><a href="{{ route('site.legal') }}" class="hover:text-white">Mentions légales</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-xs font-semibold tracking-widest text-stone-500 uppercase">Votre compte</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('site.account-deletion') }}" class="hover:text-white">Supprimer mon compte</a></li>
                    <li><a href="{{ route('site.privacy') }}#droits" class="hover:text-white">Exercer mes droits</a></li>
                    <li><a href="{{ route('site.home') }}#faq" class="hover:text-white">Questions fréquentes</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <p class="mx-auto max-w-6xl px-4 py-6 text-xs text-stone-500 sm:px-6">
                © {{ now()->year }} {{ config('site.publisher.name') }}. Tous droits réservés.
            </p>
        </div>
    </footer>

    <div class="fixed inset-x-3 bottom-3 z-50 mx-auto max-w-xl rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-stone-200 sm:p-5" role="region" aria-label="Information sur les cookies" hidden data-cookie-notice>
        <p class="text-sm leading-relaxed text-stone-700">
            Ce site n'utilise que des cookies <strong class="text-ink">indispensables à son fonctionnement</strong> (session et sécurité des formulaires). Aucun cookie publicitaire ni de mesure d'audience.
        </p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <button type="button" class="btn btn-dark" data-cookie-dismiss>J'ai compris</button>
            <a href="{{ route('site.cookies') }}" class="text-sm font-medium text-brand-700 underline underline-offset-2">En savoir plus</a>
        </div>
    </div>
</body>
</html>
