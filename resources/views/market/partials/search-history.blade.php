@if(session('history_status'))
    <p role="status">{{ session('history_status') }}</p>
@endif
@if($recentSearches)
    <section class="recent-searches" aria-label="Recherches récentes">
        <ul>
            @foreach($recentSearches as $search)
                <li><a href="{{ route('trips.index', $search) }}">{{ $search['origin'] ?? 'Toutes les villes' }} · {{ $search['country'] ?? 'Tous les pays' }}@if(!empty($search['date'])) · à partir du {{ $search['date'] }}@endif</a></li>
            @endforeach
        </ul>
        <form method="post" action="{{ route('search-history.destroy') }}">
            @csrf
            @method('DELETE')
            <button class="recent-searches-clear" type="submit" aria-label="Effacer mes recherches" title="Effacer mes recherches"><span aria-hidden="true">&times;</span></button>
        </form>

    </section>
@endif
