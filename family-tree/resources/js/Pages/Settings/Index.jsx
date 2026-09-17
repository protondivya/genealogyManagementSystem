import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Settings({ family, members, invitations, canAdmin }) {
    const details = useForm({ name: family.name, description: family.description || '' });
    const invite = useForm({ email: '', role: 'contributor' });

    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">Family settings</h1>}>
            <Head title="Settings" />
            <div className="mx-auto max-w-4xl px-4 py-8 space-y-8">
                <section className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                    <h2 className="font-serif text-xl">Workspace</h2>
                    <p className="mt-1 text-sm text-stone-500">Private family workspace — {members.length} members</p>
                    {canAdmin ? (
                        <form className="mt-4 space-y-3" onSubmit={(e) => { e.preventDefault(); details.patch(route('families.update', family.id)); }}>
                            <input className="block w-full rounded-md border-stone-300" value={details.data.name} onChange={(e) => details.setData('name', e.target.value)} />
                            <textarea className="block w-full rounded-md border-stone-300" rows="3" value={details.data.description} onChange={(e) => details.setData('description', e.target.value)} />
                            <button className="rounded-full bg-forest px-5 py-2 text-white">Save details</button>
                        </form>
                    ) : (
                        <p className="mt-3">{family.description}</p>
                    )}
                </section>

                <section className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                    <h2 className="font-serif text-xl">Members</h2>
                    <ul className="mt-4 divide-y divide-stone-100">
                        {members.map((m) => (
                            <li key={m.id} className="flex justify-between py-3">
                                <div>
                                    <div className="font-medium">{m.name}</div>
                                    <div className="text-sm text-stone-500">{m.email}</div>
                                </div>
                                <span className="capitalize text-sm text-stone-600">{m.role}</span>
                            </li>
                        ))}
                    </ul>
                </section>

                {canAdmin && (
                    <section className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                        <h2 className="font-serif text-xl">Invite a family member</h2>
                        <form className="mt-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]" onSubmit={(e) => { e.preventDefault(); invite.post(route('families.invite', family.id), { onSuccess: () => invite.reset('email') }); }}>
                            <input type="email" required className="rounded-md border-stone-300" placeholder="Email" value={invite.data.email} onChange={(e) => invite.setData('email', e.target.value)} />
                            <select className="rounded-md border-stone-300" value={invite.data.role} onChange={(e) => invite.setData('role', e.target.value)}>
                                <option value="admin">Admin</option>
                                <option value="contributor">Contributor</option>
                                <option value="viewer">Viewer</option>
                            </select>
                            <button className="rounded-full bg-forest px-5 py-2 text-white">Send invite</button>
                        </form>
                        {invite.errors.email && <p className="mt-2 text-sm text-rose-700">{invite.errors.email}</p>}
                        <ul className="mt-4 space-y-2 text-sm text-stone-600">
                            {invitations.map((inv) => (
                                <li key={inv.id}>{inv.email} · {inv.role} {inv.expired ? '(expired)' : ''}</li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
