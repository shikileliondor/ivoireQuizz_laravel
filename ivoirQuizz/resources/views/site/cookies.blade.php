@extends('site.legal-layout')

@section('title', 'Politique cookies')
@section('description', 'Les cookies utilisés par le site '.config('app.name').' : uniquement des cookies indispensables, sans publicité ni mesure d\'audience.')
@section('heading', 'Politique cookies')

@section('body')
    @php
        $lifetime = (int) config('session.lifetime');
        $duration = $lifetime % 60 === 0 ? ($lifetime / 60).' heure(s)' : $lifetime.' minutes';
    @endphp

    <p>
        Un cookie est un petit fichier déposé par un site dans votre navigateur. Cette page liste ceux que dépose le site
        {{ config('app.name') }} et explique comment les gérer.
    </p>

    <h2>En résumé</h2>
    <ul>
        <li>Le site n'utilise <strong>que des cookies indispensables</strong> à son fonctionnement et à sa sécurité.</li>
        <li>Aucun cookie publicitaire, aucun cookie de mesure d'audience, aucun cookie de réseau social.</li>
        <li>Ces cookies étant indispensables, la loi ne requiert pas votre consentement préalable ; le bandeau affiché lors de votre première visite sert uniquement à vous en informer.</li>
        <li>L'application mobile n'utilise pas de cookies.</li>
    </ul>

    <h2>Les cookies déposés par le site</h2>
    <table>
        <thead>
            <tr><th>Nom</th><th>Rôle</th><th>Durée</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><code>{{ config('session.cookie') }}</code></td>
                <td>Maintient votre session pendant votre visite, par exemple le temps de remplir le formulaire de suppression de compte.</td>
                <td>{{ $duration }}</td>
            </tr>
            <tr>
                <td><code>XSRF-TOKEN</code></td>
                <td>Protège les formulaires contre les envois frauduleux depuis un autre site (attaque CSRF).</td>
                <td>{{ $duration }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Autres informations enregistrées dans votre navigateur</h2>
    <p>
        Lorsque vous fermez le bandeau d'information, le site retient ce choix dans le stockage local de votre navigateur
        (clé <code>ivoirquizz-cookie-notice</code>) pour ne plus l'afficher. Cette information reste sur votre appareil et ne nous est jamais transmise.
    </p>

    <h2>Services tiers</h2>
    <p>
        Les polices de caractères du site sont chargées depuis <a href="https://bunny.net/fonts/" target="_blank" rel="noopener">Bunny Fonts</a>,
        un service européen qui ne dépose aucun cookie. Le site n'intègre aucun autre service tiers.
    </p>

    <h2>Gérer les cookies</h2>
    <p>
        Vous pouvez à tout moment supprimer ou bloquer les cookies depuis les réglages de votre navigateur. Bloquer les cookies indispensables
        empêchera toutefois l'envoi du formulaire de suppression de compte.
    </p>
    <p>
        Pour en savoir plus sur le traitement de vos données, consultez notre <a href="{{ route('site.privacy') }}">politique de confidentialité</a>.
    </p>
@endsection
