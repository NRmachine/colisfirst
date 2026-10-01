<article class="trip-card">
    <div class="trip-main">
        <div class="card-top"><span class="pill">{{ $trip->country }}</span><time datetime="{{ $trip->departure_date->format('Y-m-d') }}">{{ $trip->departure_date->format('d/m/Y') }}</time></div>
        <a class="trip-route" href="{{ route('trips.show',$trip) }}">{{ $trip->origin }} <span></span> {{ $trip->destination }}</a>
    </div>
    <div class="trip-pickup"><span class="mobile-label">Ramassage</span><p>{{ Str::limit($trip->pickup_points,85) }}</p><small>{{ $trip->remainingKg() }} kg disponibles</small></div>
    <div class="card-driver"><span class="avatar">{{ mb_substr($trip->driver->name,0,1) }}</span><div><strong>{{ Str::before($trip->driver->name,' ') }}</strong><small>Profil de démonstration</small></div></div>
    <div class="card-bottom"><div><strong>{{ number_format($trip->price_cents/100,2,',',' ') }} €</strong><small>/ kg · hors frais de service</small></div><a class="trip-open" href="{{ route('trips.show',$trip) }}" aria-label="Voir le trajet {{ $trip->origin }} vers {{ $trip->destination }}">Voir <span></span></a></div>
</article>
