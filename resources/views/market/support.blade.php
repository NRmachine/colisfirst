@extends('layouts.market')
@section('title', 'Contacter le support')
@section('content')
<section class="wrap section"><a class="back" href="{{ route('dashboard') }}">Mon espace</a><h1 class="page-title">Contacter le support</h1>
<div class="note"><strong>Demandes accessibles 24 h/24, 7 j/7.</strong><p>Vous pouvez nous écrire à toute heure et suivre la réponse ici. Le traitement dépend de la disponibilité de l’équipe : aucune réponse immédiate n’est garantie.</p></div>
<div class="account-grid"><section class="panel"><h2>Nouvelle demande</h2><p>Précisez la référence de réservation si nécessaire. Ne transmettez ni mot de passe ni numéro de carte. Déposez vos pièces d’identité uniquement dans « Mon dossier ».</p><form method="post" action="{{ route('support.store') }}">@csrf<label>Sujet<input name="subject" required minlength="5" maxlength="160" value="{{ old('subject') }}"></label><label>Votre message<textarea name="body" required minlength="10" maxlength="5000">{{ old('body') }}</textarea></label><button class="btn">Envoyer au support</button></form></section><section><h2>{{ $admin?'Demandes des utilisateurs':'Mes demandes' }}</h2>
@forelse($tickets as $ticket)
<article class="panel document-panel"><span class="pill">{{ $ticket->statusLabel() }}</span><h3><a href="{{ route('support.show',$ticket) }}">#{{ $ticket->id }} · {{ $ticket->subject }}</a></h3><p>{{ $admin?$ticket->user->name:'' }} · Dernière activité le {{ $ticket->updated_at->format('d/m/Y à H:i') }}</p></article>
@empty<p>Aucune demande pour le moment.</p>@endforelse
{{ $tickets->links() }}</section></div>
</section>
@endsection
