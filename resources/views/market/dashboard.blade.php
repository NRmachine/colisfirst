@extends('layouts.market')
@section('title','Mon espace')
@section('content')
    <section class="wrap section">
        <div class="section-heading">
            <div>
                <h1 class="page-title">Bonjour {{ Str::before($user->name,' ') }}<span class="green">.</span>
                </h1>
            </div>
            <div class="actions">
                <a class="btn outline" href="{{ route('profile') }}">Mon profil</a>
                <a class="btn outline" href="{{ route('alerts.index') }}">Notifications ({{ \App\Models\AccountAlert::where('user_id',$user->id)->whereNull('read_at')->count() }})</a>
                @if($user->role==='admin')
                    <a class="btn" href="{{ route('admin') }}">Administration </a>
                @elseif($user->role==='driver' && $user->account_status==='approved')
                    <a class="btn outline" href="{{ route('payments.onboarding.page') }}">Mes versements</a>
                    <a class="btn" href="{{ route('trips.create') }}">+ Publier un trajet</a>
                @else
                    <a class="btn" href="{{ route('trips.index') }}">Trouver un trajet </a>
                @endif
            </div>
        </div>
        @if($user->account_status!=='approved')
            <div class="alert">Votre compte est {{ $user->account_status==='pending'?'en attente de validation':'refusé' }}. Les réservations sont désactivées.</div>
        @endif
        <div class="account-shortcuts">
<article class="panel"><h2>Mes coordonnées et documents</h2><p>{{ $user->name }}<br>{{ $user->email }}<br>{{ $user->phone }} · {{ $user->city }}</p><p>{{ $user->role==='driver'?'Gérez votre activité, les informations de votre société et les justificatifs à renouveler.':'Complétez votre adresse et déposez ou remplacez vos pièces d’identité.' }}</p><a class="btn outline" href="{{ route('account.dossier') }}">Ouvrir mon dossier</a></article>
<article class="panel"><h2>Besoin d’aide ?</h2><p>Écrivez au support 24 h/24 et 7 j/7. Retrouvez vos échanges et les réponses dans votre espace.</p><p class="muted">Les demandes sont accessibles à toute heure ; le délai de réponse dépend de l’équipe.</p><a class="btn" href="{{ route('support.index') }}">Contacter le support</a></article>
</div>
<div class="stats">
            <div class="panel">
                <small>Réservations accessibles</small>
                <strong>{{ $bookings->total() }}</strong>
            </div>
            <div class="panel">
                <small>Statut du compte</small>
                <strong class="small-stat">{{ ['approved'=>'Approuvé','pending'=>'En examen','rejected'=>'Refusé'][$user->account_status] }}</strong>
            </div>
            <div class="panel">
                <small>{{ $user->role==='driver'?'Mes annonces':'Votre prochain départ' }}</small>
                @if($user->role==='driver')<strong>{{ $trips->count() }}</strong>
                @else
                    <a class="small-stat" href="{{ route('trips.index') }}">Explorer les trajets </a>
                @endif
            </div>
        </div>
        @if($application)
            <div class="note">
                <strong>Candidature livreur : {{ ['pending'=>'en cours d’examen','approved'=>'approuvée','rejected'=>'refusée'][$application->status] }}</strong>
                @if($application->review_note)
                    <p>{{ $application->review_note }}</p>
                @endif @if($application->status==='rejected')
                    <a href="{{ route('drivers.apply') }}">Déposer un nouveau dossier </a>
                @endif
            </div>
        @elseif($user->role==='client')
            <div class="note">Vous voyagez régulièrement ? <a href="{{ route('drivers.apply') }}">Déposer une candidature livreur </a>
            </div>
        @endif
        @if(!$user->hasVerifiedEmail())<div class="note"><strong>Confirmez votre adresse e-mail.</strong><p><a href="{{ route('verification.notice') }}">Recevoir à nouveau le lien de confirmation</a></p></div>@endif
<h2>Mes réservations</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Trajet</th>
                        <th>Poids</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)<tr>
                            <td>{{ $booking->reference }}<small>{{ $booking->user_id===$user->id?'Mon envoi':'Envoi de '.Str::before($booking->client->name,' ') }}</small>
                            </td>
                            <td>{{ $booking->trip->origin }}  {{ $booking->trip->destination }}</td>
                            <td>{{ $booking->weight_kg }} kg</td>
                            <td>{{ number_format($booking->total_cents/100,2,',',' ') }} €</td>
                            <td>
                                <span class="pill">{{ $booking->statusLabel() }}</span>
                            </td>
                            <td>
                                <a href="{{ route('bookings.show',$booking) }}">Voir </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty">Pas encore de réservation. Votre premier envoi commence avec un trajet.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $bookings->links('market.partials.pagination') }}
        @if($user->role==='driver')
            <div class="note"><a href="{{ route('payments.onboarding.page') }}">Configurer mes versements</a></div><h2 class="mt">Mes annonces</h2>
            <div class="cards">
                @forelse($trips as $trip)
                    <div class="panel">
                        <h3>{{ $trip->origin }}  {{ $trip->destination }}</h3>
                        <p>{{ $trip->departure_date->format('d/m/Y') }} · {{ $trip->remainingKg() }} kg disponibles</p>
                        <span class="pill">{{ $trip->status==='published'?'Publiée':'Fermée' }}</span>
                        @if($trip->status==='published')
                            <div class="actions mt">
                                <a href="{{ route('trips.show',$trip) }}">Voir l’annonce</a>
                                @if($trip->departure_date->isFuture() && !$trip->bookings()->whereNotIn('status',['cancelled','rejected'])->exists())<a href="{{ route('trips.edit',$trip) }}">Modifier</a>@endif
                                <form method="post" action="{{ route('trips.close',$trip) }}">
                                    @csrf
                                    <button class="text-button">Fermer les réservations</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="empty">Publiez votre premier départ pour recevoir des demandes.</div>
                @endforelse
            </div>
        @endif
    </section>
@endsection
