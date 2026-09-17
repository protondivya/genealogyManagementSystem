<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaLink extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'media_item_id',
        'linkable_type',
        'linkable_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
