<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DriverApplication;
use App\Models\PrivacyRequest;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin' && $request->user()->account_status === 'approved', 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('market.admin', ['privacyRequests' => PrivacyRequest::with('user')->latest()->get(), 'users' => User::where('account_status', 'pending')->get(), 'applications' => DriverApplication::with('user')->latest()->get(), 'bookings' => Booking::with(['client', 'trip'])->latest()->take(30)->get(), 'tripCount' => Trip::count(), 'fees' => Booking::where('status', 'delivered')->sum('fee_cents')]);
    }

    public function user(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($user->account_status === 'pending', 422);
        $validated = $request->validate(['status' => 'required|in:approved,rejected']);
        $user->account_status = $validated['status'];
        $user->save();

        return back()->with('success', 'Demande de compte traitée.');
    }

    public function application(Request $request, DriverApplication $application): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['status' => 'required|in:approved,rejected', 'review_note' => 'required|string|min:5|max:1000']);
        DB::transaction(function () use ($application, $validated) {
            $lockedApplication = DriverApplication::lockForUpdate()->findOrFail($application->id);
            abort_unless($lockedApplication->status === 'pending', 422);
            $lockedApplication->update($validated + ['reviewed_at' => now()]);
            if ($validated['status'] === 'approved') {
                $user = $lockedApplication->user;
                $user->role = 'driver';
                $user->account_status = 'approved';
                $user->save();
            }
        });

        return back()->with('success', 'Décision enregistrée.');
    }

    public function document(Request $request, DriverApplication $application, string $kind): StreamedResponse
    {
        $this->authorizeAdmin($request);
        $path = $application->documents[$kind] ?? null;
        abort_unless($path && Storage::disk(config('filesystems.private_documents'))->exists($path), 404);

        return Storage::disk(config('filesystems.private_documents'))->download($path, $kind.'.'.pathinfo($path, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff']);
    }
}
