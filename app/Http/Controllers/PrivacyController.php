<?php

namespace App\Http\Controllers;

use App\Models\AccountAlert;
use App\Models\AccountDocument;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\PrivacyRequest;
use App\Models\SupportReply;
use App\Models\SupportTicket;
use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyController extends Controller
{
    public function index(Request $request): View
    {
        return view('market.privacy-requests', ['requests' => PrivacyRequest::where('user_id', $request->user()->id)->latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['type' => 'required|in:access,erase,correct,limit,oppose,portability', 'details' => 'nullable|string|max:2000']);
        PrivacyRequest::create($data + ['user_id' => $request->user()->id, 'due_at' => now()->addMonthNoOverflow()]);

        return back()->with('success', 'Votre demande a été enregistrée. Vous pouvez suivre sa réponse dans cet espace.');
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        // Exclure les secrets et les données privées des autres comptes.
        $data = [
            'account_details' => $user->account_details,
            'notifications' => AccountAlert::where('user_id', $user->id)->get(['title', 'read_at', 'created_at']),
            'documents' => AccountDocument::where('user_id', $user->id)->get(['kind', 'status', 'expires_at', 'review_note', 'created_at', 'updated_at']),
            'support_tickets' => SupportTicket::where('user_id', $user->id)->get(['id', 'subject', 'status', 'created_at']),
            'support_messages_authored' => SupportReply::where('user_id', $user->id)->get(['support_ticket_id', 'body', 'created_at']),
            'profile' => $user->only(['name', 'email', 'phone', 'city', 'role', 'account_status', 'created_at', 'terms_accepted_at']),
            'bookings' => Booking::where('user_id', $user->id)->get(['reference', 'weight_kg', 'contents', 'declared_value_cents', 'total_cents', 'status', 'payment_status', 'created_at']),
            'trips' => Trip::where('user_id', $user->id)->get(['origin', 'destination', 'country', 'departure_date', 'arrival_date', 'pickup_points', 'conditions', 'price_cents', 'capacity_kg', 'status']),
            'messages_authored' => BookingMessage::where('user_id', $user->id)->get(['booking_id', 'body', 'created_at']),
            'privacy_requests' => PrivacyRequest::where('user_id', $user->id)->get(['type', 'details', 'status', 'response', 'created_at']),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }, 'mes-donnees-colisfirst.json', ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store']);
    }

    public function review(Request $request, PrivacyRequest $privacyRequest): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin' && $request->user()->account_status === 'approved', 403);
        $data = $request->validate(['response' => 'required|string|min:10|max:5000', 'status' => 'required|in:processing,completed']);
        $privacyRequest->update($data + ['resolved_at' => $data['status'] === 'completed' ? now() : null]);

        return back()->with('success', 'La réponse est visible dans l’espace de la personne concernée.');
    }

    public function cookies(Request $request): RedirectResponse
    {
        $request->validate(['choice' => 'required|in:essential']);

        return back()->withCookie(cookie('cf_cookie_notice', 'essential-v1', 60 * 24 * 180, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
