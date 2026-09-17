<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\AuditLog;
use App\Models\Citation;
use App\Models\LifeEvent;
use App\Models\Person;
use App\Models\Relationship;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SourceController extends Controller
{
    use ResolvesFamily;

    public function index(Request $request): Response
    {
        $family = $this->currentFamily($request);

        $sources = $family->sources()
            ->withCount('citations')
            ->latest()
            ->paginate(30)
            ->through(fn (Source $source) => [
                'id' => $source->id,
                'title' => $source->title,
                'type' => $source->type,
                'citation_text' => $source->citation_text,
                'url' => $source->url,
                'citations_count' => $source->citations_count,
            ]);

        return Inertia::render('Sources/Index', [
            'sources' => $sources,
            'types' => Source::TYPES,
            'canContribute' => $family->canContribute($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $family = $this->currentFamily($request);
        $this->authorizeContribute($request, $family);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', Source::TYPES)],
            'citation_text' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:255'],
        ]);

        $source = $family->sources()->create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record($family, $request->user(), 'created', $source, ['title' => $source->title]);

        return back()->with('success', 'Source added.');
    }

    public function attach(Request $request): RedirectResponse
    {
        $family = $this->currentFamily($request);
        $this->authorizeContribute($request, $family);

        $data = $request->validate([
            'source_id' => ['required', 'integer'],
            'citable_type' => ['required', 'in:person,relationship,life_event'],
            'citable_id' => ['required', 'integer'],
            'field_name' => ['nullable', 'string', 'max:100'],
            'confidence_level' => ['required', 'in:confirmed,probable,uncertain'],
            'notes' => ['nullable', 'string'],
        ]);

        $source = Source::where('family_id', $family->id)->findOrFail($data['source_id']);

        $citation = Citation::create([
            'source_id' => $source->id,
            'family_id' => $family->id,
            'citable_type' => $data['citable_type'],
            'citable_id' => $data['citable_id'],
            'field_name' => $data['field_name'] ?? null,
            'confidence_level' => $data['confidence_level'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record($family, $request->user(), 'created', $citation);

        return back()->with('success', 'Source attached with '.$data['confidence_level'].' confidence.');
    }
}
