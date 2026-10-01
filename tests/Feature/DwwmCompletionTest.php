<?php

use App\Models\AccountAlert;
use App\Models\Booking;
use App\Models\SupportTicket;

it('allows editing an available own trip but protects existing reservations', function () {
    $driver = marketUser('driver');
    $trip = marketTrip($driver);
    $data = ['origin' => 'Lyon', 'destination' => 'Oran', 'country' => 'Algérie', 'departure_date' => today()->addDays(5)->format('Y-m-d'), 'arrival_date' => today()->addDays(7)->format('Y-m-d'), 'pickup_points' => 'Gare publique à 10 h', 'conditions' => 'Livres et vêtements uniquement', 'price' => 9, 'capacity_kg' => 20];
    $this->actingAs($driver)->get(route('trips.edit', $trip))->assertOk()->assertSee('Paris');
    $this->put(route('trips.update', $trip), $data)->assertRedirect();
    expect($trip->fresh()->price_cents)->toBe(900);
    $this->actingAs(marketUser('driver'))->put(route('trips.update', $trip), $data)->assertForbidden();
    $this->actingAs(marketUser())->post(route('bookings.store', $trip), parcelData())->assertRedirect();
    $this->actingAs($driver)->put(route('trips.update', $trip), $data)->assertStatus(422);
    expect($trip->fresh()->capacity_kg)->toBe(20);
});

it('notifies both booking parties and protects notification ownership', function () {
    $client = marketUser();
    $driver = marketUser('driver');
    $trip = marketTrip($driver);
    $this->actingAs($client)->post(route('bookings.store', $trip), parcelData());
    expect(AccountAlert::where('user_id', $client->id)->count())->toBe(1)->and(AccountAlert::where('user_id', $driver->id)->count())->toBe(1);
    $booking = Booking::firstOrFail();
    $alert = AccountAlert::where('user_id', $driver->id)->firstOrFail();
    $this->post(route('alerts.read', $alert))->assertForbidden();
    $this->actingAs($driver)->post(route('bookings.transition', $booking), ['action' => 'accept']);
    $this->get('/notifications')->assertOk()->assertSee($booking->reference);
    $this->post(route('alerts.read', $alert))->assertRedirect();
    expect($alert->fresh()->read_at)->not->toBeNull();
});

it('notifies support recipients and retains accounts with existing records', function () {
    $client = marketUser();
    $admin = marketUser('admin');
    $this->actingAs($client)->post(route('support.store'), ['subject' => 'Demande de renseignements', 'body' => 'Je souhaite une précision sur mon colis.']);
    $ticket = SupportTicket::firstOrFail();
    expect(AccountAlert::where('user_id', $admin->id)->count())->toBe(1);
    $this->actingAs($admin)->post(route('support.reply', $ticket), ['body' => 'Voici notre réponse.']);
    expect(AccountAlert::where('user_id', $client->id)->count())->toBe(1);
    $this->actingAs($client)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect(route('privacy.requests'));
    expect($client->fresh())->not->toBeNull();
    $this->assertAuthenticatedAs($client);
});
