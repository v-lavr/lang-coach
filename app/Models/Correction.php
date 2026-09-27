<?php

namespace App\Models;

use Database\Factories\CorrectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Correction extends Model
{
    /** @use HasFactory<CorrectionFactory> */
    use HasFactory;

    protected $fillable = ['original_text', 'corrected_text'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(CorrectionError::class);
    }
}
