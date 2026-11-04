@php
    $navigation = [
        'Pilotage' => [
            ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'home'],
        ],
        'Joueurs' => [
            ['label' => 'Joueurs', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users'],
            ['label' => 'Parties', 'route' => 'admin.sessions.index', 'match' => 'admin.sessions.*', 'icon' => 'play'],
            ['label' => 'Notifications', 'route' => 'admin.notifications.index', 'match' => 'admin.notifications.*', 'icon' => 'bell'],
        ],
        'Contenu' => [
            ['label' => 'Régions', 'route' => 'admin.regions.index', 'match' => 'admin.regions.*', 'icon' => 'map'],
            ['label' => 'Villes', 'route' => 'admin.cities.index', 'match' => 'admin.cities.*', 'icon' => 'building'],
            ['label' => 'Niveaux', 'route' => 'admin.levels.index', 'match' => 'admin.levels.*', 'icon' => 'stairs'],
            ['label' => 'Catégories', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'icon' => 'tag'],
            ['label' => 'Questions', 'route' => 'admin.questions.index', 'match' => 'admin.questions.*', 'icon' => 'question'],
            ['label' => 'Signalements', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'icon' => 'flag', 'badge' => $pendingReportsCount ?? 0],
        ],
        'Récompenses' => [
            ['label' => 'Coffres', 'route' => 'admin.chests.index', 'match' => 'admin.chests.*', 'icon' => 'gift'],
            ['label' => 'Collection', 'route' => 'admin.collectibles.index', 'match' => 'admin.collectibles.*', 'icon' => 'sparkles'],
            ['label' => 'Ligues', 'route' => 'admin.leagues.index', 'match' => 'admin.leagues.*', 'icon' => 'trophy'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Administration') · IvoireQuizz Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased">
    <div id="admin-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden" data-sidebar-toggle></div>

    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 transition-transform duration-200 lg:translate-x-0">
        <div class="flex h-16 shrink-0 items-center justify-between gap-3 px-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <span class="grid size-8 place-items-center rounded-lg bg-brand-500 text-sm font-bold text-white">IQ</span>
                <span class="text-sm font-semibold text-white">IvoireQuizz <span class="font-normal text-slate-400">Admin</span></span>
            </a>
            <button type="button" class="rounded-md p-1 text-slate-400 hover:text-white lg:hidden" data-sidebar-toggle aria-label="Fermer le menu">
                <x-admin.icon name="x" class="size-5" />
            </button>
        </div>
        <div class="flex h-1 shrink-0">
            <span class="flex-1 bg-brand-500"></span><span class="flex-1 bg-white"></span><span class="flex-1 bg-ci-500"></span>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
            @foreach ($navigation as $section => $items)
                <div>
                    <p class="px-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{{ $section }}</p>
                    <ul class="mt-2 space-y-0.5">
                        @foreach ($items as $item)
                            <li>
                                <a href="{{ route($item['route']) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($item['match'])])>
                                    <x-admin.icon :name="$item['icon']" class="size-5 shrink-0" />
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if (($item['badge'] ?? 0) > 0)
                                        <span class="rounded-full bg-brand-500 px-2 py-0.5 text-[11px] font-semibold text-white">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-700 text-sm font-semibold text-white">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md p-1.5 text-slate-400 hover:bg-white/5 hover:text-white" title="Se déconnecter">
                        <x-admin.icon name="logout" class="size-5" />
                        <span class="sr-only">Se déconnecter</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:hidden">
            <button type="button" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100" data-sidebar-toggle aria-label="Ouvrir le menu">
                <x-admin.icon name="menu" class="size-6" />
            </button>
            <span class="text-sm font-semibold text-slate-900">IvoireQuizz Admin</span>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @if (session('success'))
                <div class="mb-5 flex items-start gap-3 rounded-lg bg-ci-50 px-4 py-3 text-sm text-ci-600 ring-1 ring-ci-500/20" role="status">
                    <x-admin.icon name="check" class="size-5 shrink-0" />
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 flex items-start gap-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-600/20" role="alert">
                    <x-admin.icon name="alert" class="size-5 shrink-0" />
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-600/20" role="alert">
                    <p class="font-semibold">Le formulaire contient {{ $errors->count() }} erreur(s).</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach (array_slice($errors->all(), 0, 5) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
