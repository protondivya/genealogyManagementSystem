<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\Person;
use App\Services\GraphService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreeController extends Controller
{
    use ResolvesFamily;

    public function show(Request $request, GraphService $graph): Response
    {
        $family = $this->currentFamily($request);
        $redact = $this->redactLiving($request, $family);
        $people = $family->people()->with(['birthPlace', 'deathPlace'])->orderBy('first_name')->get();

        $rootId = $request->integer('root') ?: $people->first()?->id;
        $depth = min(max($request->integer('depth') ?: 4, 1), 10);

        $tree = ['root_id' => null, 'depth' => $depth, 'nodes' => []];
        $root = $rootId ? $people->firstWhere('id', $rootId) : null;

        if ($root) {
            $cacheKey = "tree:{$family->id}:{$family->updated_at}:{$root->id}:{$depth}:".($redact ? 'r' : 'f');
            $tree = cache()->remember($cacheKey, 120, fn () => $graph->buildTree($family, $root, $depth, $redact));
        }

        return Inertia::render('Tree/Index', [
            'tree' => $tree,
            'people' => $people->map(fn (Person $p) => [
                'id' => $p->id,
                'name' => $p->full_name,
            ]),
            'root' => $rootId,
            'depth' => $depth,
            'canContribute' => $family->canContribute($request->user()),
        ]);
    }
}
