<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripePayments
{
    public function configured(): bool
    {
        $key = (string) config('payments.secret');

        return (bool) config('payments.enabled') && filled(config('payments.webhook_secret')) &&
          (config('payments.live') ? str_starts_with($key, 'sk_live_') || str_starts_with($key, 'rk_live_') : str_starts_with($key, 'sk_test_') || str_starts_with($key, 'rk_test_'));
    }

    public function client(): StripeClient
    {
        abort_unless($this->configured(), 503, 'Le paiement en ligne n’est pas encore disponible.');

        return new StripeClient(['api_key' => config('payments.secret'), 'stripe_version' => config('payments.api_version')]);
    }

    public function ready(User $driver): bool
    {
        if (! $driver->stripe_account_id) {
            return false;
        }
        $account = $this->client()->v2->core->accounts->retrieve($driver->stripe_account_id, ['include' => ['configuration.recipient']]);

        return data_get($account->toArray(), 'configuration.recipient.capabilities.stripe_balance.stripe_transfers.status') === 'active';
    }

    public function checkout(Booking $booking): Session
    {
        if ($booking->trip->driver->account_status !== 'approved' || ! $this->ready($booking->trip->driver)) {
            throw ValidationException::withMessages(['payment' => 'Le livreur doit terminer la vérification de son compte de paiement.']);
        }
        $line = function (string $name, int $amount): array {
            return ['price_data' => ['currency' => 'eur', 'product_data' => ['name' => $name], 'unit_amount' => $amount], 'quantity' => 1];
        };

        return $this->client()->checkout->sessions->create([
            'mode' => 'payment', 'locale' => 'fr', 'client_reference_id' => $booking->reference,
            'integration_identifier' => config('payments.integration_identifier'),
            'customer_email' => $booking->client->email,
            'line_items' => [$line('Transport '.$booking->reference, $booking->transport_cents), $line('Frais de service ColisFirst', $booking->fee_cents)],
            'metadata' => ['booking_id' => (string) $booking->id, 'reference' => $booking->reference],
            'payment_intent_data' => ['application_fee_amount' => $booking->fee_cents, 'transfer_data' => ['destination' => $booking->trip->driver->stripe_account_id], 'metadata' => ['booking_id' => (string) $booking->id]],
            'success_url' => route('bookings.show', $booking).'?payment=return',
            'cancel_url' => route('bookings.show', $booking).'?payment=cancelled',
        ], ['idempotency_key' => 'booking-'.$booking->id.'-'.($booking->stripe_session_id ?? 'initial')]);
    }

    public function onboarding(User $driver): string
    {
        $client = $this->client();
        if (! $driver->stripe_account_id) {
            $account = $client->v2->core->accounts->create([
                'contact_email' => $driver->email, 'display_name' => $driver->name, 'dashboard' => 'express',
                'identity' => ['country' => 'FR'],
                'defaults' => ['responsibilities' => ['fees_collector' => 'application', 'losses_collector' => 'application']],
                'configuration' => ['recipient' => ['capabilities' => ['stripe_balance' => ['stripe_transfers' => ['requested' => true]]]]],
                'metadata' => ['colisfirst_user_id' => (string) $driver->id],
            ], ['idempotency_key' => 'colisfirst-driver-'.$driver->id]);
            $driver->stripe_account_id = $account->id;
            $driver->save();
        }
        $link = $client->v2->core->accountLinks->create(['account' => $driver->stripe_account_id, 'use_case' => ['type' => 'account_onboarding', 'account_onboarding' => ['configurations' => ['recipient'], 'refresh_url' => route('payments.onboarding.page'), 'return_url' => route('payments.onboarding.page')]]]);

        return $link->url;
    }
}
