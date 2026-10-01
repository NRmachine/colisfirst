@extends('layouts.market')
@section('title','Mes notifications')
@section('content')
<section class="wrap section narrow"><a href="{{ route('dashboard') }}">Mon espace</a><h1 class="page-title">Mes notifications</h1><p>Retrouvez les changements de vos réservations et les réponses du support.</p>
@forelse($alerts as $alert)
<article class="panel document-panel"><span class="pill">{{ $alert->read_at?'Lue':'Nouvelle' }}</span><h2><a href="{{ $alert->url }}">{{ $alert->title }}</a></h2><p>{{ $alert->created_at->format('d/m/Y à H:i') }}</p>
@if(!$alert->read_at)<form method="post" action="{{ route('alerts.read',$alert) }}">@csrf<button class="btn small outline">Marquer comme lue</button></form>@endif
</article>
@empty<p>Aucune notification pour le moment.</p>@endforelse
{{ $alerts->links('market.partials.pagination') }}</section>
@endsection
