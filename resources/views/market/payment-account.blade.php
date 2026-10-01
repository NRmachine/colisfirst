@extends('layouts.market')
@section('title','Mes paiements')
@section('content')<section class="wrap section narrow"><h1 class="page-title">Mes versements</h1><div class="panel">
@if(!$available)<p>Les paiements en ligne ne sont pas encore ouverts. Vous serez informé de leur disponibilité.</p>
@elseif($ready)<p>Votre compte de versement est prêt à recevoir la part transport de vos réservations.</p><p>Revenez compléter vos informations si notre prestataire de paiement vous demande de nouveaux justificatifs.</p>
@else<p>Configurez votre identité et vos coordonnées de versement sur la page sécurisée de notre prestataire de paiement. ColisFirst ne collecte pas votre numéro de carte ou vos coordonnées bancaires dans ce formulaire.</p>
@endif
@if($available)<form method="post" action="{{ route('payments.onboarding') }}">@csrf<button class="btn">{{ $ready?'Vérifier mes informations de versement':'Configurer mes versements' }}</button></form>@endif
<p>Pour ce parcours, les fonds du transport sont affectés au compte connecté après paiement ; ils ne sont pas bloqués jusqu’à la livraison. Les frais et délais de versement dépendent du prestataire de paiement.</p></div></section>@endsection
