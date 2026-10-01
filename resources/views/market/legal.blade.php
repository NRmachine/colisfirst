@extends('layouts.market')
@section('title', 'Mentions légales')
@section('content')
<section class="wrap section narrow prose"><h1 class="page-title">Mentions légales</h1><p>Informations relatives à l’éditeur de ColisFirst.</p>
<dl class="legal-details">@foreach(['operator'=>'Exploitant','status'=>'Forme juridique','registration'=>'Immatriculation / SIRET','address'=>'Adresse professionnelle','email'=>'Contact','phone'=>'Téléphone','director'=>'Directeur de publication','vat'=>'TVA intracommunautaire, si applicable','host'=>'Hébergeur','host_address'=>'Adresse de l’hébergeur','host_phone'=>'Téléphone de l’hébergeur','mediator'=>'Médiateur de la consommation, si applicable'] as $key=>$label)<dt><strong>{{ $label }}</strong></dt><dd>{{ config('legal.'.$key) ?: 'À renseigner par l’exploitant avant l’ouverture commerciale.' }}</dd>@endforeach</dl>
<h2>Contenus et illustrations</h2><p>Le logo ColisFirst est fourni par le porteur du projet. Les scènes humaines signalées comme illustrations ne représentent pas des clients, salariés ou livreurs réels. Elles ne constituent pas des témoignages.</p>
<h2>Liens utiles</h2><p><a href="{{ route('privacy') }}">Protection des données</a> · <a href="{{ route('cookies') }}">Cookies</a> · <a href="{{ route('terms') }}">Conditions d’utilisation</a> · <a href="{{ route('regulations') }}">Cadre réglementaire</a></p>
<p>Ces informations doivent être complétées avec les données exactes de l’entreprise. Leur présence ne constitue pas une certification de conformité.</p></section>
@endsection
