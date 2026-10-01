@extends('layouts.market')
@section('title', $trip->origin.'  '.$trip->destination)
@section('content')
    <section class="wrap section">
        <a class="back" href="{{ route('trips.index') }}"> Tous les trajets</a>
        <div class="detail-grid">
            <div>
                <span class="pill">{{ $trip->country }}</span>
                <h1 class="page-title">{{ $trip->origin }} <span class="green"></span>
                    <br>{{ $trip->destination }}</h1>
                <div class="panel">
                    <div class="card-driver">
                        <span class="avatar large">{{ mb_substr($trip->driver->name,0,1) }}</span>
                        <div>
                            <h3>{{ Str::before($trip->driver->name,' ') }}</h3>
                            <span class="muted">Profil approuvé pour la démonstration</span>
                        </div>
                    </div>
                    <div class="info-grid">
                        <div>
                            <small>Départ</small>
                            <strong>{{ $trip->departure_date->format('d/m/Y') }}</strong>
                        </div>
                        <div>
                            <small>Arrivée prévue</small>
                            <strong>{{ $trip->arrival_date->format('d/m/Y') }}</strong>
                        </div>
                        <div>
                            <small>Place disponible</small>
                            <strong>{{ $trip->remainingKg() }} kg</strong>
                        </div>
                    </div>
                    <h3>Points de ramassage</h3>
                    <p class="preline">{{ $trip->pickup_points }}</p>
                    <h3>Conditions du livreur</h3>
                    <p class="preline">{{ $trip->conditions }}</p>
                </div>
                <div class="note">
                    <strong>Un envoi préparé, un trajet plus serein.</strong>
                    <p>Déclarez précisément le contenu et la valeur du colis. Les marchandises interdites ne sont jamais acceptées. Les formalités douanières doivent être vérifiées avant le transport.</p>
                    <a href="{{ route('trust') }}">Lire les règles d’envoi </a>
                </div>
            </div>
            <aside class="panel booking-form">
                <span class="eyebrow">VOTRE RÉSERVATION</span>
                <div class="big-price">{{ number_format($trip->price_cents/100,2,',',' ') }} € <small>/ kg</small>
                </div>
                <p class="muted">Frais de service : {{ config('marketplace.commission_percent') }} % du transport. Droits de douane éventuels non inclus.</p>
                @if($trip->departure_date->isPast() || $trip->remainingKg()===0)
                    <div class="note">Ce trajet n’est plus disponible à la réservation.</div>
                @elseif(auth()->guest())
                    <p>Créez un compte pour envoyer une demande au livreur.</p>
                    <a class="btn full" href="{{ route('register') }}">Créer mon compte</a>
                    <a class="center-link" href="{{ route('login') }}">J’ai déjà un compte</a>
                @elseif(auth()->user()->account_status!=='approved')
                    <div class="note">Votre compte doit être approuvé avant de réserver.</div>
                    <a class="btn" href="{{ route('dashboard') }}">Suivre ma demande</a>
                @elseif(auth()->id()===$trip->user_id)
                    <p>Vous êtes le livreur de ce trajet.</p>
                    <a href="{{ route('dashboard') }}" class="btn">Gérer mes demandes</a>
                @else
                    <form action="{{ route('bookings.store',$trip) }}" method="post" data-price="{{ $trip->price_cents }}" data-fee="{{ config('marketplace.commission_percent') }}">
                        @csrf
                        <label>Poids en kg (entier)<input type="number" name="weight_kg" min="1" max="{{ $trip->remainingKg() }}" value="{{ old('weight_kg',1) }}" required data-weight>
                        </label>
                        <label>Contenu détaillé<textarea name="contents" required minlength="10" maxlength="2000" placeholder="Ex. 3 pulls en coton, 2 livres…">{{ old('contents') }}</textarea>
                        </label>
                        <label>Valeur déclarée (€)<input type="number" name="declared_value" min="0" max="5000" step="0.01" value="{{ old('declared_value') }}" required>
                        </label>
                        <h3>À qui est destiné le colis ?</h3>
                        <label>Nom du destinataire<input name="recipient_name" value="{{ old('recipient_name') }}" required maxlength="120">
                        </label>
                        <label>Téléphone du destinataire<input name="recipient_phone" type="tel" value="{{ old('recipient_phone') }}" required maxlength="30">
                        </label>
                        <label>Adresse de remise à destination<textarea name="recipient_address" required maxlength="500">{{ old('recipient_address') }}</textarea>
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="declaration" value="1" required>
                            <span>Je déclare le contenu exact, confirme l’absence de produits interdits et accepte les <a href="{{ route('terms') }}">conditions</a>. Cette déclaration n’exonère personne de ses obligations légales.</span>
                        </label>
                        <div class="price-summary">
                            <span>Total estimé</span>
                            <strong data-total>{{ number_format(($trip->price_cents + round($trip->price_cents * config('marketplace.commission_percent') / 100)) / 100,2,',',' ') }} €</strong>
                        </div>
                        <button class="btn full">Envoyer ma demande </button>
                        <small>Aucun paiement à cette étape.</small>
                    </form>
                @endif
            </aside>
        </div>
    </section>
@endsection
