<?php

namespace App\Models;

use Database\Factories\CorrectionErrorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionError extends Model
{
    /** @use HasFactory<CorrectionErrorFactory> */
    use HasFactory;

    protected $table = 'errors';

    protected $fillable = ['type', 'original', 'replacement', 'explanation'];

    public function correction(): BelongsTo
    {
        return $this->belongsTo(Correction::class);
    }
}
