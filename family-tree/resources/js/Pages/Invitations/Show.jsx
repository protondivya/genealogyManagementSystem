import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function InvitationShow({ invite, authenticated }) {
    return (
        <GuestLayout>
            <Head title="Family invitation" />
            <h1 className="font-serif text-2xl">Join {invite.family}</h1>
            <p className="mt-3 text-stone-600">
                {invite.inviter || 'A family member'} invited {invite.email} as a {invite.role}.
            </p>
            {invite.accepted && <p className="mt-4 text-forest">This invitation has already been used.</p>}
            {invite.expired && <p className="mt-4 text-rose-700">This invitation has expired. Please ask for a new one.</p>}
            {!invite.accepted && !invite.expired && (
                <button
                    type="button"
                    className="mt-6 rounded-full bg-forest px-5 py-3 text-white"
                    onClick={() => router.post(route('invitations.accept', invite.token))}
                >
                    {authenticated ? 'Accept invitation' : 'Create an account to join'}
                </button>
            )}
            {!authenticated && (
                <p className="mt-4 text-sm">
                    Already have an account? <Link href={route('login')} className="text-forest underline">Log in</Link>
                </p>
            )}
        </GuestLayout>
    );
}
