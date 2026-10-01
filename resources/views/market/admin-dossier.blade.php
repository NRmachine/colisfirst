@extends('layouts.market')
@section('title', 'Examen du dossier')
@section('content')
<section class="wrap section"><a class="back" href="{{ route('dashboard') }}">Mon espace</a><h1 class="page-title">Examen du dossier</h1>
<h2>{{ $user->name }}</h2><p>{{ $user->email }} · {{ $user->phone }} · {{ $user->city }}</p>
<dl class="panel">
@foreach(['address'=>'Adresse','postal_code'=>'Code postal','country'=>'Pays','business_type'=>'Statut professionnel','company_name'=>'Entreprise','registration'=>'SIRET','legal_form'=>'Forme juridique','representative'=>'Représentant légal','company_address'=>'Adresse entreprise','company_email'=>'E-mail entreprise','company_phone'=>'Téléphone entreprise','vat'=>'TVA'] as $field=>$label)
@if(filled(data_get($user->account_details,$field)))<dt><strong>{{ $label }}</strong></dt><dd>{{ data_get($user->account_details,$field) }}</dd>@endif
@endforeach
</dl><h2>Documents actuels</h2><p>Valider une pièce ne modifie pas automatiquement le statut du compte ou de la candidature.</p>
@forelse($documents as $document)
<article class="panel document-panel"><h3>{{ $document->label() }}</h3><p>{{ $document->statusLabel() }}@if($document->expires_at) · Expiration : {{ $document->expires_at->format('d/m/Y') }}@endif</p><a href="{{ route('account.download',$document) }}">Télécharger pour examiner</a><p>{{ $document->review_note }}</p>
<form method="post" action="{{ route('account.review',$document) }}">@csrf<input type="hidden" name="version" value="{{ hash('sha256',$document->path) }}"><label>Compte rendu<textarea name="review_note" required minlength="5" maxlength="1000"></textarea></label><div class="actions"><button class="btn small" name="status" value="approved">Valider cette pièce</button><button class="btn small outline" name="status" value="rejected">Demander un remplacement</button></div></form></article>
@empty<p>Aucun document déposé dans cet espace.</p>@endforelse
</section>
@endsection
