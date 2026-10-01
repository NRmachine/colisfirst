<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyRequest extends Model
{
    protected $guarded = ['id'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    /** @return BelongsTo<User,$this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return ['access' => 'Accès', 'erase' => 'Effacement', 'correct' => 'Rectification', 'limit' => 'Limitation', 'oppose' => 'Opposition', 'portability' => 'Portabilité'][$this->type] ?? $this->type;
    }
}
