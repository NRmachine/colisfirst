<?php

use App\Models\User;
use App\Notifications\AccountVerification as VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});

test('registration queues the confirmation notification', function () {
    Notification::fake();
    $this->post('/register', ['name' => 'Nouveau Client', 'email' => 'confirmation@example.test', 'phone' => '0600000000', 'city' => 'Paris', 'password' => 'SecureDemo123!', 'password_confirmation' => 'SecureDemo123!', 'terms' => 1])->assertRedirect('/dashboard');
    $user = User::where('email', 'confirmation@example.test')->firstOrFail();
    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    expect($user->hasVerifiedEmail())->toBeFalse();
});
