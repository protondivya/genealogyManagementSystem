<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use BelongsToFamily;

    public const TYPES = [
        'document',
        'interview',
        'certificate',
        'book',
        'website',
        'other',
    ];

    protected $fillable = [
        'family_id',
        'title',
        'type',
        'citation_text',
        'url',
        'created_by',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }
}
