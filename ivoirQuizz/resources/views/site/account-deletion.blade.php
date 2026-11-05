@extends('site.layout')

@section('title', 'Supprimer mon compte')
@section('description', 'Supprimez votre compte '.config('app.name').' et les données associées, depuis l\'application ou depuis ce site.')

@section('content')
    @php($app = config('app.name'))

    <div class="mx-auto max-w-3xl px-4 pt-12 sm:px-6 sm:pt-16">
        <p class="text-sm font-semibold tracking-widest text-brand-600 uppercase">Votre compte</p>
        <h1 class="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">Supprimer votre compte {{ $app }}</h1>
        <p class="mt-5 text-lg leading-relaxed text-stone-600">
            Vous pouvez supprimer votre compte {{ $app }} et les données associées à tout moment, avec ou sans l'application.
            La suppression est <strong class="text-ink">immédiate et définitive</strong>.
        </p>

        @if (session('deleted'))
            <div class="mt-10 rounded-3xl bg-ci-50 p-6 ring-1 ring-ci-100 sm:p-8" role="status">
                <div class="flex items-start gap-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-ci-500 text-white">
                        <x-admin.icon name="check" class="size-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-ink">Votre compte a été supprimé.</h2>
                        <p class="mt-1.5 leading-relaxed text-stone-700">
                            Vos informations personnelles ont été effacées et toutes vos sessions ont été déconnectées.
                            Vous pouvez désinstaller l'application. Merci d'avoir joué à {{ $app }} !
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="mt-10 grid gap-5 md:grid-cols-2">
                <section class="rounded-3xl bg-white p-6 ring-1 ring-stone-200 sm:p-7">
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <x-admin.icon name="play" class="size-5" />
                    </span>
                    <h2 class="mt-4 text-lg font-bold text-ink">Depuis l'application</h2>
                    <ol class="mt-3 list-decimal space-y-1.5 pl-5 leading-relaxed text-stone-600 marker:font-semibold marker:text-brand-500">
                        <li>Ouvrez {{ $app }} et connectez-vous.</li>
                        <li>Allez dans votre profil.</li>
                        <li>Choisissez <strong class="text-ink">Supprimer mon compte</strong>, saisissez votre mot de passe et confirmez.</li>
                    </ol>
                </section>

                <section class="rounded-3xl bg-white p-6 ring-1 ring-stone-200 sm:p-7">
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-ci-50 text-ci-600">
                        <x-admin.icon name="shield" class="size-5" />
                    </span>
                    <h2 class="mt-4 text-lg font-bold text-ink">Sans l'application</h2>
                    <p class="mt-3 leading-relaxed text-stone-600">
                        Remplissez le formulaire ci-dessous avec l'e-mail et le mot de passe de votre compte.
                    </p>
                    <p class="mt-3 text-sm leading-relaxed text-stone-500 [&_a]:font-medium [&_a]:text-brand-700 [&_a]:underline [&_a]:underline-offset-2">
                        Inscrit avec Google ou mot de passe oublié ? Utilisez « Mot de passe oublié » dans l'application pour en définir un,
                        ou écrivez-nous à @include('site.partials.contact') depuis l'adresse e-mail de votre compte.
                    </p>
                </section>
            </div>

            <form method="POST" action="{{ route('site.account-deletion.store') }}" class="mt-5 rounded-3xl bg-white p-6 ring-1 ring-stone-200 sm:p-8" novalidate>
                @csrf
                <h2 class="text-lg font-bold text-ink">Supprimer mon compte maintenant</h2>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="email" class="form-label">E-mail du compte</label>
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="form-input" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        @error('email')<p id="email-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="form-label">Mot de passe</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="form-input" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        @error('password')<p id="password-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5">
                    <label for="confirmation" class="form-label">Pour confirmer, tapez <span class="font-mono text-red-700">SUPPRIMER</span></label>
                    <input id="confirmation" name="confirmation" type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" required value="{{ old('confirmation') }}" class="form-input sm:max-w-xs" @error('confirmation') aria-invalid="true" aria-describedby="confirmation-error" @enderror>
                    @error('confirmation')<p id="confirmation-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="mt-7 flex flex-wrap items-center gap-4 border-t border-stone-200 pt-6">
                    <button type="submit" class="btn btn-danger">
                        <x-admin.icon name="trash" class="size-4" />
                        Supprimer définitivement mon compte
                    </button>
                    <p class="text-sm text-stone-500">Cette action est irréversible.</p>
                </div>
            </form>
        @endif

        <div class="legal mt-14">
            <h2>Ce qui est supprimé</h2>
            <ul>
                <li>Vos informations de compte et de profil : nom, pseudo, adresse e-mail, numéro de téléphone, mot de passe, identifiant Google, avatar, ville et biographie.</li>
                <li>Vos sessions de connexion, sur tous vos appareils.</li>
                <li>Les jetons de notification de vos appareils : vous ne recevrez plus de notifications.</li>
            </ul>

            <h2>Ce qui est conservé, sous forme anonyme</h2>
            <p>
                L'historique des parties, les scores et les récompenses ne sont plus rattachés à votre identité : ils restent associés à un compte anonyme
                (« Compte supprimé ») pour préserver la cohérence des classements et des statistiques du jeu. Ils ne permettent plus de vous identifier.
            </p>
            <p>
                Les journaux techniques des serveurs sont conservés 12 mois au maximum.
                Pour en savoir plus, consultez notre <a href="{{ route('site.privacy') }}">politique de confidentialité</a>.
            </p>
        </div>
    </div>
@endsection
