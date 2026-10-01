<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\DriverApplication;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('Les comptes de test sont interdits en production.');
        }
        foreach ([
            ['admin', 'Nadia Admin', 'admin', 'approved', 'Paris'],
            ['client', 'Sarah Martin', 'client', 'approved', 'Paris'],
            ['livreur', 'Karim Benali', 'driver', 'approved', 'Paris'],
            ['livreur2', 'Amine Mansouri', 'driver', 'approved', 'Lyon'],
            ['livreur3', 'Inès Haddad', 'driver', 'approved', 'Marseille'],
            ['candidat', 'Yanis Démonstration', 'client', 'pending', 'Lille'],
        ] as [$key,$name,$role,$status,$city]) {
            $u = User::firstOrNew(['email' => $key.'@colisfirst.test']);
            $u->name = $name;
            $u->password = Hash::make('DemoColis2026!');
            $u->role = $role;
            $u->account_status = $status;
            $u->phone = '0600000000';
            $u->city = $city;
            $u->terms_accepted_at = now();
            $u->email_verified_at = now();
            $u->save();
        }
        $demoFile = 'applications/demo/justificatif-fictif.txt';
        Storage::disk('local')->put($demoFile, 'DOCUMENT FICTIF — DÉMONSTRATION DWWM — AUCUNE VALIDITÉ.');
        foreach (['livreur', 'livreur2', 'livreur3', 'candidat'] as $key) {
            DriverApplication::firstOrCreate(['user_id' => User::where('email', $key.'@colisfirst.test')->firstOrFail()->id], [
                'status' => $key === 'candidat' ? 'pending' : 'approved', 'business_type' => 'particulier', 'experience' => 'Profil fictif pour tester les trajets et le parcours de validation. Aucun transport réel.',
                'documents' => ['identity' => $demoFile, 'insurance' => $demoFile, 'license' => $demoFile],
                'review_note' => $key === 'candidat' ? null : 'Dossier fictif approuvé uniquement pour la démonstration.',
            ]);
        }
        $first = null;
        foreach ([
            ['livreur', 'Paris', 'Alger', 'Algérie', 3, 800, 60, 'Paris Bercy · 09 h, puis Orly · 11 h'],
            ['livreur2', 'Lyon', 'Oran', 'Algérie', 5, 700, 45, 'Lyon Part-Dieu · 10 h'],
            ['livreur3', 'Marseille', 'Tunis', 'Tunisie', 7, 600, 80, 'Marseille Saint-Charles · 14 h'],
            ['livreur', 'Paris', 'Casablanca', 'Maroc', 10, 900, 40, 'Paris Bercy · 08 h'],
            ['livreur2', 'Lyon', 'Tanger', 'Maroc', 12, 750, 35, 'Lyon Perrache · 09 h'],
            ['livreur3', 'Marseille', 'Annaba', 'Algérie', 15, 650, 50, 'Marseille Saint-Charles · 11 h'],
        ] as [$key,$origin,$destination,$country,$days,$price,$capacity,$pickup]) {
            $trip = Trip::firstOrCreate(['user_id' => User::where('email', $key.'@colisfirst.test')->firstOrFail()->id, 'origin' => $origin, 'destination' => $destination], [
                'country' => $country, 'departure_date' => today()->addDays($days), 'arrival_date' => today()->addDays($days + 2),
                'pickup_points' => $pickup, 'conditions' => 'Démonstration : vêtements et livres uniquement. Carton fermé après vérification, 60 × 40 × 40 cm maximum. Aucun objet dangereux, médicament ou denrée alimentaire.', 'price_cents' => $price, 'capacity_kg' => $capacity,
            ]);
            $first ??= $trip;
        }
        Booking::firstOrCreate(['reference' => 'CF-DEMO2026'], [
            'user_id' => User::where('email', 'client@colisfirst.test')->firstOrFail()->id, 'trip_id' => $first->id, 'weight_kg' => 5, 'contents' => 'Colis fictif : trois pulls et deux livres pour la famille.', 'declared_value_cents' => 6000,
            'recipient_name' => 'Destinataire Démo', 'recipient_phone' => '0000000000', 'recipient_address' => 'Adresse fictive, Alger',
            'transport_cents' => 4000, 'fee_cents' => 480, 'total_cents' => 4480, 'status' => 'pending', 'payment_status' => 'unpaid', 'declaration_version' => '2026-09-v1', 'declared_at' => now(),
        ]);
    }
}
