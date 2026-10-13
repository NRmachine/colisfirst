<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DriverApplication;
use App\Models\Trip;
use App\Services\SearchHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketplaceController extends Controller
{
    public function home(): View
    {
        $trips = $this->availableTrips()->with('driver')->orderBy('departure_date')->take(3)->get();

        return view('market.home', compact('trips'));
    }

    /** @return Builder<Trip> */
    private function availableTrips(): Builder
    {
        return Trip::where('status', 'published')->whereDate('departure_date', '>=', today())->whereHas('driver', fn ($query) => $query->where('role', 'driver')->where('account_status', 'approved'));
    }

    public function trips(Request $request, SearchHistory $history): View
    {
        $filters = $request->validate(['origin' => 'nullable|string|max:100', 'country' => 'nullable|in:Algérie,Maroc,Tunisie', 'date' => 'nullable|date', 'sort' => 'nullable|in:date,price']);
        $query = $this->availableTrips()->with('driver');
        if ($request->filled('origin')) {
            $query->where('origin', 'like', '%'.$filters['origin'].'%');
        }
        if ($request->filled('country')) {
            $query->where('country', $filters['country']);
        }
        if ($request->filled('date')) {
            $query->whereDate('departure_date', '>=', $filters['date']);
        }
        $trips = $query->orderBy($request->input('sort') === 'price' ? 'price_cents' : 'departure_date')->paginate(9)->withQueryString();

        $recentSearches = [];
        if (config('search_history.enabled')) {
            try {
                if (! $request->has('page')) {
                    $history->remember($request->session()->getId(), $filters);
                }
                $recentSearches = $history->recent($request->session()->getId());
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return view('market.trips', compact('trips', 'recentSearches'));
    }

    public function trip(Trip $trip): View
    {
        abort_unless($trip->status === 'published' && $trip->driver->account_status === 'approved', 404);

        return view('market.trip', compact('trip'));
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $bookings = Booking::with(['trip.driver', 'client'])->where(function ($query) use ($user) {
            $query->where('user_id', $user->id);
            if ($user->role === 'driver') {
                $query->orWhereHas('trip', fn ($t) => $t->where('user_id', $user->id));
            }
        })->latest()->paginate(15);
        $trips = Trip::where('user_id', $user->id)->latest()->get();
        $application = DriverApplication::where('user_id', $user->id)->first();

        return view('market.dashboard', compact('user', 'bookings', 'trips', 'application'));
    }

    private function approved(Request $request): void
    {
        abort_unless($request->user()->account_status === 'approved', 403, 'Votre compte doit être validé par un administrateur.');
    }

    private function driver(Request $request): void
    {
        $this->approved($request);
        abort_unless($request->user()->role === 'driver', 403);
    }

    public function newTrip(Request $request): View
    {
        $this->driver($request);

        return view('market.trip-form');
    }

    public function storeTrip(Request $request): RedirectResponse
    {
        $this->driver($request);
        $validated = $request->validate(['origin' => 'required|string|max:100', 'destination' => 'required|string|max:100', 'country' => 'required|in:Algérie,Maroc,Tunisie', 'departure_date' => 'required|date|after:today', 'arrival_date' => 'required|date|after_or_equal:departure_date', 'pickup_points' => 'required|string|max:1000', 'conditions' => 'required|string|max:2000', 'price' => 'required|numeric|min:1|max:100', 'capacity_kg' => 'required|integer|min:1|max:1000']);
        $validated['price_cents'] = (int) round($validated['price'] * 100);
        unset($validated['price']);
        $validated['user_id'] = $request->user()->id;
        $trip = Trip::create($validated);

        return redirect()->route('trips.show', $trip)->with('success', 'Votre trajet est publié.');
    }

    public function closeTrip(Request $request, Trip $trip): RedirectResponse
    {
        $this->driver($request);
        abort_unless($trip->user_id === $request->user()->id, 403);
        $trip->update(['status' => 'closed']);

        return back()->with('success', 'Annonce fermée aux nouvelles réservations. Vos réservations existantes restent accessibles.');
    }

    public function book(Request $request, Trip $trip): RedirectResponse
    {
        $this->approved($request);
        abort_if($trip->user_id === $request->user()->id, 403);
        $validated = $request->validate(['weight_kg' => 'required|integer|min:1|max:1000', 'contents' => 'required|string|min:10|max:2000', 'declared_value' => 'required|numeric|min:0|max:5000', 'recipient_name' => 'required|string|max:120', 'recipient_phone' => 'required|string|max:30', 'recipient_address' => 'required|string|max:500', 'declaration' => 'accepted']);
        $booking = DB::transaction(function () use ($request, $trip, $validated) {
            // Verrouiller la capacité pendant la réservation.
            Trip::whereKey($trip->id)->update(['updated_at' => now()]);
            $trip = Trip::lockForUpdate()->findOrFail($trip->id);
            abort_unless($trip->status === 'published' && $trip->departure_date->isFuture() && $trip->driver->account_status === 'approved', 422, 'Ce trajet ne peut plus être réservé.');
            if ($validated['weight_kg'] > $trip->remainingKg()) {
                throw ValidationException::withMessages(['weight_kg' => 'La capacité restante est insuffisante.']);
            }
            $transport = $trip->price_cents * $validated['weight_kg'];
            $fee = (int) round($transport * config('marketplace.commission_percent') / 100);

            return Booking::create(['reference' => 'CF-'.strtoupper(Str::random(10)), 'user_id' => $request->user()->id, 'trip_id' => $trip->id, 'weight_kg' => $validated['weight_kg'], 'contents' => $validated['contents'], 'declared_value_cents' => (int) round($validated['declared_value'] * 100), 'recipient_name' => $validated['recipient_name'], 'recipient_phone' => $validated['recipient_phone'], 'recipient_address' => $validated['recipient_address'], 'transport_cents' => $transport, 'fee_cents' => $fee, 'total_cents' => $transport + $fee, 'declared_at' => now(), 'declaration_version' => config('marketplace.declaration_version')]);
        });

        return redirect()->route('bookings.show', $booking)->with('success', 'Demande envoyée au livreur. Aucun paiement effectué.');
    }

    private function access(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id || $booking->trip->user_id === $request->user()->id || $request->user()->role === 'admin', 403);
    }

    public function booking(Request $request, Booking $booking): View
    {
        $this->access($request, $booking);
        $booking->load(['trip.driver', 'client', 'messages.user']);

        return view('market.booking', compact('booking'));
    }

    public function transition(Request $request, Booking $booking): RedirectResponse
    {
        $this->access($request, $booking);
        $this->approved($request);
        $action = $request->validate(['action' => 'required|in:accept,reject,cancel,collect,deliver'])['action'];
        DB::transaction(function () use ($request, $booking, $action) {
            $lockedBooking = Booking::lockForUpdate()->findOrFail($booking->id);
            $isDriver = $lockedBooking->trip->user_id === $request->user()->id;
            $allowed = match ($action) {
                'accept','reject' => $isDriver && $lockedBooking->status === 'pending','collect' => $isDriver && $lockedBooking->status === 'confirmed','deliver' => $lockedBooking->user_id === $request->user()->id && $lockedBooking->status === 'collected','cancel' => $lockedBooking->user_id === $request->user()->id && in_array($lockedBooking->status, ['pending', 'accepted']),default => false
            };
            abort_unless($allowed, 422, 'Cette action n’est pas disponible à cette étape.');
            $lockedBooking->update(['status' => ['accept' => 'accepted', 'reject' => 'rejected', 'cancel' => 'cancelled', 'collect' => 'collected', 'deliver' => 'delivered'][$action]]);
        });

        return back()->with('success', 'Le suivi du colis a été mis à jour.');
    }

    public function pay(Request $request, Booking $booking): RedirectResponse
    {
        $this->approved($request);
        abort_unless(config('marketplace.demo') && ! app()->isProduction(), 403, 'Les paiements réels ne sont pas encore disponibles.');
        abort_unless($booking->user_id === $request->user()->id, 403);
        DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::lockForUpdate()->findOrFail($booking->id);
            abort_unless($lockedBooking->status === 'accepted' && $lockedBooking->payment_status === 'unpaid', 422);
            $lockedBooking->update(['payment_status' => 'simulated', 'status' => 'confirmed']);
        });

        return back()->with('success', 'Paiement simulé : aucun argent n’a été débité.');
    }

    public function message(Request $request, Booking $booking): RedirectResponse
    {
        $this->access($request, $booking);
        $this->approved($request);
        abort_unless(in_array($booking->status, ['confirmed', 'collected', 'delivered']), 403);
        $validated = $request->validate(['body' => 'required|string|max:2000']);
        $booking->messages()->create(['user_id' => $request->user()->id, 'body' => $validated['body']]);

        return back()->with('success', 'Message envoyé.');
    }

    public function apply(Request $request): View
    {
        $application = DriverApplication::where('user_id', $request->user()->id)->first();

        return view('market.apply', compact('application'));
    }

    public function storeApplication(Request $request): RedirectResponse
    {
        abort_if(DriverApplication::where('user_id', $request->user()->id)->whereIn('status', ['pending', 'approved'])->exists(), 422, 'Une candidature est déjà en cours ou validée.');
        $validated = $request->validate(['business_type' => 'required|in:particulier,professionnel', 'business_number' => 'nullable|required_if:business_type,professionnel|digits:14', 'experience' => 'required|string|min:20|max:2000', 'identity' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096', 'insurance' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096', 'license' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096', 'business' => 'nullable|required_if:business_type,professionnel|file|mimes:pdf,jpg,jpeg,png|max:4096', 'agreement' => 'accepted']);
        $documents = [];
        foreach (['identity', 'insurance', 'license', 'business'] as $kind) {
            if ($request->hasFile($kind)) {
                $documents[$kind] = $request->file($kind)->store('applications/'.$request->user()->id, config('filesystems.private_documents'));
            }
        }
        $old = DriverApplication::where('user_id', $request->user()->id)->first();
        DriverApplication::updateOrCreate(['user_id' => $request->user()->id], ['status' => 'pending', 'business_type' => $validated['business_type'], 'business_number' => $validated['business_number'] ?? null, 'experience' => $validated['experience'], 'documents' => $documents, 'review_note' => null, 'reviewed_at' => null]);
        if ($old) {
            foreach ($old->documents as $path) {
                if (str_starts_with($path, 'applications/'.$request->user()->id.'/')) {
                    Storage::disk(config('filesystems.private_documents'))->delete($path);
                }
            }
        }

        return redirect()->route('dashboard')->with('success', 'Candidature reçue. Un administrateur doit examiner votre dossier avant toute publication.');
    }

    public function profile(Request $request): View
    {
        return view('market.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:120', 'phone' => 'required|string|max:30', 'city' => 'required|string|max:100']);
        $user = $request->user();
        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->city = $validated['city'];
        $user->save();

        return back()->with('success', 'Profil mis à jour.');
    }
}
