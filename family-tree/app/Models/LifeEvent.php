<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LifeEvent extends Model
{
    use BelongsToFamily;

    public const TYPES = [
        'birth',
        'death',
        'marriage',
        'divorce',
        'migration',
        'education',
        'occupation',
        'military_service',
        'census',
        'other',
    ];

    protected $fillable = [
        'family_id',
        'event_type',
        'event_date',
        'event_date_precision',
        'event_date_text',
        'event_date_range_end',
        'place_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_date_range_end' => 'date',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'life_event_people')
            ->withPivot('role');
    }

    public function citations(): MorphMany
    {
        return $this->morphMany(Citation::class, 'citable');
    }

    public function mediaLinks(): MorphMany
    {
        return $this->morphMany(MediaLink::class, 'linkable');
    }

    public function formattedDate(): string
    {
        if ($this->event_date_text) {
            return $this->event_date_text;
        }

        if (! $this->event_date) {
            return 'Date unknown';
        }

        $year = $this->event_date->format('Y');

        return match ($this->event_date_precision) {
            'exact' => $this->event_date->format('j M Y'),
            'month' => $this->event_date->format('M Y'),
            'year' => $year,
            'approximate', 'circa' => 'c. '.$year,
            'before' => 'bef. '.$year,
            'after' => 'aft. '.$year,
            'range' => $year.($this->event_date_range_end ? '–'.$this->event_date_range_end->format('Y') : ''),
            default => $year,
        };
    }

    public function typeLabel(): string
    {
        return str_replace('_', ' ', $this->event_type);
    }
}
