<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripEditController extends Controller
{
    private function authorizeTrip(Request $request, Trip $trip): void
    {
        abort_unless($request->user()->role === 'driver' && $request->user()->account_status === 'approved' && $trip->user_id === $request->user()->id, 403);
        abort_unless($trip->status === 'published' && $trip->departure_date->isFuture(), 422, 'Ce trajet ne peut plus être modifié.');
        abort_if($trip->bookings()->whereNotIn('status', ['cancelled', 'rejected'])->exists(), 422, 'Des réservations sont en cours. Fermez cette annonce et publiez un nouveau trajet pour préserver leurs conditions.');
    }

    public function edit(Request $request, Trip $trip): View
    {
        $this->authorizeTrip($request, $trip);

        return view('market.trip-form', compact('trip'));
    }

    public function update(Request $request, Trip $trip): RedirectResponse
    {
        $this->authorizeTrip($request, $trip);
        $data = $request->validate(['origin' => 'required|string|max:100', 'destination' => 'required|string|max:100', 'country' => 'required|in:Algérie,Maroc,Tunisie', 'departure_date' => 'required|date|after:today', 'arrival_date' => 'required|date|after_or_equal:departure_date', 'pickup_points' => 'required|string|max:1000', 'conditions' => 'required|string|max:2000', 'price' => 'required|numeric|min:1|max:100', 'capacity_kg' => 'required|integer|min:1|max:1000']);
        $data['price_cents'] = (int) round($data['price'] * 100);
        unset($data['price']);
        DB::transaction(function () use ($request, $trip, $data) {
            Trip::whereKey($trip->id)->update(['updated_at' => now()]);
            $locked = Trip::lockForUpdate()->findOrFail($trip->id);
            $this->authorizeTrip($request, $locked);
            $locked->update($data);
        });

        return redirect()->route('trips.show', $trip)->with('success', 'Votre trajet a été mis à jour.');
    }
}
