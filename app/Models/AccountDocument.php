<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $expires_at
 * @property Carbon|null $reviewed_at
 */
class AccountDocument extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['expires_at' => 'date', 'reviewed_at' => 'datetime'];
    }

    /** @return array<string, string> */
    public static function kinds(bool $driver): array
    {
        return ['identity_front' => 'Pièce d’identité — recto', 'identity_back' => 'Pièce d’identité — verso'] + ($driver ? ['insurance' => 'Assurance transport', 'license' => 'Permis de conduire', 'business' => 'Justificatif d’immatriculation de l’activité', 'transport' => 'Autorisation de transport'] : []);
    }

    public function label(): string
    {
        return self::kinds(true)[$this->kind] ?? $this->kind;
    }

    public function statusLabel(): string
    {
        return $this->expires_at && $this->expires_at->isBefore(today()) ? 'Expiré — à remplacer' : (['pending' => 'À examiner', 'approved' => 'Validé', 'rejected' => 'À remplacer'][$this->status] ?? $this->status);
    }
}
