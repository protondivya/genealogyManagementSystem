<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Citation extends Model
{
    protected $fillable = [
        'source_id',
        'family_id',
        'citable_type',
        'citable_id',
        'field_name',
        'confidence_level',
        'notes',
        'created_by',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function citable(): MorphTo
    {
        return $this->morphTo();
    }
}
