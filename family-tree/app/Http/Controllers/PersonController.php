<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\AuditLog;
use App\Models\Citation;
use App\Models\LifeEvent;
use App\Models\Person;
use App\Models\Place;
use App\Models\Relationship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PersonController extends Controller
{
    use ResolvesFamily;

    public function index(Request $request): Response
    {
        $family = $this->currentFamily($request);
        $redact = $this->redactLiving($request, $family);

        $query = $family->people()->with(['birthPlace', 'deathPlace']);

        if ($search = $request->string('q')->toString()) {
            $query->search($search);
        }

        if ($gender = $request->string('gender')->toString()) {
            $query->where('gender', $gender);
        }

        if ($request->boolean('living_only')) {
            $query->where('is_living', true);
        }

        $people = $query->orderBy('last_name')->orderBy('first_name')->paginate(24)->withQueryString();

        $people->getCollection()->transform(fn (Person $p) => $p->toPublicArray($redact));

        return Inertia::render('People/Index', [
            'people' => $people,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'gender' => $request->string('gender')->toString(),
                'living_only' => $request->boolean('living_only'),
            ],
            'canContribute' => $family->canContribute($request->user()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('People/Form', [
            'person' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $family = $this->currentFamily($request);
        $this->authorizeContribute($request, $family);

        $data = $this->validatedPerson($request);
        $person = $family->people()->create([
            ...$this->personAttributes($data),
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('photo')) {
            $this->storePhoto($request, $person);
        }

        $family->touch();
        AuditLog::record($family, $request->user(), 'created', $person, ['name' => $person->full_name]);

        return redirect()->route('people.show', $person)->with('success', $person->full_name.' has been added.');
    }

    public function show(Request $request, Person $person): Response
    {
        $family = $this->familyOrFail($request, $person->family);
        $redact = $this->redactLiving($request, $family);
        $person->load(['birthPlace', 'deathPlace', 'citations.source']);

        $rels = Relationship::with(['personOne.birthPlace', 'personOne.deathPlace', 'personTwo.birthPlace', 'personTwo.deathPlace'])
            ->where('family_id', $family->id)
            ->where(function ($q) use ($person) {
                $q->where('person_one_id', $person->id)->orWhere('person_two_id', $person->id);
            })
            ->get()
            ->map(function (Relationship $rel) use ($person, $redact) {
                $other = $rel->person_one_id === $person->id ? $rel->personTwo : $rel->personOne;

                return [
                    'id' => $rel->id,
                    'type' => $rel->relationship_type,
                    'label' => $rel->label(),
                    'notes' => $rel->notes,
                    'confidence_level' => $rel->confidence_level,
                    'start_date' => optional($rel->start_date)?->toDateString(),
                    'end_date' => optional($rel->end_date)?->toDateString(),
                    'other' => $other?->toPublicArray($redact),
                ];
            });

        $events = LifeEvent::with(['place', 'people'])
            ->where('family_id', $family->id)
            ->whereHas('people', fn ($q) => $q->where('people.id', $person->id))
            ->orderBy('event_date')
            ->get()
            ->map(fn (LifeEvent $event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'type_label' => $event->typeLabel(),
                'date' => $event->formattedDate(),
                'place' => $event->place?->label(),
                'description' => $event->description,
                'people' => $event->people->map(fn (Person $p) => [
                    'id' => $p->id,
                    'name' => $p->full_name,
                    'role' => $p->pivot->role,
                ]),
            ]);

        $media = $person->mediaLinks()->with('mediaItem')->get()->map(fn ($link) => [
            'id' => $link->mediaItem->id,
            'url' => $link->mediaItem->url,
            'caption' => $link->mediaItem->caption,
            'file_type' => $link->mediaItem->file_type,
        ]);

        $citations = $person->citations()->with('source')->get()->map(fn (Citation $c) => [
            'id' => $c->id,
            'field_name' => $c->field_name,
            'confidence_level' => $c->confidence_level,
            'notes' => $c->notes,
            'source' => [
                'id' => $c->source->id,
                'title' => $c->source->title,
                'type' => $c->source->type,
            ],
        ]);

        return Inertia::render('People/Show', [
            'person' => $person->toPublicArray($redact) + [
                'notes' => $redact ? null : $person->notes,
                'birth_date_precision' => $person->birth_date_precision,
                'death_date_precision' => $person->death_date_precision,
                'maiden_name' => $person->maiden_name,
            ],
            'relationships' => $rels,
            'events' => $events,
            'media' => $media,
            'citations' => $citations,
            'canContribute' => $family->canContribute($request->user()),
            'canAdmin' => $family->canAdmin($request->user()),
        ]);
    }

    public function edit(Request $request, Person $person): Response
    {
        $family = $this->familyOrFail($request, $person->family);
        $this->authorizeContribute($request, $family);
        $person->load(['birthPlace', 'deathPlace']);

        return Inertia::render('People/Form', [
            'person' => [
                ...$person->toPublicArray(false),
                'notes' => $person->notes,
                'birth_date_precision' => $person->birth_date_precision,
                'death_date_precision' => $person->death_date_precision,
                'maiden_name' => $person->maiden_name,
                'birth_place' => $person->birthPlace?->name,
                'death_place' => $person->deathPlace?->name,
            ],
        ]);
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        $family = $this->familyOrFail($request, $person->family);
        $this->authorizeContribute($request, $family);

        $data = $this->validatedPerson($request, false);
        $person->update($this->personAttributes($data));

        if ($request->hasFile('photo')) {
            $this->storePhoto($request, $person);
        }

        $family->touch();
        AuditLog::record($family, $request->user(), 'updated', $person, ['name' => $person->full_name]);

        return redirect()->route('people.show', $person)->with('success', 'Details saved.');
    }

    public function destroy(Request $request, Person $person): RedirectResponse
    {
        $family = $this->familyOrFail($request, $person->family);
        $this->authorizeAdmin($request, $family);

        $name = $person->full_name;
        $person->delete();
        $family->touch();
        AuditLog::record($family, $request->user(), 'deleted', $person, ['name' => $name]);

        return redirect()->route('people.index')->with('success', $name.' was removed from the tree. You can restore them later if needed.');
    }

    public function search(Request $request)
    {
        $family = $this->currentFamily($request);
        $redact = $this->redactLiving($request, $family);
        $term = $request->string('q')->toString();

        $results = $family->people()
            ->with(['birthPlace', 'deathPlace'])
            ->search($term)
            ->limit(12)
            ->get()
            ->map(fn (Person $p) => $p->toPublicArray($redact));

        if ($request->header('X-Inertia')) {
            return Inertia::render('People/Search', [
                'results' => $results,
                'q' => $term,
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($results);
        }

        return Inertia::render('People/Search', [
            'results' => $results,
            'q' => $term,
        ]);
    }

    private function validatedPerson(Request $request, bool $requireName = true): array
    {
        return $request->validate([
            'first_name' => [$requireName ? 'required' : 'sometimes', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'maiden_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other,unknown'],
            'birth_date' => ['nullable', 'date'],
            'birth_date_precision' => ['nullable', 'in:exact,approximate,year,month,circa,before,after,range,unknown'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'death_date' => ['nullable', 'date'],
            'death_date_precision' => ['nullable', 'in:exact,approximate,year,month,circa,before,after,range,unknown'],
            'death_place' => ['nullable', 'string', 'max:255'],
            'is_living' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);
    }

    private function personAttributes(array $data): array
    {
        $isLiving = array_key_exists('is_living', $data)
            ? (bool) $data['is_living']
            : empty($data['death_date']);

        return [
            'first_name' => $data['first_name'] ?? 'Unknown',
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'maiden_name' => $data['maiden_name'] ?? null,
            'gender' => $data['gender'] ?? 'unknown',
            'birth_date' => $data['birth_date'] ?? null,
            'birth_date_precision' => $data['birth_date_precision'] ?? 'unknown',
            'birth_place_id' => Place::findOrCreateFromName($data['birth_place'] ?? null)?->id,
            'death_date' => $data['death_date'] ?? null,
            'death_date_precision' => $data['death_date_precision'] ?? null,
            'death_place_id' => Place::findOrCreateFromName($data['death_place'] ?? null)?->id,
            'is_living' => $isLiving,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function storePhoto(Request $request, Person $person): void
    {
        $path = $request->file('photo')->store('family-media/'.$person->family_id, 'local');
        $person->update(['profile_photo_path' => $path]);
    }
}
