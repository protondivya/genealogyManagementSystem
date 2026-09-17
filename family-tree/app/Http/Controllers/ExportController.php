<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\Person;
use App\Services\GraphService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExportController extends Controller
{
    use ResolvesFamily;

    public function index(Request $request, GraphService $graph): Response
    {
        $family = $this->currentFamily($request);
        $people = $family->people()->orderBy('first_name')->get()->map(fn (Person $p) => [
            'id' => $p->id,
            'name' => $p->full_name,
        ]);

        return Inertia::render('Export/Index', [
            'people' => $people,
            'familyName' => $family->name,
        ]);
    }

    public function download(Request $request, GraphService $graph): HttpResponse
    {
        $family = $this->currentFamily($request);
        $includeLiving = $request->boolean('include_living') && $family->canAdmin($request->user());
        $redact = ! $includeLiving;
        $type = $request->string('type')->toString() ?: 'tree';

        if ($type === 'person') {
            $person = Person::where('family_id', $family->id)->with(['birthPlace', 'deathPlace'])->findOrFail($request->integer('person_id'));
            $html = $this->personSheet($family->name, $person, $redact && $person->is_living);

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline; filename="'.$person->full_name.'-summary.html"',
            ]);
        }

        $root = Person::where('family_id', $family->id)->find($request->integer('root'))
            ?: $family->people()->first();

        abort_unless($root, 404, 'Add a family member before exporting.');

        $tree = $graph->buildTree($family, $root, min($request->integer('depth') ?: 4, 8), $redact);
        $html = $this->treeSheet($family->name, $root, $tree);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$family->name.'-tree.html"',
        ]);
    }

    private function personSheet(string $familyName, Person $person, bool $redact): string
    {
        $name = e($person->full_name);
        $span = $redact ? '' : e($person->life_span);
        $notes = $redact ? '' : e((string) $person->notes);
        $birth = $redact ? 'Private' : e(($person->birth_date?->toDateString() ?: 'Unknown').($person->birthPlace ? ' — '.$person->birthPlace->label() : ''));
        $death = $person->is_living ? 'Living' : e(($person->death_date?->toDateString() ?: 'Unknown').($person->deathPlace ? ' — '.$person->deathPlace->label() : ''));

        return $this->wrap("{$name} — {$familyName}", "
            <h1>{$name}</h1>
            <p class='meta'>{$span}</p>
            <table>
                <tr><th>Birth</th><td>{$birth}</td></tr>
                <tr><th>Death</th><td>{$death}</td></tr>
                <tr><th>Notes</th><td>{$notes}</td></tr>
            </table>
            <p class='hint'>Print this page or save it as a PDF from your browser.</p>
        ");
    }

    private function treeSheet(string $familyName, Person $root, array $tree): string
    {
        $grouped = [];
        foreach ($tree['nodes'] as $node) {
            $grouped[$node['generation']][] = $node;
        }
        ksort($grouped);

        $sections = '';
        foreach ($grouped as $gen => $nodes) {
            $label = $gen == 0 ? 'Root generation' : ($gen < 0 ? abs($gen).' generation'.(abs($gen) > 1 ? 's' : '').' above' : $gen.' generation'.($gen > 1 ? 's' : '').' below');
            $cards = '';
            foreach ($nodes as $node) {
                $span = e($node['life_span'] ?? '');
                $cards .= '<div class="card"><strong>'.e($node['full_name']).'</strong><div class="span">'.$span.'</div></div>';
            }
            $sections .= '<h2>'.e($label).'</h2><div class="row">'.$cards.'</div>';
        }

        $rootName = e($root->full_name);

        return $this->wrap("{$familyName} family tree", "
            <h1>".e($familyName)."</h1>
            <p class='meta'>Rooted at {$rootName}</p>
            {$sections}
            <p class='hint'>Print this page or save it as a PDF from your browser. Living-person details are hidden unless an admin opted to include them.</p>
        ");
    }

    private function wrap(string $title, string $body): string
    {
        $title = e($title);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{$title}</title>
<style>
body { font-family: Georgia, serif; max-width: 900px; margin: 40px auto; color: #1c1917; }
h1 { font-size: 28px; margin-bottom: 4px; }
h2 { font-size: 16px; margin-top: 28px; border-bottom: 1px solid #d6d3d1; padding-bottom: 4px; text-transform: uppercase; letter-spacing: .08em; color: #78716c; }
.meta { color: #57534e; margin-top: 0; }
table { width: 100%; border-collapse: collapse; margin-top: 16px; }
th { text-align: left; width: 140px; padding: 8px; background: #f5f5f4; }
td { padding: 8px; border-bottom: 1px solid #e7e5e4; }
.row { display: flex; flex-wrap: wrap; gap: 12px; }
.card { border: 1px solid #e7e5e4; padding: 12px 16px; min-width: 160px; }
.span { color: #78716c; font-size: 13px; }
.hint { margin-top: 40px; color: #a8a29e; font-size: 13px; }
@media print { .hint { display: none; } body { margin: 16px; } }
</style>
</head>
<body>{$body}</body>
</html>
HTML;
    }
}
