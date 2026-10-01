<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['declared_at' => 'datetime', 'stripe_livemode' => 'boolean', 'stripe_expires_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    /** @return BelongsTo<Trip, $this> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /** @return BelongsTo<User, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<BookingMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(BookingMessage::class);
    }

    public function statusLabel(): string
    {
        return ['payment_pending' => 'Paiement en cours', 'refunded' => 'Remboursée', 'disputed' => 'Litige de paiement', 'pending' => 'En attente du livreur', 'accepted' => 'Acceptée · paiement attendu', 'confirmed' => 'Confirmée', 'collected' => 'Colis pris en charge', 'delivered' => 'Livrée', 'cancelled' => 'Annulée', 'rejected' => 'Refusée'][$this->status] ?? $this->status;
    }
}
