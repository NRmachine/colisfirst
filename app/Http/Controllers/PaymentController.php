<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Services\StripePayments;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function checkout(Request $request, Booking $booking, StripePayments $payments): RedirectResponse
    {
        abort_unless($request->user()->id === $booking->user_id && $request->user()->account_status === 'approved' && $request->user()->hasVerifiedEmail(), 403);
        abort_unless($payments->configured(), 503, 'Le paiement en ligne n’est pas encore disponible.');
        try {
            $url = DB::transaction(function () use ($booking, $payments) {
                $booking = Booking::lockForUpdate()->findOrFail($booking->id);
                abort_unless(in_array($booking->status, ['accepted', 'payment_pending']) && in_array($booking->payment_status, ['unpaid', 'processing']), 422);
                // Réutiliser la session de paiement existante.
                if ($booking->stripe_session_id) {
                    $session = $payments->client()->checkout->sessions->retrieve($booking->stripe_session_id, []);
                    if ($session->status === 'open') {
                        return $session->url;
                    }
                    abort_unless(($session->status === 'expired' || ($session->status === 'complete' && $session->payment_status === 'unpaid' && $booking->payment_status === 'unpaid')), 422, 'Le paiement est en cours de confirmation.');
                }
                abort_unless($booking->trip->departure_date->isFuture(), 422, 'La date de départ est passée.');
                $session = $payments->checkout($booking);
                $booking->update(['status' => 'payment_pending', 'payment_status' => 'processing', 'stripe_session_id' => $session->id, 'stripe_checkout_url' => $session->url, 'stripe_expires_at' => date('Y-m-d H:i:s', $session->expires_at), 'stripe_destination' => $booking->trip->driver->stripe_account_id, 'stripe_livemode' => $session->livemode]);

                return $session->url;
            });

            return redirect()->away($url);
        } catch (ApiErrorException $exception) {
            // Ne pas exposer les réponses privées de Stripe.
            return back()->withErrors(['payment' => 'Le paiement est momentanément indisponible. Réessayez dans quelques instants.']);
        }
    }

    public function onboardingPage(Request $request, StripePayments $payments): View
    {
        abort_unless($request->user()->role === 'driver' && $request->user()->account_status === 'approved', 403);
        $ready = false;
        $available = $payments->configured();
        if ($available) {
            try {
                $ready = $payments->ready($request->user());
            } catch (ApiErrorException $e) {
                $available = false;
            }
        }

        return view('market.payment-account', compact('ready', 'available'));
    }

    public function onboarding(Request $request, StripePayments $payments): RedirectResponse
    {
        abort_unless($request->user()->role === 'driver' && $request->user()->account_status === 'approved' && $request->user()->hasVerifiedEmail(), 403);
        try {
            $url = DB::transaction(function () use ($request, $payments) {
                return $payments->onboarding(User::lockForUpdate()->findOrFail($request->user()->id));
            });

            return redirect()->away($url);
        } catch (ApiErrorException $e) {
            return back()->withErrors(['payment' => 'La configuration des versements n’est pas disponible. Contactez l’administration.']);
        }
    }
}
