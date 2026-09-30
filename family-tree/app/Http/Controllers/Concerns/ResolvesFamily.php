<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Family;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

trait ResolvesFamily
{
    protected function currentFamily(Request $request): Family
    {
        $family = Family::current();

        if ($family && $family->hasMember($request->user())) {
            return $family;
        }

        $user = $request->user();
        $familyId = $request->session()->get('current_family_id');

        $family = $familyId
            ? $user->families()->where('families.id', $familyId)->first()
            : $user->families()->first();

        if (! $family) {
            throw new HttpResponseException(redirect()->route('families.create'));
        }

        $this->activateFamily($request, $family);

        return $family;
    }

    protected function familyOrFail(Request $request, Family $family): Family
    {
        abort_unless($family->hasMember($request->user()), 403);

        $this->activateFamily($request, $family);

        return $family;
    }

    protected function activateFamily(Request $request, Family $family): void
    {
        $request->session()->put('current_family_id', $family->id);

        if (! $family->isCurrent()) {
            $family->makeCurrent();
        }
    }

    protected function redactLiving(Request $request, Family $family): bool
    {
        return $family->roleFor($request->user()) === 'viewer';
    }

    protected function authorizeContribute(Request $request, Family $family): void
    {
        abort_unless($family->canContribute($request->user()), 403, 'Viewers cannot edit family records.');
    }

    protected function authorizeAdmin(Request $request, Family $family): void
    {
        abort_unless($family->canAdmin($request->user()), 403, 'Only owners and admins can do this.');
    }
}
