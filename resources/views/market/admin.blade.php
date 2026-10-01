@extends('layouts.market')
@section('title','Administration')
@section('content')
    <section class="wrap section">
        <span class="eyebrow">ADMINISTRATION</span>
        <h1 class="page-title">Administration</h1><div class="actions"><a class="btn" href="{{ route('account.admin') }}">Dossiers et documents</a><a class="btn outline" href="{{ route('support.index') }}">Demandes de support</a></div>
        <div class="stats">
            <div class="panel">
                <small>Comptes à examiner</small>
                <strong>{{ $users->count() }}</strong>
            </div>
            <div class="panel">
                <small>Trajets créés</small>
                <strong>{{ $tripCount }}</strong>
            </div>
            <div class="panel">
                <small>Commissions simulées · livraisons terminées</small>
                <strong>{{ number_format($fees/100,2,',',' ') }} €</strong>
            </div>
        </div>
        <h2>Demandes de compte</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>E-mail</th>
                        <th>Ville</th>
                        <th>Décision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)<tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->city }}</td>
                            <td>
                                <form method="post" action="{{ route('admin.user',$user) }}" class="actions">
                                    @csrf
                                    <button class="btn small" name="status" value="approved">Approuver</button>
                                    <button class="btn small outline" name="status" value="rejected">Refuser</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Toutes les demandes ont été traitées.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h2 class="mt">Candidatures livreurs</h2>
        <div class="cards">
            @forelse($applications as $application)
                <article class="panel">
                    <span class="pill">{{ ['pending'=>'À examiner','approved'=>'Approuvée','rejected'=>'Refusée'][$application->status] }}</span>
                    <h3>{{ $application->user->name }}</h3>
                    <p>{{ $application->business_type }} · {{ $application->business_number ?: 'Sans SIRET renseigné' }}</p>
                    <p>{{ $application->experience }}</p>
                    <div class="document-links">
                        @foreach($application->documents as $kind=>$path)
                            <a href="{{ route('admin.document',[$application,$kind]) }}">↓ {{ ['identity'=>'Identité','insurance'=>'Assurance','license'=>'Permis','business'=>'Activité'][$kind] ?? $kind }}</a>
                        @endforeach
                    </div>
                    @if($application->status==='pending')
                        <form method="post" action="{{ route('admin.application',$application) }}">
                            @csrf
                            <label>Compte rendu de l’examen<textarea name="review_note" required minlength="5" maxlength="1000" placeholder="Pièces examinées, éligibilité, motif de la décision…">
                                </textarea>
                            </label>
                            <div class="actions">
                                <button class="btn small" name="status" value="approved">Approuver</button>
                                <button class="btn small outline" name="status" value="rejected">Refuser</button>
                            </div>
                        </form>
                    @else
                        <p>{{ $application->review_note }}</p>
                    @endif
                </article>
            @empty
                <div class="empty">Aucune candidature déposée.</div>
            @endforelse
        </div>
        <h2 class="mt">Dernières réservations</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Trajet</th>
                        <th>Statut</th>
                        <th>Frais de service</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)<tr>
                            <td>
                                <a href="{{ route('bookings.show',$booking) }}">{{ $booking->reference }}</a>
                            </td>
                            <td>{{ $booking->client->name }}</td>
                            <td>{{ $booking->trip->origin }}  {{ $booking->trip->destination }}</td>
                            <td>{{ $booking->statusLabel() }}</td>
                            <td>{{ number_format($booking->fee_cents/100,2,',',' ') }} €</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Aucune réservation pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    <h2 class="mt">Demandes relatives aux données personnelles</h2>
@forelse($privacyRequests as $privacyRequest)
<article class="panel mt"><h3>{{ $privacyRequest->user->name }} : {{ $privacyRequest->label() }}</h3><p>Échéance : {{ \Carbon\Carbon::parse($privacyRequest->due_at)->format('d/m/Y') }} · {{ $privacyRequest->status }}</p><p>{{ $privacyRequest->details }}</p><form method="post" action="{{ route('admin.privacy',$privacyRequest) }}">@csrf<label>Réponse et actions effectivement réalisées<textarea name="response" required minlength="10" maxlength="5000">{{ $privacyRequest->response }}</textarea></label><label>Statut<select name="status"><option value="processing">En cours de traitement</option><option value="completed">Réponse apportée</option></select></label><p>Répondre ne supprime aucune donnée automatiquement. Exécutez les opérations nécessaires avant de clôturer.</p><button class="btn">Enregistrer la réponse</button></form></article>
@empty<p>Aucune demande.</p>@endforelse
</section>
@endsection
