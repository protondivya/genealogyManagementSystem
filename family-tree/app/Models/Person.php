<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'family_id',
        'first_name',
        'middle_name',
        'last_name',
        'maiden_name',
        'gender',
        'birth_date',
        'birth_date_precision',
        'birth_place_id',
        'death_date',
        'death_date_precision',
        'death_place_id',
        'is_living',
        'profile_photo_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'is_living' => 'boolean',
    ];

    protected $appends = [
        'full_name',
        'life_span',
        'photo_url',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function birthPlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'birth_place_id');
    }

    public function deathPlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'death_place_id');
    }

    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'person_one_id');
    }

    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'person_two_id');
    }

    public function citations(): MorphMany
    {
        return $this->morphMany(Citation::class, 'citable');
    }

    public function mediaLinks(): MorphMany
    {
        return $this->morphMany(MediaLink::class, 'linkable');
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' ');
    }

    public function getLifeSpanAttribute(): string
    {
        $birth = $this->formattedYear($this->birth_date, $this->birth_date_precision);
        $death = $this->is_living
            ? ''
            : $this->formattedYear($this->death_date, $this->death_date_precision);

        if ($birth && $death) {
            return $birth.' – '.$death;
        }

        if ($birth && $this->is_living) {
            return 'b. '.$birth;
        }

        if ($death) {
            return 'd. '.$death;
        }

        return '';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return route('media.serve', ['path' => $this->profile_photo_path]);
    }

    public function formattedYear($date, ?string $precision): string
    {
        if (! $date) {
            return $precision && $precision !== 'unknown' ? '?' : '';
        }

        $year = $date->format('Y');

        return match ($precision) {
            'approximate', 'circa' => 'c. '.$year,
            'before' => 'bef. '.$year,
            'after' => 'aft. '.$year,
            'year', 'month', 'exact', 'range' => $year,
            default => $year,
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('first_name', 'like', $like)
                ->orWhere('middle_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('maiden_name', 'like', $like);
        });
    }

    public function toPublicArray(bool $redactLiving = false): array
    {
        $redact = $redactLiving && $this->is_living;

        return [
            'id' => $this->id,
            'family_id' => $this->family_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'maiden_name' => $this->maiden_name,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'birth_date' => $redact ? null : optional($this->birth_date)?->toDateString(),
            'birth_date_precision' => $this->birth_date_precision,
            'birth_place' => $redact ? null : $this->birthPlace?->label(),
            'death_date' => optional($this->death_date)?->toDateString(),
            'death_date_precision' => $this->death_date_precision,
            'death_place' => $this->deathPlace?->label(),
            'is_living' => $this->is_living,
            'notes' => $redact ? null : $this->notes,
            'photo_url' => $redact ? null : $this->photo_url,
            'life_span' => $redact ? '' : $this->life_span,
        ];
    }
}
