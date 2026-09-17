<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\AuditLog;
use App\Models\LifeEvent;
use App\Models\Person;
use App\Models\Place;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LifeEventController extends Controller
{
    use ResolvesFamily;

    public function index(Request $request): Response
    {
        $family = $this->currentFamily($request);
        $personId = $request->integer('person_id') ?: null;
        $type = $request->string('type')->toString() ?: null;

        $query = $family->lifeEvents()->with(['place', 'people']);

        if ($personId) {
            $query->whereHas('people', fn ($q) => $q->where('people.id', $personId));
        }
        if ($type) {
            $query->where('event_type', $type);
        }

        $events = $query->orderByRaw('event_date is null')
            ->orderBy('event_date')
            ->paginate(40)
            ->withQueryString();

        $events->getCollection()->transform(fn (LifeEvent $event) => [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'type_label' => $event->typeLabel(),
            'date' => $event->formattedDate(),
            'raw_date' => optional($event->event_date)?->toDateString(),
            'place' => $event->place?->label(),
            'description' => $event->description,
            'people' => $event->people->map(fn (Person $p) => [
                'id' => $p->id,
                'name' => $p->full_name,
                'role' => $p->pivot->role,
            ]),
        ]);

        return Inertia::render('Timeline/Index', [
            'events' => $events,
            'filters' => [
                'person_id' => $personId,
                'type' => $type,
            ],
            'people' => $family->people()->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn (Person $p) => ['id' => $p->id, 'name' => $p->full_name]),
            'eventTypes' => LifeEvent::TYPES,
            'canContribute' => $family->canContribute($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $family = $this->currentFamily($request);
        $this->authorizeContribute($request, $family);

        $data = $request->validate([
            'event_type' => ['required', 'in:'.implode(',', LifeEvent::TYPES)],
            'event_date' => ['nullable', 'date'],
            'event_date_precision' => ['nullable', 'string'],
            'event_date_text' => ['nullable', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'person_ids' => ['required', 'array', 'min:1'],
            'person_ids.*' => ['integer'],
            'roles' => ['nullable', 'array'],
        ]);

        $event = $family->lifeEvents()->create([
            'event_type' => $data['event_type'],
            'event_date' => $data['event_date'] ?? null,
            'event_date_precision' => $data['event_date_precision'] ?? 'unknown',
            'event_date_text' => $data['event_date_text'] ?? null,
            'place_id' => Place::findOrCreateFromName($data['place'] ?? null)?->id,
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        foreach ($data['person_ids'] as $index => $personId) {
            $person = Person::where('family_id', $family->id)->find($personId);
            if (! $person) {
                continue;
            }
            $role = $data['roles'][$personId] ?? $data['roles'][$index] ?? 'subject';
            $event->people()->attach($person->id, ['role' => $role, 'created_at' => now()]);
        }

        AuditLog::record($family, $request->user(), 'created', $event, ['type' => $event->event_type]);

        return back()->with('success', 'Life event recorded.');
    }

    public function destroy(Request $request, LifeEvent $lifeEvent): RedirectResponse
    {
        $family = $this->familyOrFail($request, $lifeEvent->family);
        $this->authorizeContribute($request, $family);

        $lifeEvent->delete();
        AuditLog::record($family, $request->user(), 'deleted', $lifeEvent);

        return back()->with('success', 'Event removed.');
    }
}
