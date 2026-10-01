<?php

use App\Models\Booking;
use App\Models\DriverApplication;
use App\Models\PrivacyRequest;
use Illuminate\Support\Facades\Storage;

function signedPaymentEvent(array $event): array
{
    $payload = json_encode($event, JSON_THROW_ON_ERROR);
    $time = time();

    return [$payload, 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$payload, 'whsec_local_test')];
}

it('validates signed payments and processes duplicate events only once', function () {
    config(['payments.webhook_secret' => 'whsec_local_test', 'payments.live' => false]);
    $client = marketUser();
    $trip = marketTrip(marketUser('driver'));
    $this->actingAs($client)->post(route('bookings.store', $trip), parcelData());
    $booking = Booking::firstOrFail();
    $booking->update(['status' => 'payment_pending', 'payment_status' => 'processing', 'stripe_session_id' => 'cs_test_sample', 'stripe_livemode' => false]);
    $event = ['id' => 'evt_local_1', 'object' => 'event', 'type' => 'checkout.session.completed', 'livemode' => false, 'data' => ['object' => ['object' => 'checkout.session', 'id' => 'cs_test_sample', 'payment_status' => 'paid', 'currency' => 'eur', 'amount_total' => $booking->total_cents, 'client_reference_id' => $booking->reference, 'metadata' => ['booking_id' => (string) $booking->id], 'payment_intent' => 'pi_local_1']]];
    [$body,$signature] = signedPaymentEvent($event);
    $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 'bad', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
    expect($booking->fresh()->status)->toBe('payment_pending');
    $wrong = $event;
    $wrong['data']['object']['amount_total'] = 1;
    [$wrongBody,$wrongSignature] = signedPaymentEvent($wrong);
    $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $wrongSignature, 'CONTENT_TYPE' => 'application/json'], $wrongBody)->assertStatus(400);
    foreach ([1, 2] as $attempt) {
        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
    }
    expect($booking->fresh()->payment_status)->toBe('test_paid');
    $this->assertDatabaseCount('payment_events', 1);
});

it('does not activate payment without configuration or for another client', function () {
    $client = marketUser();
    $trip = marketTrip(marketUser('driver'));
    $this->actingAs($client)->post(route('bookings.store', $trip), parcelData());
    $booking = Booking::firstOrFail();
    $booking->update(['status' => 'accepted']);
    config(['payments.enabled' => false]);
    $this->post(route('payments.checkout', $booking))->assertStatus(503);
    $this->actingAs(marketUser())->post(route('payments.checkout', $booking))->assertForbidden();
    expect($booking->fresh()->payment_status)->toBe('unpaid');
});

it('renders legal pages and restricts personal exports and rights responses', function () {
    foreach (['/mentions-legales', '/confidentialite', '/cookies', '/accessibilite', '/cadre-reglementaire'] as $url) {
        $this->get($url)->assertOk();
    }
    $this->get('/mes-donnees/export')->assertRedirect('/login');
    $user = marketUser();
    $other = marketUser();
    $this->actingAs($user)->post('/mes-donnees', ['type' => 'erase', 'details' => 'Suppression demandée'])->assertRedirect();
    $request = PrivacyRequest::firstOrFail();
    $export = $this->get('/mes-donnees/export')->assertOk()->streamedContent();
    expect($export)->toContain($user->email)->not->toContain($other->email)->not->toContain('password');
    $this->actingAs($other)->get('/mes-donnees')->assertOk()->assertDontSee('Suppression demandée');
    $this->post(route('admin.privacy', $request), ['status' => 'completed', 'response' => 'Réponse non autorisée'])->assertForbidden();
    $this->actingAs(marketUser('admin'))->post(route('admin.privacy', $request), ['status' => 'processing', 'response' => 'Votre demande est en cours de traitement.'])->assertRedirect();
    expect($request->fresh()->status)->toBe('processing');
    $this->post('/cookies/preferences', ['choice' => 'essential'])->assertRedirect()->assertCookie('cf_cookie_notice');
});

it('purges expired private documents but preserves recent applications', function () {
    Storage::fake('local');
    $user = marketUser();
    $path = 'applications/'.$user->id.'/identity.pdf';
    Storage::disk('local')->put($path, 'private');
    $application = DriverApplication::create(['user_id' => $user->id, 'status' => 'approved', 'business_type' => 'particulier', 'experience' => 'Test', 'documents' => ['identity' => $path], 'reviewed_at' => now()->subDays(91)]);
    $this->artisan('privacy:purge-documents --dry-run')->assertSuccessful();
    Storage::disk('local')->assertExists($path);
    $this->artisan('privacy:purge-documents')->assertSuccessful();
    Storage::disk('local')->assertMissing($path);
    expect($application->fresh()->documents)->toBe([]);
});
