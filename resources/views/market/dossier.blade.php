@extends('layouts.market')
@section('title', 'Mon dossier')
@section('content')
<section class="wrap section"><a class="back" href="{{ route('dashboard') }}">Mon espace</a><h1 class="page-title">Mon dossier</h1>
<p>Vos coordonnées, votre activité et vos justificatifs restent dans votre espace privé.</p>
<div class="account-grid"><section class="panel"><h2>Mes coordonnées</h2><p><strong>{{ $user->name }}</strong><br>{{ $user->email }}<br>{{ $user->phone }} · {{ $user->city }}</p><a href="{{ route('profile') }}">Modifier mon nom, mon téléphone ou ma ville</a>
<form method="post" action="{{ route('account.save') }}">@csrf
@foreach(['address'=>'Adresse personnelle','postal_code'=>'Code postal','country'=>'Pays de résidence'] as $field=>$label)
<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field,$details[$field] ?? '') }}" required maxlength="{{ $field==='address'?250:($field==='postal_code'?20:100) }}" autocomplete="{{ ['address'=>'street-address','postal_code'=>'postal-code','country'=>'country-name'][$field] }}"></label>
@endforeach
@if($driver)
<h2>Mon activité de livreur</h2><label>Statut<select name="business_type" required>
@foreach(['particulier'=>'Particulier','micro'=>'Micro-entreprise','societe'=>'Société'] as $value=>$label)
<option value="{{ $value }}" @selected(old('business_type',$details['business_type'] ?? 'particulier')===$value)>{{ $label }}</option>
@endforeach
</select></label><p>Pour une activité professionnelle, renseignez la dénomination, le SIRET et l’adresse. Pour une société, ajoutez la forme juridique et le représentant.</p>
@foreach(['company_name'=>'Nom de l’entreprise / raison sociale','registration'=>'SIRET (14 chiffres)','legal_form'=>'Forme juridique','representative'=>'Représentant légal','company_address'=>'Adresse de l’entreprise','company_email'=>'E-mail professionnel','company_phone'=>'Téléphone professionnel','vat'=>'Numéro de TVA (si applicable)'] as $field=>$label)
<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field,$details[$field] ?? '') }}" type="{{ $field==='company_email'?'email':'text' }}" maxlength="500"></label>
@endforeach
@endif
<button class="btn">Enregistrer mon dossier</button></form></section>
<section><h2>Mes documents</h2><p>PDF, JPG ou PNG, 5 Mo maximum par fichier. Le remplacement retire l’ancienne version et déclenche un nouvel examen. Ces fichiers ne sont jamais affichés aux autres clients ou livreurs.</p>
@foreach($kinds as $kind=>$label)
@php($document=$documents->get($kind))
<article class="panel document-panel"><h3>{{ $label }}</h3>
@if($document)
<p><span class="pill">{{ $document->statusLabel() }}</span></p><p>Mis à jour le {{ $document->updated_at->format('d/m/Y') }}@if($document->expires_at) · Valable jusqu’au {{ $document->expires_at->format('d/m/Y') }}@endif</p>
<a href="{{ route('account.download',$document) }}">Télécharger mon document</a>
@if($document->review_note)<p>Retour de l’administration : {{ $document->review_note }}</p>@endif
@else<p class="muted">Aucun document déposé.</p>@endif
<form method="post" action="{{ route('account.upload') }}" enctype="multipart/form-data">@csrf<input type="hidden" name="kind" value="{{ $kind }}"><label>{{ $document?'Remplacer le fichier':'Ajouter le fichier' }}<input type="file" name="document" accept="application/pdf,image/jpeg,image/png" required></label><label>Date d’expiration (si applicable)<input type="date" name="expires_at" min="{{ today()->format('Y-m-d') }}"></label><button class="btn small">{{ $document?'Remplacer et soumettre':'Déposer le document' }}</button></form></article>
@endforeach
@if($application)<div class="note">Les pièces de votre candidature initiale suivent leur propre examen et leur durée de conservation. Cet espace sert à déposer vos justificatifs actuels et leurs renouvellements.</div>@endif
</section></div>
</section>
@endsection
