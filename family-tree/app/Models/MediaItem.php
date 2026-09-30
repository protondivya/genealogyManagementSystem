<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaItem extends Model
{
    use BelongsToFamily;

    protected $fillable = [
        'family_id',
        'file_path',
        'file_type',
        'original_name',
        'caption',
        'uploaded_by',
    ];

    protected $appends = ['url'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function links(): HasMany
    {
        return $this->hasMany(MediaLink::class);
    }

    public function getUrlAttribute(): string
    {
        return route('media.serve', ['path' => $this->file_path]);
    }
}
