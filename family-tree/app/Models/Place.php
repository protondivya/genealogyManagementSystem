<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    protected $fillable = [
        'name',
        'country',
        'region',
        'latitude',
        'longitude',
    ];

    public function birthPeople(): HasMany
    {
        return $this->hasMany(Person::class, 'birth_place_id');
    }

    public function deathPeople(): HasMany
    {
        return $this->hasMany(Person::class, 'death_place_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LifeEvent::class);
    }

    public function label(): string
    {
        return collect([$this->name, $this->region, $this->country])
            ->filter()
            ->implode(', ');
    }

    public static function findOrCreateFromName(?string $name): ?self
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return static::firstOrCreate(['name' => $name]);
    }
}
