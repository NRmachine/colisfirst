<?php

use App\Models\Booking;
use App\Models\DriverApplication;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function marketUser(string $role = 'client', string $status = 'approved'): User
{
    $user = User::factory()->create();
    $user->role = $role;
    $user->account_status = $status;
    $user->phone = '0600000000';
    $user->city = 'Paris';
    $user->save();

    return $user;
}
function marketTrip(User $driver): Trip
{
    return Trip::create(['user_id' => $driver->id, 'origin' => 'Paris', 'destination' => 'Alger', 'country' => 'Algérie', 'departure_date' => today()->addDays(3), 'arrival_date' => today()->addDays(5), 'pickup_points' => 'Gare de Bercy, 9 h', 'conditions' => 'Livres et vêtements', 'price_cents' => 800, 'capacity_kg' => 10]);
}
function parcelData(): array
{
    return ['weight_kg' => 5, 'contents' => 'Trois pulls et deux livres', 'declared_value' => 60, 'recipient_name' => 'Destinataire Test', 'recipient_phone' => '0000000000', 'recipient_address' => 'Adresse de test à Alger', 'declaration' => 1];
}

it('renders public pages and filters trips without exposing driver contacts', function () {
    $driver = marketUser('driver');
    $trip = marketTrip($driver);
    foreach (['/', '/trajets', '/trajets/'.$trip->id, '/login', '/register', '/comment-ca-marche', '/confiance', '/conditions', '/confidentialite'] as $url) {
        $this->get($url)->assertOk()->assertDontSee($driver->email)->assertDontSee($driver->phone);
    }
    $this->get('/trajets?country=Maroc')->assertOk()->assertSee('Aucun départ');
});

it('registers only a pending client even when a role is submitted', function () {
    $this->post('/register', ['name' => 'Client Test', 'email' => 'new@example.test', 'phone' => '0600000000', 'city' => 'Paris', 'password' => 'SecureDemo123!', 'password_confirmation' => 'SecureDemo123!', 'terms' => 1, 'role' => 'admin', 'account_status' => 'approved'])->assertRedirect('/dashboard');
    $user = User::where('email', 'new@example.test')->firstOrFail();
    expect($user->role)->toBe('client')->and($user->account_status)->toBe('pending')->and($user->terms_accepted_at)->not->toBeNull();
    $this->get('/dashboard')->assertOk()->assertSee('en attente de validation');
});

it('requires acceptance and contact details during registration', function () {
    $this->post('/register', ['name' => 'Client Test', 'email' => 'new@example.test', 'password' => 'SecureDemo123!', 'password_confirmation' => 'SecureDemo123!'])->assertSessionHasErrors(['terms', 'phone', 'city']);
    expect(User::count())->toBe(0);
});

it('completes the client and driver flow with server calculated prices', function () {
    config(['marketplace.demo' => true]);
    $client = marketUser();
    $driver = marketUser('driver');
    $trip = marketTrip($driver);
    $this->actingAs($client)->post(route('bookings.store', $trip), parcelData() + ['total_cents' => 1])->assertRedirect();
    $booking = Booking::firstOrFail();
    expect($booking->total_cents)->toBe(4480)->and($trip->remainingKg())->toBe(5);
    $this->get(route('bookings.show', $booking))->assertOk()->assertDontSee($driver->phone);
    $this->post(route('bookings.pay', $booking))->assertStatus(422);
    $this->actingAs($driver)->post(route('bookings.transition', $booking), ['action' => 'accept'])->assertRedirect();
    $this->actingAs($client)->post(route('bookings.pay', $booking))->assertRedirect();
    expect($booking->fresh()->status)->toBe('confirmed')->and($booking->fresh()->payment_status)->toBe('simulated');
    $this->post(route('bookings.pay', $booking))->assertStatus(422);
    $this->get(route('bookings.show', $booking))->assertOk()->assertSee($driver->phone);
    $this->post(route('bookings.message', $booking), ['body' => 'Rendez-vous à la gare.'])->assertRedirect();
    $this->actingAs($driver)->post(route('bookings.transition', $booking), ['action' => 'collect'])->assertRedirect();
    $this->post(route('bookings.transition', $booking), ['action' => 'deliver'])->assertStatus(422);
    $this->actingAs($client)->post(route('bookings.transition', $booking), ['action' => 'deliver'])->assertRedirect();
    expect($booking->fresh()->status)->toBe('delivered');
});

it('protects ownership and roles', function () {
    $client = marketUser();
    $driver = marketUser('driver');
    $other = marketUser();
    $trip = marketTrip($driver);
    $this->actingAs($client)->post(route('bookings.store', $trip), parcelData());
    $booking = Booking::firstOrFail();
    $this->actingAs($other)->get(route('bookings.show', $booking))->assertForbidden();
    $this->post(route('bookings.transition', $booking), ['action' => 'cancel'])->assertForbidden();
    $this->post(route('bookings.message', $booking), ['body' => 'Intrusion'])->assertForbidden();
    $this->get('/administration')->assertForbidden();
    $this->get('/trajets/nouveau')->assertForbidden();
    $this->actingAs($client)->post(route('bookings.transition', $booking), ['action' => 'accept'])->assertStatus(422);
    $this->actingAs($driver)->post(route('bookings.store', $trip), parcelData())->assertForbidden();
});

it('prevents overselling and returns capacity after cancellation', function () {
    $client = marketUser();
    $trip = marketTrip(marketUser('driver'));
    $this->actingAs($client)->post(route('bookings.store', $trip), parcelData());
    $this->post(route('bookings.store', $trip), array_replace(parcelData(), ['weight_kg' => 6]))->assertSessionHasErrors('weight_kg');
    expect(Booking::count())->toBe(1);
    $this->post(route('bookings.transition', Booking::first()), ['action' => 'cancel'])->assertRedirect();
    expect($trip->remainingKg())->toBe(10);
});

it('blocks pending accounts, missing declarations and disabled demo payments', function () {
    $trip = marketTrip(marketUser('driver'));
    $this->actingAs(marketUser('client', 'pending'))->post(route('bookings.store', $trip), parcelData())->assertForbidden();
    $this->actingAs(marketUser())->post(route('bookings.store', $trip), array_replace(parcelData(), ['declaration' => 0]))->assertSessionHasErrors('declaration');
    $this->post(route('bookings.store', $trip), parcelData());
    $booking = Booking::firstOrFail();
    $booking->update(['status' => 'accepted']);
    config(['marketplace.demo' => false]);
    $this->post(route('bookings.pay', $booking))->assertForbidden();
});

it('stores application files privately and permits only admin review', function () {
    Storage::fake('local');
    $candidate = marketUser();
    $admin = marketUser('admin');
    $this->actingAs($candidate)->post(route('drivers.store'), ['business_type' => 'particulier', 'experience' => 'Je voyage régulièrement entre Paris et Alger.', 'identity' => UploadedFile::fake()->create('identite.pdf', 20, 'application/pdf'), 'insurance' => UploadedFile::fake()->create('assurance.pdf', 20, 'application/pdf'), 'license' => UploadedFile::fake()->create('permis.pdf', 20, 'application/pdf'), 'agreement' => 1])->assertRedirect('/dashboard');
    $application = DriverApplication::firstOrFail();
    Storage::disk('local')->assertExists($application->documents['identity']);
    $this->get(route('admin.document', [$application, 'identity']))->assertForbidden();
    $this->post(route('admin.application', $application), ['status' => 'approved', 'review_note' => 'Dossier examiné'])->assertForbidden();
    $this->actingAs($admin)->get('/administration')->assertOk();
    $this->get(route('admin.document', [$application, 'identity']))->assertOk();
    $this->post(route('admin.application', $application), ['status' => 'approved', 'review_note' => 'Dossier fictif examiné pour test'])->assertRedirect();
    expect($candidate->fresh()->role)->toBe('driver');
    $this->actingAs($candidate->fresh())->get('/trajets/nouveau')->assertOk();
});

it('allows an approved driver to publish and close their own trip', function () {
    $driver = marketUser('driver');
    $this->actingAs($driver)->post(route('trips.store'), ['origin' => 'Lyon', 'destination' => 'Oran', 'country' => 'Algérie', 'departure_date' => today()->addDays(4)->format('Y-m-d'), 'arrival_date' => today()->addDays(6)->format('Y-m-d'), 'pickup_points' => 'Part-Dieu', 'conditions' => 'Livres seulement', 'price' => 7.5, 'capacity_kg' => 20])->assertRedirect();
    $trip = Trip::firstOrFail();
    expect($trip->price_cents)->toBe(750);
    $this->get('/dashboard')->assertOk()->assertSee('Lyon');
    $this->actingAs(marketUser('driver'))->post(route('trips.close', $trip))->assertForbidden();
    $this->actingAs($driver)->post(route('trips.close', $trip))->assertRedirect();
    $this->get(route('trips.show', $trip))->assertNotFound();
});
