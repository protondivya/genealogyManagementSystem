import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ stats, recentPeople, activity, pendingInvites }) {
    const empty = !stats?.people;

    return (
        <AuthenticatedLayout
            header={<h1 className="font-serif text-2xl text-ink">Family dashboard</h1>}
        >
            <Head title="Dashboard" />
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {empty ? (
                    <div className="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-stone-200">
                        <h2 className="font-serif text-3xl">Start by adding yourself, then add your parents</h2>
                        <p className="mt-3 text-stone-600">
                            A fresh workspace is empty on purpose. Names, approximate years, and photos can come later.
                        </p>
                        <div className="mt-6 flex justify-center gap-3">
                            <Link href={route('people.create')} className="rounded-full bg-forest px-5 py-3 text-white">
                                Add a family member
                            </Link>
                            <Link href={route('settings.index')} className="rounded-full border border-forest px-5 py-3 text-forest">
                                Invite a family member
                            </Link>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {[
                                ['People', stats.people, 'people.index'],
                                ['Relationships', stats.relationships, 'tree.show'],
                                ['Life events', stats.events, 'timeline.index'],
                                ['Photos & documents', stats.media, 'media.index'],
                                ['Sources', stats.sources, 'sources.index'],
                                ['Members', stats.members, 'settings.index'],
                            ].map(([label, value, href]) => (
                                <Link key={label} href={route(href)} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                                    <div className="text-sm text-stone-500">{label}</div>
                                    <div className="mt-1 font-serif text-3xl">{value}</div>
                                </Link>
                            ))}
                        </div>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href={route('people.create')} className="rounded-full bg-forest px-5 py-3 text-white">Add a person</Link>
                            <Link href={route('tree.show')} className="rounded-full border border-forest px-5 py-3 text-forest">View tree</Link>
                            <Link href={route('settings.index')} className="rounded-full border border-stone-300 px-5 py-3">Invite a family member</Link>
                        </div>
                        <div className="mt-10 grid gap-8 lg:grid-cols-2">
                            <section className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                                <h2 className="font-serif text-xl">Recent people</h2>
                                <ul className="mt-4 divide-y divide-stone-100">
                                    {recentPeople.map((person) => (
                                        <li key={person.id} className="py-3">
                                            <Link href={route('people.show', person.id)} className="flex justify-between">
                                                <span>{person.full_name}</span>
                                                <span className="text-sm text-stone-500">{person.life_span}</span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                            <section className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                                <h2 className="font-serif text-xl">Recent activity</h2>
                                <ul className="mt-4 space-y-3 text-sm text-stone-600">
                                    {activity.map((item) => (
                                        <li key={item.id}>
                                            <span className="font-medium text-ink">{item.user}</span> {item.action} a {item.type.toLowerCase()} · {item.created_at}
                                        </li>
                                    ))}
                                    {!activity.length && <li>No activity yet.</li>}
                                </ul>
                                {pendingInvites?.length > 0 && (
                                    <p className="mt-4 text-sm text-amber-800">{pendingInvites.length} pending invite{pendingInvites.length > 1 ? 's' : ''}.</p>
                                )}
                            </section>
                        </div>
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
