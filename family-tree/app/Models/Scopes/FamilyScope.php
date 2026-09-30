<?php

namespace App\Models\Scopes;

use App\Models\Family;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FamilyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $family = Family::current();

        if (! $family) {
            return;
        }

        $builder->where($model->qualifyColumn('family_id'), $family->id);
    }
}
