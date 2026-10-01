<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SupportReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class);
    }

    public function statusLabel(): string
    {
        return ['open' => 'Ouvert', 'answered' => 'Réponse du support', 'closed' => 'Clôturé'][$this->status] ?? $this->status;
    }
}
