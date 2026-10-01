@extends('layouts.market')
@section('title','Trouver un trajet')
@section('content')
    <section class="wrap section">
        <span class="eyebrow">FRANCE  VOS PROCHES</span>
        <h1 class="page-title">Trouver un trajet</h1>
        @include('market.partials.search-history')
        <form class="search-box inline-search" action="{{ route('trips.index') }}">
            <label>Ville de départ<input name="origin" value="{{ request('origin') }}" placeholder="Ex. Paris">
            </label>
            <label>Pays d’arrivée<select name="country">
                    <option value="">Tous les pays</option>
                <option @selected(request('country')==='Algérie')>Algérie</option>
                <option @selected(request('country')==='Maroc')>Maroc</option>
                <option @selected(request('country')==='Tunisie')>Tunisie</option>
            </select>
        </label>
        <label>À partir du<input type="date" name="date" value="{{ request('date') }}">
        </label>
        <label>Trier par<select name="sort">
                <option value="date">Date de départ</option>
                <option value="price" @selected(request('sort')==='price')>Prix au kg</option>
            </select>
        </label>
        <button class="btn">Rechercher </button>
    </form>
    <div class="section-heading">
        <p>{{ $trips->total() }} trajet(s) trouvé(s)</p>
        <a href="{{ route('trips.index') }}">Réinitialiser les filtres</a>
    </div>
    <div class="cards">
        @forelse($trips as $trip) @include('market.partials.trip-card') @empty
            <div class="empty">
                <h3>Aucun départ pour ces critères.</h3>
                <p>Essayez une autre ville ou une date plus proche.</p>
                <a class="btn" href="{{ route('trips.index') }}">Voir tous les départs</a>
            </div>
        @endforelse
    </div>
    <div class="pagination">{{ $trips->links('market.partials.pagination') }}</div>
</section>
@endsection
