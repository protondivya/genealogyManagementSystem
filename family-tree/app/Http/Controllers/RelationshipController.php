<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\Relationship;
use App\Services\GraphService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RelationshipController extends Controller
{
    use ResolvesFamily;

    public function store(Request $request, GraphService $graph): RedirectResponse
    {
        $family = $this->currentFamily($request);
        $this->authorizeContribute($request, $family);

        $data = $request->validate([
            'person_one_id' => ['required', 'integer'],
            'person_two_id' => ['required', 'integer', 'different:person_one_id'],
            'relationship_type' => ['required', 'in:parent,spouse,partner,sibling,adoptive_parent,step_parent,guardian'],
            'start_date' => ['nullable', 'date'],
            'start_date_precision' => ['nullable', 'string'],
            'end_date' => ['nullable', 'date'],
            'end_date_precision' => ['nullable', 'string'],
            'confidence_level' => ['nullable', 'in:confirmed,probable,uncertain'],
            'notes' => ['nullable', 'string'],
        ]);

        $one = Person::where('family_id', $family->id)->findOrFail($data['person_one_id']);
        $two = Person::where('family_id', $family->id)->findOrFail($data['person_two_id']);

        if (Relationship::isDirectionalType($data['relationship_type'])
            && $graph->wouldCreateCycle($one->id, $two->id, $family)) {
            return back()->withErrors([
                'relationship_type' => 'This would make '.$one->full_name.' their own ancestor — please check the relationship.',
            ]);
        }

        [$personOneId, $personTwoId] = Relationship::canonicalize($one->id, $two->id, $data['relationship_type']);

        $exists = Relationship::where('person_one_id', $personOneId)
            ->where('person_two_id', $personTwoId)
            ->where('relationship_type', $data['relationship_type'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['relationship_type' => 'That relationship is already recorded.']);
        }

        $relationship = Relationship::create([
            'family_id' => $family->id,
            'person_one_id' => $personOneId,
            'person_two_id' => $personTwoId,
            'relationship_type' => $data['relationship_type'],
            'is_directional' => Relationship::isDirectionalType($data['relationship_type']),
            'start_date' => $data['start_date'] ?? null,
            'start_date_precision' => $data['start_date_precision'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'end_date_precision' => $data['end_date_precision'] ?? null,
            'confidence_level' => $data['confidence_level'] ?? 'confirmed',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $family->touch();
        AuditLog::record($family, $request->user(), 'created', $relationship, [
            'type' => $relationship->relationship_type,
            'people' => [$one->full_name, $two->full_name],
        ]);

        return back()->with('success', $relationship->load(['personOne', 'personTwo'])->previewSentence().'.');
    }

    public function update(Request $request, Relationship $relationship): RedirectResponse
    {
        $family = $this->familyOrFail($request, $relationship->family);
        $this->authorizeContribute($request, $family);

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'start_date_precision' => ['nullable', 'string'],
            'end_date' => ['nullable', 'date'],
            'end_date_precision' => ['nullable', 'string'],
            'confidence_level' => ['nullable', 'in:confirmed,probable,uncertain'],
            'notes' => ['nullable', 'string'],
        ]);

        $relationship->update($data);
        AuditLog::record($family, $request->user(), 'updated', $relationship);

        return back()->with('success', 'Relationship updated.');
    }

    public function destroy(Request $request, Relationship $relationship): RedirectResponse
    {
        $family = $this->familyOrFail($request, $relationship->family);
        $this->authorizeContribute($request, $family);

        $relationship->delete();
        $family->touch();
        AuditLog::record($family, $request->user(), 'deleted', $relationship);

        return back()->with('success', 'Relationship removed.');
    }
}
