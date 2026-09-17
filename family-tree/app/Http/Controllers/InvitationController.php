<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\FamilyInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    use ResolvesFamily;

    public function store(Request $request, Family $family): RedirectResponse
    {
        $this->familyOrFail($request, $family);
        $this->authorizeAdmin($request, $family);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:admin,contributor,viewer'],
        ]);

        if ($family->members()->where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => 'That person is already a member of this family.']);
        }

        $invite = FamilyInvitation::create([
            'family_id' => $family->id,
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'token' => FamilyInvitation::generateToken(),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
        ]);

        AuditLog::record($family, $request->user(), 'created', $invite, [
            'email' => $invite->email,
            'role' => $invite->role,
        ]);

        return back()->with('success', 'Invitation created. Share the invite link with '.$invite->email.'.')
            ->with('invite_url', route('invitations.show', $invite->token));
    }

    public function show(string $token): Response
    {
        $invite = FamilyInvitation::with('family', 'inviter')->where('token', $token)->firstOrFail();

        return Inertia::render('Invitations/Show', [
            'invite' => [
                'email' => $invite->email,
                'role' => $invite->role,
                'family' => $invite->family->name,
                'inviter' => $invite->inviter?->name,
                'expired' => $invite->isExpired(),
                'accepted' => $invite->isAccepted(),
                'token' => $invite->token,
            ],
            'authenticated' => Auth::check(),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = FamilyInvitation::with('family')->where('token', $token)->firstOrFail();

        if ($invite->isAccepted()) {
            return redirect()->route('login')->with('success', 'This invitation has already been used.');
        }

        if ($invite->isExpired()) {
            return back()->withErrors(['token' => 'This invitation has expired. Please ask for a new one.']);
        }

        if (! $request->user()) {
            $request->session()->put('pending_invite_token', $token);
            $request->session()->put('url.intended', route('invitations.accept', $token));

            return redirect()->route('register')->with('success', 'Create an account to join '.$invite->family->name.'.');
        }

        if (strtolower($request->user()->email) !== strtolower($invite->email)) {
            return back()->withErrors(['token' => 'Please sign in with '.$invite->email.' to accept this invitation.']);
        }

        $family = $invite->family;
        if (! $family->hasMember($request->user())) {
            $family->members()->attach($request->user()->id, [
                'role' => $invite->role,
                'invited_at' => $invite->created_at,
                'joined_at' => now(),
            ]);
        }

        $invite->update(['accepted_at' => now()]);
        $request->session()->put('current_family_id', $family->id);
        AuditLog::record($family, $request->user(), 'updated', $invite, ['accepted' => true]);

        return redirect()->route('dashboard')->with('success', 'Welcome to '.$family->name.'.');
    }
}
