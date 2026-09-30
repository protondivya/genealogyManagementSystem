<?php

namespace App\Http\Middleware;

use App\Models\Family;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyCurrentFamily
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $familyId = $request->session()->get('current_family_id');

        $family = $familyId
            ? $user->families()->where('families.id', $familyId)->first()
            : $user->families()->first();

        if ($family) {
            $request->session()->put('current_family_id', $family->id);
            $family->makeCurrent();
        } else {
            Family::forgetCurrent();
        }

        return $next($request);
    }
}
