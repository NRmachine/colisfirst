<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless(filled(config('payments.webhook_secret')), 503);
        try {
            $event = Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), config('payments.webhook_secret'));
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }
        if ($event->livemode !== (bool) config('payments.live')) {
            return response('Wrong environment', 400);
        }
        $types = ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed', 'checkout.session.expired', 'charge.refunded', 'charge.dispute.created'];
        if (! in_array($event->type, $types)) {
            return response('Ignored', 200);
        }
        DB::transaction(function () use ($event) {
            if (DB::table('payment_events')->where('stripe_event_id', $event->id)->exists()) {
                return;
            }
            $object = $event->data->object->toArray();
            if (str_starts_with($event->type, 'checkout.session.')) {
                $booking = Booking::where('stripe_session_id', $object['id'])->lockForUpdate()->first();
                // Retry later if a session was created but the local database commit failed.
                if (! $booking && ! data_get($object['metadata'], 'booking_id')) {
                    return;
                }
                abort_unless($booking !== null, 409, 'Session not registered');
                if ($event->type === 'checkout.session.expired' || $event->type === 'checkout.session.async_payment_failed') {
                    if ($booking->status === 'payment_pending') {
                        $booking->update(['status' => 'accepted', 'payment_status' => 'unpaid']);
                    }
                } elseif ($object['payment_status'] === 'paid') {
                    abort_unless($object['currency'] === 'eur' && $object['amount_total'] === $booking->total_cents && $object['client_reference_id'] === $booking->reference && (string) data_get($object['metadata'], 'booking_id') === (string) $booking->id && $booking->stripe_livemode === $event->livemode, 400, 'Payment mismatch');
                    if (in_array($booking->status, ['accepted', 'payment_pending']) && in_array($booking->payment_status, ['unpaid', 'processing'])) {
                        $booking->update(['status' => 'confirmed', 'payment_status' => $event->livemode ? 'paid' : 'test_paid', 'stripe_payment_intent' => $object['payment_intent'], 'paid_at' => now()]);
                    }
                }
            } else {
                if (! filled($object['payment_intent'])) {
                    return;
                }
                $booking = Booking::where('stripe_payment_intent', $object['payment_intent'])->lockForUpdate()->first();
                if ($booking) {
                    if ($event->type === 'charge.dispute.created') {
                        $booking->update(['status' => 'disputed', 'payment_status' => 'disputed']);
                    } elseif ($object['refunded']) {
                        $booking->update(['status' => 'refunded', 'payment_status' => 'refunded']);
                    } else {
                        $booking->update(['payment_status' => 'partially_refunded']);
                    }
                }
            }
            DB::table('payment_events')->insert(['stripe_event_id' => $event->id, 'type' => $event->type, 'created_at' => now(), 'updated_at' => now()]);
        });

        return response('Received', 200);
    }
}
