<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion · IvoireQuizz Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-900 px-4 py-12 font-sans antialiased">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-xl bg-brand-500 text-lg font-bold text-white">IQ</span>
            <h1 class="mt-4 text-xl font-bold text-white">IvoireQuizz Admin</h1>
            <p class="mt-1 text-sm text-slate-400">Espace réservé aux administrateurs</p>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex h-1">
                <span class="flex-1 bg-brand-500"></span><span class="flex-1 bg-white"></span><span class="flex-1 bg-ci-500"></span>
            </div>
            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5 p-6 sm:p-8">
                @csrf
                <x-admin.input name="email" label="Email" type="email" autocomplete="username" autofocus required />
                <x-admin.input name="password" label="Mot de passe" type="password" autocomplete="current-password" required />
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="size-4 accent-brand-500">
                    Rester connecté
                </label>
                <button type="submit" class="btn btn-primary w-full py-2.5">Se connecter</button>
            </form>
        </div>
    </div>
</body>
</html>
