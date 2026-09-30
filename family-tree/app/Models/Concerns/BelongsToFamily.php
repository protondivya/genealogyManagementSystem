<?php

namespace App\Models\Concerns;

use App\Models\Family;
use App\Models\Scopes\FamilyScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToFamily
{
    public static function bootBelongsToFamily(): void
    {
        static::addGlobalScope(new FamilyScope);

        static::creating(function (Model $model) {
            if (! $model->getAttribute('family_id') && $family = Family::current()) {
                $model->setAttribute('family_id', $family->id);
            }
        });
    }
}
