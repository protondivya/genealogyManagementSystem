<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\Person;
use App\Services\GraphService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RelationshipPathController extends Controller
{
    use ResolvesFamily;

    public function show(Request $request, GraphService $graph): Response
    {
        $family = $this->currentFamily($request);
        $redact = $this->redactLiving($request, $family);

        $people = $family->people()->orderBy('first_name')->get()->map(fn (Person $p) => [
            'id' => $p->id,
            'name' => $p->full_name,
        ]);

        $fromId = $request->integer('from') ?: null;
        $toId = $request->integer('to') ?: null;
        $result = null;

        if ($fromId && $toId) {
            $from = Person::where('family_id', $family->id)->findOrFail($fromId);
            $to = Person::where('family_id', $family->id)->findOrFail($toId);
            $result = $graph->shortestPath($family, $from, $to, $redact);
        }

        return Inertia::render('Path/Index', [
            'people' => $people,
            'from' => $fromId,
            'to' => $toId,
            'result' => $result,
        ]);
    }
}
