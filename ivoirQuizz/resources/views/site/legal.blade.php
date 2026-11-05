@extends('site.legal-layout')

@section('title', 'Mentions légales')
@section('description', 'Éditeur, hébergeur et crédits du site et de l\'application '.config('app.name').'.')
@section('heading', 'Mentions légales')

@section('body')
    @php
        $publisher = config('site.publisher');
        $host = config('site.host');
    @endphp

    <h2>Éditeur</h2>
    <p>
        Le site et l'application {{ config('app.name') }} sont édités par <strong>{{ $publisher['name'] }}</strong>.
    </p>
    <ul>
        @if ($publisher['legal_form'])
            <li>Forme juridique : {{ $publisher['legal_form'] }}</li>
        @endif
        @if ($publisher['registration'])
            <li>Immatriculation : {{ $publisher['registration'] }}</li>
        @endif
        @if ($publisher['address'])
            <li>Adresse : {{ $publisher['address'] }}</li>
        @endif
        <li>Contact : @include('site.partials.contact')</li>
        @if ($publisher['director'])
            <li>Directeur ou directrice de la publication : {{ $publisher['director'] }}</li>
        @endif
    </ul>

    <h2>Hébergeur</h2>
    <p>
        <strong>{{ $host['name'] }}</strong><br>
        {{ $host['address'] }}
        @if ($host['website'])
            <br><a href="{{ $host['website'] }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', $host['website']) }}</a>
        @endif
    </p>

    <h2>Propriété intellectuelle</h2>
    <p>
        Les textes, questions, éléments graphiques, logos et la marque {{ config('app.name') }} sont la propriété de leur éditeur, sauf mention contraire.
        Toute reproduction sans autorisation est interdite.
    </p>

    <h2 id="marques">Marques et logos</h2>
    <p>
        La catégorie « Logos » du jeu présente des logos de marques. Ces marques et logos appartiennent à leurs propriétaires respectifs.
        Ils sont reproduits uniquement pour permettre aux joueurs de les reconnaître, sans aucune affiliation, partenariat ni approbation de leur part.
    </p>
    <p>
        Si vous représentez une marque et souhaitez que son logo soit retiré du jeu, écrivez-nous à @include('site.partials.contact') :
        il sera retiré de l'application sans attendre de mise à jour.
    </p>

    <h2 id="credits">Crédits photographiques</h2>
    <p>Certaines photographies de villes proviennent de Wikimedia Commons et sont utilisées selon leur licence :</p>
    <table>
        <thead>
            <tr><th>Ville</th><th>Auteur et source</th><th>Licence</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Yopougon</td>
                <td>Belysee65, <a href="https://commons.wikimedia.org/wiki/File:Mairie_centrale_de_Yopougon_Abidjan.jpg" target="_blank" rel="noopener">Mairie centrale de Yopougon Abidjan</a></td>
                <td><a href="https://creativecommons.org/licenses/by-sa/4.0/deed.fr" target="_blank" rel="noopener">CC BY-SA 4.0</a>, redimensionnée</td>
            </tr>
            <tr>
                <td>Korhogo</td>
                <td>BONI LINDSAY, <a href="https://commons.wikimedia.org/wiki/File:La_grande_mosqu%C3%A9e_de_Korhogo.jpg" target="_blank" rel="noopener">La grande mosquée de Korhogo</a></td>
                <td><a href="https://creativecommons.org/licenses/by-sa/4.0/deed.fr" target="_blank" rel="noopener">CC BY-SA 4.0</a>, redimensionnée</td>
            </tr>
            <tr>
                <td>Aboisso</td>
                <td>François-Edmond Fortier, <a href="https://commons.wikimedia.org/wiki/File:Aboisso-D%C3%A9barcad%C3%A8re_(C%C3%B4te_d%27Ivoire).jpg" target="_blank" rel="noopener">Aboisso. Débarcadère</a>, vers 1906–1910</td>
                <td>Domaine public</td>
            </tr>
        </tbody>
    </table>

    <h2>Données personnelles et cookies</h2>
    <p>
        Consultez notre <a href="{{ route('site.privacy') }}">politique de confidentialité</a> et notre <a href="{{ route('site.cookies') }}">politique cookies</a>.
    </p>
@endsection
