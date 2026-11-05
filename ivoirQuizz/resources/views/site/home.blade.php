@extends('site.layout')

@section('content')
    {{-- Accroche --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-32 -right-24 size-[28rem] rounded-full bg-brand-200/60 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute top-64 -left-32 size-[22rem] rounded-full bg-ci-100/70 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-4 pt-14 pb-20 sm:px-6 md:pt-20 lg:grid-cols-[1.15fr_1fr] lg:gap-10">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold tracking-wide text-stone-700 ring-1 ring-stone-200">
                    <span class="flex h-2.5 w-4 overflow-hidden rounded-[2px]" aria-hidden="true"><span class="w-1/3 bg-brand-500"></span><span class="w-1/3 bg-white ring-1 ring-stone-200 ring-inset"></span><span class="w-1/3 bg-ci-500"></span></span>
                    Le quiz 100 % Côte d'Ivoire
                </p>
                <h1 class="mt-6 font-display text-[2.6rem] leading-[1.05] font-extrabold tracking-tight text-ink sm:text-6xl">
                    Voyagez à travers la Côte d'Ivoire, <span class="text-brand-500">une question</span> à la fois.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-stone-600">
                    Choisissez une région, jouez les niveaux de chaque ville, battez le boss régional et remplissez votre passeport.
                    Histoire, géographie, personnalités, monuments : jusqu'où ira votre culture ivoirienne ?
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-4">
                    @include('site.partials.play-button')
                    <a href="#jeu" class="btn btn-ghost py-3">Découvrir le jeu</a>
                </div>
            </div>

            {{-- Aperçu de l'application --}}
            <div class="relative mx-auto w-full max-w-[19rem]" aria-hidden="true">
                <div class="absolute -top-5 -left-10 z-10 hidden rotate-[-8deg] rounded-2xl bg-white px-4 py-3 shadow-xl ring-1 ring-stone-200 sm:block">
                    <p class="text-[11px] font-semibold tracking-wide text-stone-500 uppercase">Passeport</p>
                    <p class="mt-0.5 font-display text-lg font-bold text-ink">Abidjan ✓</p>
                </div>
                <div class="absolute -right-8 bottom-20 z-10 hidden rotate-[6deg] rounded-2xl bg-ci-600 px-4 py-3 text-white shadow-xl sm:block">
                    <p class="text-[11px] font-semibold tracking-wide text-ci-100 uppercase">Bonne réponse</p>
                    <p class="mt-0.5 font-display text-lg font-bold">+20 XP</p>
                </div>

                <div class="rounded-[2.75rem] bg-ink p-3 shadow-2xl shadow-stone-900/30">
                    <div class="overflow-hidden rounded-[2.1rem] bg-sand">
                        <div class="bg-brand-500 px-5 pt-6 pb-5 text-white">
                            <div class="flex items-center justify-between text-xs font-semibold">
                                <span class="rounded-full bg-white/20 px-2.5 py-1">♥ 5</span>
                                <span class="rounded-full bg-white/20 px-2.5 py-1">🔥 7 jours</span>
                            </div>
                            <p class="mt-5 text-xs font-medium text-brand-100">Lagunes · Abidjan · Cocody</p>
                            <p class="font-display text-xl font-bold">Niveau 2</p>
                            <div class="mt-3 h-2 rounded-full bg-white/25">
                                <div class="h-2 w-[30%] rounded-full bg-white"></div>
                            </div>
                            <p class="mt-1.5 text-right text-[11px] text-brand-100">Question 3 / 10</p>
                        </div>
                        <div class="space-y-3 p-5">
                            <p class="rounded-2xl bg-white p-4 text-[15px] leading-snug font-semibold text-ink shadow-sm">
                                Quelle ville est surnommée « la Perle des Lagunes » ?
                            </p>
                            <div class="flex items-center justify-between rounded-xl bg-ci-500 px-4 py-3 text-sm font-semibold text-white">
                                Abidjan <span>✓</span>
                            </div>
                            <div class="rounded-xl bg-white px-4 py-3 text-sm font-medium text-stone-600 ring-1 ring-stone-200">Bouaké</div>
                            <div class="rounded-xl bg-white px-4 py-3 text-sm font-medium text-stone-600 ring-1 ring-stone-200">Yamoussoukro</div>
                            <div class="rounded-xl bg-white px-4 py-3 text-sm font-medium text-stone-600 ring-1 ring-stone-200">San-Pédro</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Boucle de jeu --}}
    <section id="jeu" class="scroll-mt-20 bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold tracking-widest text-brand-600 uppercase">Comment ça marche</p>
                <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Un pays entier à explorer, région après région.</h2>
            </div>

            <ol class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Choisissez une région', "Chaque région est un monde de jeu, avec ses villes et ses communes à débloquer dans l'ordre."],
                    ['Jouez les niveaux', 'Répondez aux questions de chaque niveau. Atteignez le score minimum pour gagner XP et pièces et débloquer la suite.'],
                    ['Battez le boss', "Le niveau boss valide la région : un tampon dans votre passeport, un coffre, et la région suivante s'ouvre."],
                ] as $i => [$title, $text])
                    <li class="relative rounded-3xl bg-sand p-7">
                        <span class="font-display text-5xl font-extrabold text-brand-200">{{ $i + 1 }}</span>
                        <h3 class="mt-3 text-lg font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-stone-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>

            <p class="mt-8 flex flex-wrap items-center gap-2 text-sm font-semibold text-stone-600">
                @foreach (['Abidjan', 'Cocody', 'Niveau 1', 'Niveau 2', 'Boss Abidjan', 'Passeport ✓'] as $step)
                    @unless ($loop->first)<span class="text-stone-300" aria-hidden="true">→</span>@endunless
                    <span class="rounded-full px-3 py-1 {{ $loop->last ? 'bg-ci-500 text-white' : 'bg-sand ring-1 ring-stone-200' }}">{{ $step }}</span>
                @endforeach
            </p>
        </div>
    </section>

    {{-- Fonctionnalités --}}
    <section class="py-20 sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold tracking-widest text-ci-600 uppercase">Et bien plus</p>
                <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">De quoi revenir jouer chaque jour.</h2>
            </div>

            <div class="mt-12 grid gap-px overflow-hidden rounded-3xl bg-stone-200 ring-1 ring-stone-200 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['trophy', 'Ligues', "Chaque saison, grimpez dans le classement de votre ligue grâce à l'XP gagnée en jouant."],
                    ['users', 'Défis entre amis', 'Ajoutez vos amis avec leur code et défiez-les sur les mêmes dix questions.'],
                    ['bolt', 'Série quotidienne', "Jouez chaque jour pour faire grandir votre série. Une protection peut sauver un jour manqué."],
                    ['gift', 'Coffres', 'Un boss vaincu ou une partie très réussie peut vous rapporter un coffre à ouvrir.'],
                    ['sparkles', 'Collection', 'Débloquez les cartes des personnalités et des monuments qui ont fait la Côte d\'Ivoire.'],
                    ['map', 'Passeport', 'Chaque région terminée ajoute un tampon. Combien en aurez-vous ?'],
                ] as [$icon, $title, $text])
                    <div class="bg-white p-7">
                        <span class="inline-flex size-11 items-center justify-center rounded-xl {{ $loop->even ? 'bg-ci-50 text-ci-600' : 'bg-brand-50 text-brand-600' }}">
                            <x-admin.icon :name="$icon" class="size-6" />
                        </span>
                        <h3 class="mt-4 text-lg font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-1.5 leading-relaxed text-stone-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Téléchargement --}}
    <section id="telecharger" class="scroll-mt-20 px-4 sm:px-6">
        <div class="relative mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-brand-500 px-6 py-14 text-white sm:px-14">
            <div class="pointer-events-none absolute -right-20 -bottom-24 size-80 rounded-full bg-ci-500/80 blur-2xl" aria-hidden="true"></div>
            <div class="relative max-w-xl">
                <h2 class="font-display text-3xl font-extrabold tracking-tight sm:text-4xl">Prêt à prendre la route ?</h2>
                <p class="mt-4 text-lg text-brand-50">Installez {{ config('app.name') }} sur votre téléphone Android et commencez par la première région.</p>
                <div class="mt-8">
                    @include('site.partials.play-button')
                </div>
            </div>
        </div>
    </section>

    {{-- Questions fréquentes --}}
    <section id="faq" class="scroll-mt-20 pt-24">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Questions fréquentes</h2>

            <div class="mt-8 divide-y divide-stone-200 border-y border-stone-200">
                @foreach ([
                    ['Faut-il créer un compte ?', "Oui. Un compte, créé avec votre e-mail ou votre compte Google, sauvegarde votre progression, vos récompenses et vos amis d'un appareil à l'autre."],
                    ['Comment fonctionnent les vies ?', 'Chaque partie utilise une vie. Les vies se rechargent automatiquement avec le temps : revenez un peu plus tard pour rejouer.'],
                    ["J'ai repéré une erreur dans une question.", "Merci ! Signalez-la directement depuis l'écran de la question dans l'application. Notre équipe vérifie chaque signalement."],
                    ['Mes données sont-elles vendues ?', "Non. Nous ne vendons ni ne louons vos données personnelles. Tout est détaillé dans notre <a href=\"".route('site.privacy')."\">politique de confidentialité</a>."],
                    ['Comment supprimer mon compte ?', "Depuis l'application, ou sans l'application depuis <a href=\"".route('site.account-deletion')."\">notre page de suppression de compte</a>. La suppression est immédiate et définitive."],
                ] as [$question, $answer])
                    <details class="group py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold text-ink [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white text-stone-500 ring-1 ring-stone-200 transition group-open:rotate-45" aria-hidden="true">
                                <x-admin.icon name="plus" class="size-4" />
                            </span>
                        </summary>
                        <p class="legal mt-3 pr-12">{!! $answer !!}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endsection
