<?php

namespace App\Multitenancy;

use App\Models\Family;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class SessionFamilyFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        if (! $request->hasSession()) {
            return null;
        }

        $familyId = $request->session()->get('current_family_id');

        if (! $familyId) {
            return null;
        }

        return Family::query()->find($familyId);
    }
}
