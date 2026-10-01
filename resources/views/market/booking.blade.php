@extends('layouts.market')
@section('title', $booking->reference)
@section('content')
    <section class="wrap section">
        <a class="back" href="{{ route('dashboard') }}"> Mon espace</a>
        <div class="section-heading">
            <div>
                <span class="eyebrow">{{ $booking->reference }}</span>
                <h1 class="page-title">{{ $booking->trip->origin }}  {{ $booking->trip->destination }}</h1>
            </div>
            <span class="pill">{{ $booking->statusLabel() }}</span>
        </div>
        <div class="detail-grid">
            <div>
                <div class="panel">
                    <h2>Le voyage de votre colis</h2>
                    <div class="timeline">
                        @foreach(['pending'=>'Demande envoyée','accepted'=>'Acceptation du livreur','confirmed'=>'Réservation confirmée','collected'=>'Prise en charge','delivered'=>'Réception confirmée'] as $key=>$label)
                            <div @class(['timeline-item','current'=>$booking->status===$key])>
                                <span>●</span> {{ $label }}</div>
                        @endforeach
                    </div>
                    <h3>Déclaration du contenu</h3>
                    <p class="preline">{{ $booking->contents }}</p>
                    <div class="info-grid">
                        <div>
                            <small>Poids déclaré</small>
                            <strong>{{ $booking->weight_kg }} kg</strong>
                        </div>
                        <div>
                            <small>Valeur déclarée</small>
                            <strong>{{ number_format($booking->declared_value_cents/100,2,',',' ') }} €</strong>
                        </div>
                        <div>
                            <small>Départ</small>
                            <strong>{{ $booking->trip->departure_date->format('d/m/Y') }}</strong>
                        </div>
                    </div>
                    <small>Déclaration acceptée le {{ $booking->declared_at->format('d/m/Y à H:i') }} · version {{ $booking->declaration_version }}</small>
                </div>
                @if(in_array($booking->status,['confirmed','collected','delivered']))
                    <div class="panel mt">
                        <h2>Coordonnées et remise</h2>
                        <p>
                            <strong>Livreur :</strong> {{ $booking->trip->driver->name }} · {{ $booking->trip->driver->phone }}</p>
                        <p>
                            <strong>Expéditeur :</strong> {{ $booking->client->name }} · {{ $booking->client->phone }}</p>
                        <p>
                            <strong>Destinataire :</strong> {{ $booking->recipient_name }} · {{ $booking->recipient_phone }}</p>
                        <p class="preline">{{ $booking->recipient_address }}</p>
                        <h3>Ramassage</h3>
                        <p class="preline">{{ $booking->trip->pickup_points }}</p>
                    </div>
                    <div class="panel mt">
                        <h2>Votre conversation</h2>
                        @forelse($booking->messages as $message)
                            <div class="message">
                                <strong>{{ $message->user->name }}</strong>
                                <small>{{ $message->created_at->format('d/m H:i') }}</small>
                                <p class="preline">{{ $message->body }}</p>
                            </div>
                        @empty
                            <p class="muted">Convenez ici des détails de la remise.</p>
                        @endforelse
                        <form action="{{ route('bookings.message',$booking) }}" method="post">
                            @csrf
                            <label>Votre message<textarea name="body" required maxlength="2000">
                                </textarea>
                            </label>
                            <button class="btn">Envoyer le message</button>
                        </form>
                    </div>
                @else
                    <div class="note">Les coordonnées et la conversation deviennent accessibles après confirmation du paiement.</div>
                @endif
            </div>
            <aside>
                <div class="panel">
                    <h2>Récapitulatif</h2>
                    <div class="price-summary">
                        <span>Transport</span>
                        <strong>{{ number_format($booking->transport_cents/100,2,',',' ') }} €</strong>
                    </div>
                    <div class="price-summary">
                        <span>Frais de service</span>
                        <strong>{{ number_format($booking->fee_cents/100,2,',',' ') }} €</strong>
                    </div>
                    <div class="price-summary total">
                        <span>Total</span>
                        <strong>{{ number_format($booking->total_cents/100,2,',',' ') }} €</strong>
                    </div>
                    <p class="muted">{{ ['paid'=>'Paiement confirmé','test_paid'=>'Paiement de test · aucun débit réel','simulated'=>'Paiement simulé · aucun débit réel','processing'=>'Confirmation de paiement en attente','refunded'=>'Paiement remboursé','partially_refunded'=>'Paiement partiellement remboursé','disputed'=>'Paiement contesté'][$booking->payment_status] ?? 'Aucun paiement effectué' }}. Douane éventuelle non incluse.</p>
                    @if(auth()->id()===$booking->trip->user_id && $booking->status==='pending')
                        <p>Vérifiez le contenu et la capacité avant d’accepter.</p>
                        @foreach(['accept'=>'Accepter la demande','reject'=>'Refuser la demande'] as $action=>$label)
                            <form method="post" action="{{ route('bookings.transition',$booking) }}">
                                @csrf
                                <input type="hidden" name="action" value="{{ $action }}">
                                <button class="btn full {{ $action==='reject'?'outline':'' }}">{{ $label }}</button>
                            </form>
                        @endforeach
                    @endif
                    @if(auth()->id()===$booking->user_id && in_array($booking->status,['accepted','payment_pending']))
    @if(app(\App\Services\StripePayments::class)->configured())
        @if(!config('payments.live'))<p class="note">Le paiement est en mode test : aucun débit bancaire réel.</p>@endif
        <form method="post" action="{{ route('payments.checkout',$booking) }}">@csrf<button class="btn full">Payer {{ number_format($booking->total_cents/100,2,',',' ') }} € par carte</button></form>
    @else
        <div class="note">Le paiement en ligne n’est pas encore disponible. Aucun montant ne vous a été débité.</div>
    @endif
@endif
@if(auth()->id()===$booking->trip->user_id && $booking->status==='confirmed')
                        <form method="post" action="{{ route('bookings.transition',$booking) }}">
                            @csrf
                            <input type="hidden" name="action" value="collect">
                            <button class="btn full">Confirmer la prise en charge</button>
                        </form>
                    @endif
                    @if(auth()->id()===$booking->user_id && $booking->status==='collected')
                        <form method="post" action="{{ route('bookings.transition',$booking) }}">
                            @csrf
                            <input type="hidden" name="action" value="deliver">
                            <button class="btn full">Confirmer la réception du colis</button>
                        </form>
                    @endif
                    @if(auth()->id()===$booking->user_id && in_array($booking->status,['pending','accepted']))
                        <form method="post" action="{{ route('bookings.transition',$booking) }}">
                            @csrf
                            <input type="hidden" name="action" value="cancel">
                            <button class="text-button full">Annuler ma demande</button>
                        </form>
                    @endif
                </div>
                <div class="note">
                    <strong>Gardez votre réservation sur Colisfirst.</strong>
                    <p>Historique, déclaration du colis et conversation restent réunis dans votre espace.</p>
                    <a href="{{ route('trust') }}">Nos règles de confiance </a>
                </div>
            </aside>
        </div>
    </section>
@endsection
