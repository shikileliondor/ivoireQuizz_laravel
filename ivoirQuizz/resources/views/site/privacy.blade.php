@extends('site.legal-layout')

@section('title', 'Politique de confidentialité')
@section('description', "Quelles données l'application ".config('app.name')." collecte, pourquoi, combien de temps elles sont conservées et comment exercer vos droits.")
@section('heading', 'Politique de confidentialité')

@section('body')
    @php
        $app = config('app.name');
        $publisher = config('site.publisher');
        $host = config('site.host');
        $publisherDetails = collect([$publisher['legal_form'], $publisher['address']])->filter()->implode(', ');
    @endphp

    <p>
        Cette politique explique quelles données personnelles l'application mobile <strong>{{ $app }}</strong> et le site
        <a href="{{ route('site.home') }}">{{ request()->getHost() }}</a> collectent,
        pourquoi, avec qui elles sont partagées, combien de temps elles sont conservées et comment exercer vos droits.
        Elle s'applique conformément à la loi ivoirienne n° 2013-450 du 19 juin 2013 relative à la protection des données
        à caractère personnel et, pour les joueurs résidant dans l'Union européenne, au Règlement général sur la protection des données (RGPD).
    </p>

    <h2 id="responsable">1. Qui est responsable de vos données ?</h2>
    <p>
        Le responsable du traitement est <strong>{{ $publisher['name'] }}</strong>{{ $publisherDetails ? ', '.$publisherDetails : '' }}.
        Pour toute question sur vos données, écrivez-nous à @include('site.partials.contact').
    </p>

    <h2 id="donnees">2. Les données que nous collectons</h2>

    <h3>Lorsque vous créez un compte</h3>
    <ul>
        <li>votre <strong>nom</strong> (affiché dans le jeu) et votre <strong>pseudo</strong> ;</li>
        <li>votre <strong>adresse e-mail</strong> ;</li>
        <li>votre <strong>mot de passe</strong>, que nous stockons uniquement sous forme hachée : personne, pas même notre équipe, ne peut le lire ;</li>
        <li>facultativement, votre <strong>numéro de téléphone</strong>.</li>
    </ul>

    <h3>Si vous vous connectez avec Google</h3>
    <p>
        Nous recevons de Google votre identifiant de compte Google, votre nom et votre adresse e-mail vérifiée.
        Nous n'avons accès à aucune autre donnée de votre compte Google (contacts, fichiers, agenda…).
    </p>

    <h3>Lorsque vous complétez votre profil</h3>
    <p>Facultativement : votre ville, une courte biographie et l'avatar que vous choisissez.</p>

    <h3>Lorsque vous jouez</h3>
    <ul>
        <li>votre progression : régions, villes et niveaux débloqués ou terminés, passeport ;</li>
        <li>vos parties : questions posées, réponses choisies, temps de réponse, scores ;</li>
        <li>vos récompenses et ressources de jeu : points d'expérience, pièces, gemmes, vies, coffres, collection, série de jours joués ;</li>
        <li>votre participation aux ligues et vos classements ;</li>
        <li>vos dates de dernière connexion et de dernière activité.</li>
    </ul>

    <h3>Lorsque vous jouez avec d'autres joueurs</h3>
    <p>Votre code ami, votre liste d'amis, les demandes d'amis envoyées ou reçues et les défis entre amis.</p>

    <h3>Lorsque vous signalez une question</h3>
    <p>Le motif et le commentaire de votre signalement, rattachés à votre compte.</p>

    <h3>Pour les notifications</h3>
    <p>
        Si vous autorisez les notifications, nous enregistrons le jeton technique de votre appareil, fourni par Firebase Cloud Messaging,
        et le type d'appareil (Android). Il sert uniquement à vous envoyer les notifications du jeu.
    </p>

    <h3>Données techniques</h3>
    <p>
        Comme tout service en ligne, nos serveurs enregistrent des journaux techniques (adresse IP, date et heure, page ou fonction appelée)
        pour assurer la sécurité du service et limiter les abus. Lorsqu'une erreur se produit, un rapport technique est envoyé à notre outil de suivi
        des erreurs ; nous le configurons pour qu'il n'inclue ni votre adresse IP ni vos informations de compte.
    </p>

    <h3>Ce que nous ne collectons pas</h3>
    <p>
        Nous ne collectons pas votre position GPS, vos contacts, vos photos, ni aucune donnée bancaire.
        Nous n'utilisons pas vos données pour de la publicité ciblée.
    </p>

    <h2 id="finalites">3. Pourquoi nous utilisons vos données</h2>
    <table>
        <thead>
            <tr><th>Finalité</th><th>Base légale</th></tr>
        </thead>
        <tbody>
            <tr><td>Créer et gérer votre compte, vous authentifier, réinitialiser votre mot de passe</td><td>Exécution des conditions d'utilisation</td></tr>
            <tr><td>Faire fonctionner le jeu : progression, récompenses, vies, séries, ligues, classements</td><td>Exécution des conditions d'utilisation</td></tr>
            <tr><td>Fonctions sociales : amis, défis</td><td>Exécution des conditions d'utilisation</td></tr>
            <tr><td>Vous envoyer des notifications (rappels, vies rechargées, nouveautés)</td><td>Votre consentement, donné via l'autorisation Android et retirable à tout moment</td></tr>
            <tr><td>Sécuriser le service, détecter la triche et les abus</td><td>Intérêt légitime à protéger le jeu et ses joueurs</td></tr>
            <tr><td>Corriger les erreurs techniques et améliorer les questions grâce à vos signalements</td><td>Intérêt légitime à fournir un service fiable</td></tr>
            <tr><td>Répondre aux demandes des autorités</td><td>Obligation légale</td></tr>
        </tbody>
    </table>

    <h2 id="visibilite">4. Ce que voient les autres joueurs</h2>
    <p>
        Votre nom, votre pseudo, votre avatar et vos résultats de jeu (score, XP, ligue, classement) peuvent apparaître dans les classements,
        dans les ligues et auprès de vos amis. Votre adresse e-mail, votre numéro de téléphone et votre mot de passe ne sont jamais montrés aux autres joueurs.
    </p>

    <h2 id="destinataires">5. Avec qui nous partageons vos données</h2>
    <p>
        Nous ne vendons ni ne louons vos données. Elles sont accessibles à notre équipe, dans la limite de ce dont chacun a besoin,
        et à des prestataires techniques qui les traitent pour notre compte, selon nos instructions :
    </p>
    <ul>
        <li><strong>{{ $host['name'] }}</strong> ({{ $host['address'] }}) : hébergement des serveurs et de la base de données ;</li>
        <li><strong>Google</strong> (Firebase Cloud Messaging) : acheminement des notifications ; et, uniquement si vous l'utilisez, Google Sign-In pour la connexion ;</li>
        <li><strong>Sentry</strong> (Functional Software, Inc., États-Unis) : suivi des erreurs techniques ;</li>
        <li>notre prestataire d'envoi d'e-mails, pour les e-mails de réinitialisation de mot de passe.</li>
    </ul>
    <p>Nous pouvons aussi communiquer des données aux autorités lorsque la loi nous y oblige.</p>

    <h2 id="transferts">6. Transferts hors de Côte d'Ivoire</h2>
    <p>
        Nos serveurs sont hébergés en France. Google et Sentry peuvent traiter certaines données aux États-Unis.
        Ces transferts sont encadrés par les garanties contractuelles de ces prestataires (clauses contractuelles types de la Commission européenne).
    </p>

    <h2 id="conservation">7. Combien de temps nous conservons vos données</h2>
    <table>
        <thead>
            <tr><th>Données</th><th>Durée</th></tr>
        </thead>
        <tbody>
            <tr><td>Données de compte et de profil</td><td>Tant que votre compte existe. Effacées dès sa suppression.</td></tr>
            <tr><td>Historique de jeu, classements, récompenses</td><td>Tant que votre compte existe. Après sa suppression, conservés uniquement sous une forme anonyme, détachée de votre identité, pour la cohérence des classements et des statistiques.</td></tr>
            <tr><td>Jetons de connexion et de notification</td><td>Jusqu'à la déconnexion de l'appareil ou la suppression du compte.</td></tr>
            <tr><td>Lien de réinitialisation du mot de passe</td><td>{{ config('auth.passwords.users.expire', 60) }} minutes.</td></tr>
            <tr><td>Journaux techniques des serveurs</td><td>12 mois au maximum.</td></tr>
            <tr><td>Rapports d'erreurs</td><td>90 jours au maximum.</td></tr>
        </tbody>
    </table>

    <h2 id="suppression">8. Supprimer votre compte</h2>
    <p>
        Vous pouvez supprimer votre compte à tout moment depuis l'application, ou sans l'application depuis notre
        <a href="{{ route('site.account-deletion') }}">page de suppression de compte</a>. La suppression est immédiate :
    </p>
    <ul>
        <li>votre nom, pseudo, e-mail, téléphone, mot de passe, identifiant Google, avatar, ville et biographie sont effacés ;</li>
        <li>toutes vos sessions sont déconnectées et vos appareils ne reçoivent plus de notifications ;</li>
        <li>votre historique de jeu n'est conservé que sous forme anonyme, comme indiqué ci-dessus.</li>
    </ul>
    <p>Cette opération est définitive : votre progression et vos récompenses ne pourront pas être récupérées.</p>

    <h2 id="securite">9. Sécurité</h2>
    <p>
        Les échanges entre l'application et nos serveurs sont chiffrés (HTTPS). Les mots de passe sont hachés, les tentatives de connexion
        sont limitées et l'accès aux outils d'administration est réservé à un nombre restreint de personnes habilitées.
    </p>

    <h2 id="droits">10. Vos droits</h2>
    <p>Vous disposez des droits suivants sur vos données :</p>
    <ul>
        <li><strong>accès</strong> : obtenir une copie des données que nous détenons sur vous ;</li>
        <li><strong>rectification</strong> : la plupart de vos informations se modifient directement depuis votre profil dans l'application ;</li>
        <li><strong>suppression</strong> : voir la section 8 ;</li>
        <li><strong>opposition</strong> et <strong>limitation</strong> du traitement, dans les cas prévus par la loi ;</li>
        <li><strong>portabilité</strong> : recevoir vos données dans un format structuré ;</li>
        <li><strong>retrait du consentement</strong> aux notifications, à tout moment, dans les réglages Android de votre téléphone.</li>
    </ul>
    <p>
        Pour exercer ces droits, écrivez-nous à @include('site.partials.contact') depuis l'adresse e-mail de votre compte.
        Nous répondons dans un délai d'un mois.
    </p>
    <p>
        Si vous estimez que vos droits ne sont pas respectés, vous pouvez saisir l'Autorité de Régulation des Télécommunications/TIC
        de Côte d'Ivoire (<a href="https://www.artci.ci" target="_blank" rel="noopener">ARTCI</a>) ou, si vous résidez dans l'Union européenne,
        l'autorité de protection des données de votre pays (en France, la <a href="https://www.cnil.fr" target="_blank" rel="noopener">CNIL</a>).
    </p>

    <h2 id="enfants">11. Enfants</h2>
    <p>
        {{ $app }} s'adresse aux personnes âgées d'au moins {{ config('site.minimum_age') }} ans. Nous ne collectons pas sciemment de données concernant
        des enfants plus jeunes. Si vous pensez qu'un enfant de moins de {{ config('site.minimum_age') }} ans nous a transmis des données,
        contactez-nous : nous supprimerons son compte.
    </p>

    <h2 id="cookies">12. Cookies</h2>
    <p>
        L'application mobile n'utilise pas de cookies. Le site n'utilise que des cookies indispensables à son fonctionnement,
        détaillés dans notre <a href="{{ route('site.cookies') }}">politique cookies</a>.
    </p>

    <h2 id="modifications">13. Modifications de cette politique</h2>
    <p>
        Nous pouvons mettre à jour cette politique, par exemple lorsque nous ajoutons une fonctionnalité. La date de dernière mise à jour figure
        en haut de la page. En cas de changement important, nous vous en informerons dans l'application.
    </p>
@endsection
