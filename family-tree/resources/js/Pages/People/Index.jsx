import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

function Avatar({ person }) {
    if (person.photo_url) {
        return <img src={person.photo_url} alt="" className="h-12 w-12 rounded-full object-cover" />;
    }
    const initial = (person.full_name || '?').charAt(0);
    const tone = person.gender === 'female' ? 'bg-rose-100 text-rose-800' : person.gender === 'male' ? 'bg-sky-100 text-sky-800' : 'bg-stone-200 text-stone-700';
    return <div className={`flex h-12 w-12 items-center justify-center rounded-full text-lg font-semibold ${tone}`}>{initial}</div>;
}

export default function Index({ people, filters, canContribute }) {
    const [q, setQ] = useState(filters.q || '');

    const submit = (e) => {
        e.preventDefault();
        router.get(route('people.index'), { q, gender: filters.gender, living_only: filters.living_only }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="font-serif text-2xl">People</h1>
                    {canContribute && (
                        <Link href={route('people.create')} className="rounded-full bg-forest px-5 py-2.5 text-white">
                            Add a family member
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="People" />
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <form onSubmit={submit} className="mb-6 flex flex-wrap gap-3">
                    <input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Search by name"
                        className="min-w-[220px] flex-1 rounded-full border-stone-300 px-4 py-2"
                    />
                    <select
                        value={filters.gender || ''}
                        onChange={(e) => router.get(route('people.index'), { q, gender: e.target.value, living_only: filters.living_only })}
                        className="rounded-full border-stone-300"
                    >
                        <option value="">Any gender</option>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="other">Other</option>
                        <option value="unknown">Unknown</option>
                    </select>
                    <button type="submit" className="rounded-full bg-forest px-5 py-2 text-white">Search</button>
                </form>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {people.data.map((person) => (
                        <Link key={person.id} href={route('people.show', person.id)} className="flex gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
                            <Avatar person={person} />
                            <div>
                                <div className="font-medium">{person.full_name}</div>
                                <div className="text-sm text-stone-500">{person.life_span || 'Dates unknown'}</div>
                                {person.birth_place && <div className="text-sm text-stone-500">{person.birth_place}</div>}
                            </div>
                        </Link>
                    ))}
                </div>
                {!people.data.length && (
                    <div className="rounded-2xl bg-white p-10 text-center text-stone-600">
                        No people match that search. Try a first name, last name, or maiden name.
                    </div>
                )}
                {people.links?.length > 3 && (
                    <div className="mt-6 flex flex-wrap gap-2">
                        {people.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                className={`rounded-full px-3 py-1 text-sm ${link.active ? 'bg-forest text-white' : 'bg-white ring-1 ring-stone-200'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
