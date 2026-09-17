<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    use ResolvesFamily;

    public function index(Request $request): Response|RedirectResponse
    {
        $families = $request->user()->families()->withCount('people')->get();

        if ($families->isEmpty()) {
            return redirect()->route('families.create');
        }

        return Inertia::render('Families/Index', [
            'families' => $families,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Families/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $family = DB::transaction(function () use ($request, $data) {
            $family = Family::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'owner_id' => $request->user()->id,
            ]);

            $family->members()->attach($request->user()->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            AuditLog::record($family, $request->user(), 'created', $family, ['name' => $family->name]);

            return $family;
        });

        $request->session()->put('current_family_id', $family->id);

        return redirect()->route('dashboard')->with('success', 'Family workspace created. Start by adding yourself.');
    }

    public function switch(Request $request, Family $family): RedirectResponse
    {
        $this->familyOrFail($request, $family);

        return redirect()->route('dashboard');
    }

    public function dashboard(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        if ($user->families()->count() === 0) {
            return redirect()->route('families.create');
        }

        $family = $this->currentFamily($request);
        $redact = $this->redactLiving($request, $family);

        $recentPeople = $family->people()
            ->with(['birthPlace', 'deathPlace'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Person $p) => $p->toPublicArray($redact));

        $activity = $family->auditLogs()
            ->with('user')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'type' => class_basename($log->auditable_type),
                'user' => $log->user?->name,
                'created_at' => optional($log->created_at)?->diffForHumans(),
            ]);

        $pendingInvites = $family->invitations()
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get(['id', 'email', 'role', 'expires_at', 'created_at']);

        return Inertia::render('Dashboard', [
            'stats' => [
                'people' => $family->people()->count(),
                'relationships' => $family->relationships()->count(),
                'events' => $family->lifeEvents()->count(),
                'media' => $family->mediaItems()->count(),
                'sources' => $family->sources()->count(),
                'members' => $family->members()->count(),
            ],
            'recentPeople' => $recentPeople,
            'activity' => $activity,
            'pendingInvites' => $pendingInvites,
        ]);
    }

    public function settings(Request $request): Response
    {
        $family = $this->currentFamily($request);

        return Inertia::render('Settings/Index', [
            'family' => [
                'id' => $family->id,
                'name' => $family->name,
                'description' => $family->description,
                'owner_id' => $family->owner_id,
            ],
            'members' => $family->members()->get()->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
                'joined_at' => optional($user->pivot->joined_at)?->toDateString(),
            ]),
            'invitations' => $family->invitations()
                ->whereNull('accepted_at')
                ->latest()
                ->get()
                ->map(fn (FamilyInvitation $invite) => [
                    'id' => $invite->id,
                    'email' => $invite->email,
                    'role' => $invite->role,
                    'expires_at' => optional($invite->expires_at)?->toDateTimeString(),
                    'expired' => $invite->isExpired(),
                ]),
            'canAdmin' => $family->canAdmin($request->user()),
        ]);
    }

    public function update(Request $request, Family $family): RedirectResponse
    {
        $this->familyOrFail($request, $family);
        $this->authorizeAdmin($request, $family);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $family->update($data);
        AuditLog::record($family, $request->user(), 'updated', $family, $data);

        return back()->with('success', 'Family details updated.');
    }
}
