@extends('layouts.market')
@section('title', 'Suivi de votre demande')
@section('content')
<section class="wrap section"><a class="back" href="{{ route('dashboard') }}">Mon espace</a><h1 class="page-title">Suivi de votre demande</h1>
<a href="{{ route('support.index') }}">Toutes les demandes</a><h2>#{{ $ticket->id }} · {{ $ticket->subject }}</h2><p class="pill">{{ $ticket->statusLabel() }}</p>
<div class="support-thread">
@foreach($ticket->replies as $reply)
<article class="panel document-panel"><strong>{{ $reply->user->role==='admin'?'Équipe ColisFirst':$reply->user->name }}</strong><small> · {{ $reply->created_at->format('d/m/Y à H:i') }}</small><p class="message-body">{{ $reply->body }}</p></article>
@endforeach
<form class="panel" method="post" action="{{ route('support.reply',$ticket) }}">@csrf<label>{{ $ticket->status==='closed'?'Rouvrir la demande avec un message':'Ajouter une réponse' }}<textarea name="body" required minlength="2" maxlength="5000">{{ old('body') }}</textarea></label>
@if($admin)<label><input type="checkbox" name="close" value="1"> Clôturer après cette réponse</label>@endif
<button class="btn">Envoyer la réponse</button></form></div>
</section>
@endsection
