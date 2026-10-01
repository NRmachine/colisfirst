<?php

namespace App\Models;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        $this->notify($booking, 'Nouvelle demande '.$booking->reference);
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('status')) {
            $this->notify($booking, $booking->reference.' : '.$booking->statusLabel());
        }
    }

    private function notify(Booking $booking, string $title): void
    {
        foreach (array_unique([$booking->user_id, $booking->trip->user_id]) as $id) {
            AccountAlert::create(['user_id' => $id, 'title' => $title, 'url' => route('bookings.show', $booking, false)]);
        }
    }
}
