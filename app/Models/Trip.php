<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $departure_date
 * @property Carbon $arrival_date
 */
class Trip extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['departure_date' => 'date', 'arrival_date' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function remainingKg(): int
    {
        return max(0, $this->capacity_kg - (int) $this->bookings()->whereNotIn('status', ['cancelled', 'rejected'])->sum('weight_kg'));
    }
}
