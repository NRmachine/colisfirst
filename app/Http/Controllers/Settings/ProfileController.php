<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\AccountDocument;
use App\Models\Booking;
use App\Models\DriverApplication;
use App\Models\PrivacyRequest;
use App\Models\SupportReply;
use App\Models\SupportTicket;
use App\Models\Trip;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        $hasRecords = Booking::where('user_id', $user->id)->exists()
            || Trip::where('user_id', $user->id)->exists()
            || DriverApplication::where('user_id', $user->id)->exists()
            || AccountDocument::where('user_id', $user->id)->exists()
            || SupportTicket::where('user_id', $user->id)->exists()
            || SupportReply::where('user_id', $user->id)->exists()
            || PrivacyRequest::where('user_id', $user->id)->exists();
        if ($hasRecords) {
            return redirect()->route('privacy.requests')->with('success', 'Votre compte contient des dossiers ou échanges. Déposez une demande d’effacement pour un traitement adapté.');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
