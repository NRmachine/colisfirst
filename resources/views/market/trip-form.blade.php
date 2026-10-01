@extends('layouts.market')
@section('title',isset($trip)?'Modifier un trajet':'Publier un trajet')
@section('content')
    <section class="wrap section narrow">
        <a class="back" href="{{ route('dashboard') }}"> Mon espace</a>
        <h1 class="page-title">{{ isset($trip)?'Modifier un trajet':'Publier un trajet' }}</h1>
        <p>Indiquez des lieux publics pour le ramassage. Gardez vos coordonnées privées jusqu’à la confirmation.</p>
        <form class="panel" action="{{ isset($trip)?route('trips.update',$trip):route('trips.store') }}" method="post">
            @csrf
            @if(isset($trip)) @method('PUT') @endif
            <div class="two-fields">
                <label>Ville de départ en France<input name="origin" value="{{ old('origin',$trip->origin ?? '') }}" required maxlength="100">
                </label>
                <label>Ville d’arrivée<input name="destination" value="{{ old('destination',$trip->destination ?? '') }}" required maxlength="100">
                </label>
            </div>
            <label>Pays d’arrivée<select name="country">
                    @foreach(['Algérie','Maroc','Tunisie'] as $country)
                        <option @selected(old('country',$trip->country ?? '')===$country)>{{ $country }}</option>
                    @endforeach
                </select>
            </label>
            <div class="two-fields">
                <label>Date de départ<input type="date" name="departure_date" min="{{ now()->addDay()->format('Y-m-d') }}" value="{{ old('departure_date',isset($trip)?$trip->departure_date->format('Y-m-d'):'') }}" required>
                </label>
                <label>Arrivée prévue<input type="date" name="arrival_date" value="{{ old('arrival_date',isset($trip)?$trip->arrival_date->format('Y-m-d'):'') }}" required>
                </label>
            </div>
            <label>Points et horaires de ramassage<textarea name="pickup_points" required maxlength="1000" placeholder="Ex. Paris : gare de Bercy, 9 h. Lyon : gare Part-Dieu, 15 h.">{{ old('pickup_points',$trip->pickup_points ?? '') }}</textarea>
            </label>
            <div class="two-fields">
                <label>Votre prix au kg (€)<input type="number" name="price" min="1" max="100" step="0.01" value="{{ old('price',isset($trip)?$trip->price_cents/100:8) }}" required>
                </label>
                <label>Capacité totale (kg)<input type="number" name="capacity_kg" min="1" max="1000" value="{{ old('capacity_kg',$trip->capacity_kg ?? 50) }}" required>
                </label>
            </div>
            <label>Conditions d’acceptation<textarea name="conditions" required maxlength="2000" placeholder="Dimensions maximales, emballage, objets acceptés…">{{ old('conditions',$trip->conditions ?? '') }}</textarea>
            </label>
            <div class="note">Votre prix correspond au montant transport. Le client voit aussi les {{ config('marketplace.commission_percent') }} % de frais de service. Les versements dépendent de l’ouverture des paiements en ligne et de la validation du compte de paiement.</div>
            <button class="btn full">{{ isset($trip)?'Enregistrer les modifications':'Publier mon trajet' }}</button>
        </form>
    </section>
@endsection
