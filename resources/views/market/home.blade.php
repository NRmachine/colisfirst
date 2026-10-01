@extends('layouts.market')
@section('title', 'Livraison de colis France – Maghreb')
@section('content')
<section class="home-intro">
    <div class="wrap intro-grid">
        <div class="intro-copy">
            <p class="kicker">FRANCE / ALGÉRIE / MAROC / TUNISIE</p>
            <h1>Un colis à envoyer<br>au pays ?</h1>
            <p class="intro-text">Trouvez un livreur sur votre trajet.<br> Comparez les départs, les points de ramassage<br> et les prix au kilo.</p>
            <a class="intro-link" href="{{ route('how') }}">Comment fonctionne ColisFirst ? <span></span></a>
        </div>
        <form class="departure-search" action="{{ route('trips.index') }}">
            <div class="search-title"><h2>Où va votre colis ?</h2></div>
            <label>Ville de départ en France
                <input name="origin" placeholder="Ex. Paris, Lyon, Marseille" autocomplete="address-level2">
            </label>
            <label>Pays de destination
                <select name="country">
                    <option value="">Choisir un pays</option>
                    <option>Algérie</option>
                    <option>Maroc</option>
                    <option>Tunisie</option>
                </select>
            </label>
            <label>Départ à partir du
                <input type="date" name="date" min="{{ date('Y-m-d') }}">
            </label>
            <button class="btn full">Rechercher un départ <span></span></button>
            <p class="search-help">Consultez les annonces sans créer de compte.</p>
        </form>
    </div>
</section>
<div class="destination-strip">
    <div class="wrap destination-links">
        <span>LES DESTINATIONS</span>
        <a href="{{ route('trips.index', ['country'=>'Algérie']) }}">Algérie <span></span></a>
        <a href="{{ route('trips.index', ['country'=>'Maroc']) }}">Maroc <span></span></a>
        <a href="{{ route('trips.index', ['country'=>'Tunisie']) }}">Tunisie <span></span></a>
    </div>
</div>
<section class="wrap section departures-section">
    <div class="section-heading">
        <div><h2>Les prochains départs</h2></div>
        <a class="link" href="{{ route('trips.index') }}">Tous les trajets </a>
    </div>
    <div class="departure-head" aria-hidden="true"><span>Trajet</span><span>Ramassage</span><span>Livreur</span><span>Prix au kilo</span></div>
    <div class="cards">
        @forelse($trips as $trip)
            @include('market.partials.trip-card')
        @empty
            <div class="empty">Aucun départ disponible pour le moment.</div>
        @endforelse
    </div>
    <p class="list-footnote">Les prix affichés sont ceux des livreurs. {{ config('marketplace.commission_percent') }} % de frais de service s’ajoutent lors de la réservation.</p>
</section>
<section class="wrap section human-section">
    <figure><img src="{{ asset('images/remise-colis.png') }}" alt="Illustration d’une remise de colis entre une expéditrice et un livreur" width="1536" height="1024" loading="lazy"></figure>
    <div><h2>Un rendez-vous clair.<br>Un colis bien préparé.</h2><p>Vérifiez les conditions du trajet, déclarez le contenu et convenez du ramassage avec le livreur depuis votre réservation.</p><a class="link" href="{{ route('trust') }}">Consulter les règles d’envoi</a></div>
</section>
<section class="how-section">
    <div class="wrap how-grid">
        <div><h2>Du ramassage<br>à la remise du colis.</h2><p>Chaque demande se suit depuis votre compte.</p><a class="link" href="{{ route('trust') }}">Quels objets peut-on envoyer ? </a></div>
        <ol class="process-list">
            <li><span>1</span><div><h3>Choisissez votre départ</h3><p>Vérifiez le lieu de ramassage, la date et les conditions du livreur.</p></div></li>
            <li><span>2</span><div><h3>Décrivez votre colis</h3><p>Indiquez le contenu, le poids et le destinataire. Le livreur examine votre demande.</p></div></li>
            <li><span>3</span><div><h3>Retrouvez le suivi dans votre espace</h3><p>Après confirmation, échangez avec le livreur et confirmez la réception à l’arrivée.</p></div></li>
        </ol>
    </div>
</section>
<section class="wrap section">
    <div class="driver-banner">
        <div><h2>Vous faites régulièrement le trajet ?</h2><p>Proposez vos départs et fixez votre tarif au kilo.<br>Une candidature est nécessaire avant de publier une annonce.</p></div>
        <a class="btn" href="{{ route('drivers.apply') }}">Déposer ma candidature </a>
    </div>
</section>
@endsection
