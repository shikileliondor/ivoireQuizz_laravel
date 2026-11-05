@extends('site.legal-layout')

@section('title', "Conditions d'utilisation")
@section('description', "Les règles d'utilisation de l'application ".config('app.name').'.')
@section('heading', "Conditions d'utilisation")

@section('body')
    @php($app = config('app.name'))

    <h2>1. Objet</h2>
    <p>
        Les présentes conditions encadrent l'utilisation de l'application mobile {{ $app }} et de son site, édités par
        {{ config('site.publisher.name') }} (voir les <a href="{{ route('site.legal') }}">mentions légales</a>).
        En créant un compte ou en utilisant l'application, vous acceptez ces conditions.
    </p>

    <h2>2. Le service</h2>
    <p>
        {{ $app }} est un jeu de quiz sur la culture, l'histoire et la géographie de la Côte d'Ivoire. L'application est disponible sur Google Play
        et nécessite une connexion Internet. Nous pouvons faire évoluer le jeu (contenu, règles, récompenses, fonctionnalités) à tout moment
        pour l'améliorer.
    </p>

    <h2>3. Votre compte</h2>
    <ul>
        <li>Vous devez avoir au moins {{ config('site.minimum_age') }} ans pour créer un compte.</li>
        <li>Les informations que vous fournissez doivent être exactes. Vous êtes responsable de la confidentialité de votre mot de passe et de l'activité de votre compte.</li>
        <li>Votre compte est personnel : vous ne pouvez ni le vendre, ni le céder, ni le partager.</li>
        <li>Vous pouvez supprimer votre compte à tout moment, depuis l'application ou depuis <a href="{{ route('site.account-deletion') }}">cette page</a>.</li>
    </ul>

    <h2>4. Règles de fair-play</h2>
    <p>Pour que le jeu reste juste pour tous, il est interdit :</p>
    <ul>
        <li>d'utiliser des programmes automatisés, des scripts ou une application modifiée ;</li>
        <li>d'exploiter un bug ou une faille plutôt que de le signaler ;</li>
        <li>de manipuler les classements, les ligues ou les défis (comptes multiples, entente entre joueurs…) ;</li>
        <li>de choisir un nom, un pseudo ou une biographie injurieux, haineux, à caractère sexuel ou usurpant l'identité d'une autre personne ;</li>
        <li>de perturber le fonctionnement du service ou de tenter d'accéder aux données d'autres joueurs.</li>
    </ul>

    <h2>5. Ressources virtuelles</h2>
    <p>
        Les points d'expérience, pièces, gemmes, vies, coffres, objets de collection et tampons du passeport sont des éléments de jeu.
        Ils n'ont aucune valeur monétaire, ne peuvent être ni échangés, ni vendus, ni convertis en argent, et ne sont pas remboursables.
        Nous pouvons corriger un solde ou une progression obtenus par erreur ou en violation de ces conditions.
    </p>

    <h2>6. Contenu des questions</h2>
    <p>
        Nous apportons le plus grand soin à l'exactitude des questions, mais des erreurs restent possibles. Si vous en repérez une,
        signalez-la depuis l'application : chaque signalement est examiné.
    </p>

    <h2>7. Propriété intellectuelle</h2>
    <p>
        L'application, ses questions, ses textes, son design, ses illustrations et la marque {{ $app }} sont protégés. Vous bénéficiez d'un droit
        personnel et non commercial de les utiliser dans le cadre du jeu ; toute autre reproduction nécessite notre accord écrit.
        Certaines images sont utilisées sous licence libre : leurs auteurs sont crédités dans les <a href="{{ route('site.legal') }}#credits">mentions légales</a>.
    </p>

    <h2>8. Suspension et fermeture de compte</h2>
    <p>
        En cas de manquement à ces conditions, nous pouvons, selon la gravité, corriger votre progression, suspendre ou fermer votre compte.
        Vous pouvez nous contacter pour contester cette décision.
    </p>

    <h2>9. Responsabilité</h2>
    <p>
        Nous faisons notre possible pour que le service soit disponible et fonctionne correctement, sans pouvoir le garantir en permanence :
        des interruptions peuvent survenir pour maintenance ou en cas d'incident. Dans les limites permises par la loi, nous ne sommes pas
        responsables des dommages indirects liés à l'utilisation de l'application.
    </p>

    <h2>10. Données personnelles</h2>
    <p>
        Le traitement de vos données est décrit dans notre <a href="{{ route('site.privacy') }}">politique de confidentialité</a>.
    </p>

    <h2>11. Modification des conditions</h2>
    <p>
        Nous pouvons modifier ces conditions. En cas de changement important, nous vous en informerons dans l'application avant son entrée en vigueur.
        Continuer à utiliser l'application après cette date vaut acceptation des nouvelles conditions.
    </p>

    <h2>12. Droit applicable et contact</h2>
    <p>
        Ces conditions sont régies par le droit ivoirien. En cas de litige, nous vous invitons à nous contacter d'abord à
        @include('site.partials.contact') pour rechercher une solution amiable.
    </p>
@endsection
