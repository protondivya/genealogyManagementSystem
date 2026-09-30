<?php

namespace App\Http\Middleware;

use App\Models\Family;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $families = [];
        $currentFamily = null;
        $role = null;

        if ($user) {
            $families = $user->families()->get()->map(fn ($family) => [
                'id' => $family->id,
                'name' => $family->name,
                'role' => $family->pivot->role,
            ]);

            $currentTenant = Family::current();
            $familyId = $currentTenant?->id ?? $request->session()->get('current_family_id');
            $current = $familyId
                ? $families->firstWhere('id', $familyId)
                : $families->first();
            $currentFamily = $current;
            $role = $current['role'] ?? null;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
            ],
            'families' => $families,
            'currentFamily' => $currentFamily,
            'role' => $role,
            'canContribute' => in_array($role, ['owner', 'admin', 'contributor'], true),
            'canAdmin' => in_array($role, ['owner', 'admin'], true),
            'flash' => [
                'success' => $request->session()->get('success'),
                'invite_url' => $request->session()->get('invite_url'),
            ],
        ];
    }
}
