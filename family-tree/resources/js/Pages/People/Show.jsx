import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const types = [
    ['parent', 'Parent'],
    ['adoptive_parent', 'Adoptive parent'],
    ['step_parent', 'Step-parent'],
    ['guardian', 'Guardian'],
    ['spouse', 'Spouse'],
    ['partner', 'Partner'],
    ['sibling', 'Sibling'],
];

export default function Show({ person, relationships, events, media, citations, canContribute, canAdmin }) {
    const { families, currentFamily } = usePage().props;
    const [tab, setTab] = useState('overview');
    const [peopleOptions, setPeopleOptions] = useState([]);
    const relForm = useForm({
        person_one_id: person.id,
        person_two_id: '',
        relationship_type: 'parent',
        notes: '',
        confidence_level: 'confirmed',
        swap: false,
    });

    const searchPeople = async (term) => {
        if (!term) {
            setPeopleOptions([]);
            return;
        }
        const res = await fetch(route('people.search', { q: term }), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.ok) {
            setPeopleOptions(await res.json());
        }
    };

    const submitRel = (e) => {
        e.preventDefault();
        const payload = { ...relForm.data };
        if (payload.swap && ['parent', 'adoptive_parent', 'step_parent', 'guardian'].includes(payload.relationship_type)) {
            [payload.person_one_id, payload.person_two_id] = [payload.person_two_id, payload.person_one_id];
        }
        router.post(route('relationships.store'), payload, { preserveScroll: true });
    };

    const tabs = [
        ['overview', 'Overview'],
        ['relationships', 'Relationships'],
        ['events', 'Life events'],
        ['media', 'Media'],
        ['sources', 'Sources'],
    ];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div className="flex items-center gap-4">
                        {person.photo_url ? (
                            <img src={person.photo_url} alt="" className="h-16 w-16 rounded-full object-cover" />
                        ) : (
                            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-forest/10 font-serif text-2xl text-forest">
                                {(person.full_name || '?').charAt(0)}
                            </div>
                        )}
                        <div>
                            <h1 className="font-serif text-3xl">{person.full_name}</h1>
                            <p className="text-stone-500">{person.life_span || 'Dates unknown'}</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('tree.show', { root: person.id })} className="rounded-full border border-forest px-4 py-2 text-forest">View in tree</Link>
                        {canContribute && <Link href={route('people.edit', person.id)} className="rounded-full bg-forest px-4 py-2 text-white">Edit</Link>}
                    </div>
                </div>
            }
        >
            <Head title={person.full_name} />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <div className="flex flex-wrap gap-2">
                    {tabs.map(([id, label]) => (
                        <button key={id} type="button" onClick={() => setTab(id)} className={`rounded-full px-4 py-2 text-sm ${tab === id ? 'bg-forest text-white' : 'bg-white ring-1 ring-stone-200'}`}>
                            {label}
                        </button>
                    ))}
                </div>

                {tab === 'overview' && (
                    <section className="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div><dt className="text-sm text-stone-500">Birth</dt><dd>{person.birth_date || 'Unknown'} {person.birth_place ? `— ${person.birth_place}` : ''}</dd></div>
                            <div><dt className="text-sm text-stone-500">Death</dt><dd>{person.is_living ? 'Living' : (person.death_date || 'Unknown')} {person.death_place ? `— ${person.death_place}` : ''}</dd></div>
                            {person.maiden_name && <div><dt className="text-sm text-stone-500">Maiden name</dt><dd>{person.maiden_name}</dd></div>}
                            <div><dt className="text-sm text-stone-500">Gender</dt><dd className="capitalize">{person.gender}</dd></div>
                        </dl>
                        {person.notes && <p className="mt-6 whitespace-pre-wrap text-stone-700">{person.notes}</p>}
                    </section>
                )}

                {tab === 'relationships' && (
                    <section className="mt-6 space-y-6">
                        <ul className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                            {relationships.map((rel) => (
                                <li key={rel.id} className="flex items-center justify-between border-b border-stone-100 py-3 last:border-0">
                                    <div>
                                        <Link href={route('people.show', rel.other.id)} className="font-medium">{rel.other.full_name}</Link>
                                        <div className="text-sm capitalize text-stone-500">{rel.label} · {rel.confidence_level}</div>
                                    </div>
                                    {canContribute && (
                                        <button type="button" className="text-sm text-rose-700" onClick={() => router.delete(route('relationships.destroy', rel.id))}>Remove</button>
                                    )}
                                </li>
                            ))}
                            {!relationships.length && <li className="text-stone-500">No relationships yet.</li>}
                        </ul>
                        {canContribute && (
                            <form onSubmit={submitRel} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                                <h2 className="font-serif text-xl">Add a relationship</h2>
                                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                    <select className="rounded-md border-stone-300" value={relForm.data.relationship_type} onChange={(e) => relForm.setData('relationship_type', e.target.value)}>
                                        {types.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                                    </select>
                                    <input className="rounded-md border-stone-300" placeholder="Find the other person" onChange={(e) => searchPeople(e.target.value)} />
                                </div>
                                {peopleOptions.length > 0 && (
                                    <ul className="mt-2 max-h-40 overflow-auto rounded-md border border-stone-200 bg-white">
                                        {peopleOptions.filter((p) => p.id !== person.id).map((p) => (
                                            <li key={p.id}>
                                                <button type="button" className="block w-full px-3 py-2 text-left hover:bg-parchment" onClick={() => { relForm.setData('person_two_id', p.id); setPeopleOptions([]); }}>
                                                    {p.full_name}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                {relForm.data.person_two_id && (
                                    <p className="mt-3 rounded-lg bg-parchment px-3 py-2 text-sm">
                                        {['parent', 'adoptive_parent', 'step_parent', 'guardian'].includes(relForm.data.relationship_type)
                                            ? `${person.full_name} will become the ${relForm.data.relationship_type.replace('_', ' ')} of the selected person.`
                                            : `${person.full_name} and the selected person will be recorded as ${relForm.data.relationship_type}s.`}
                                    </p>
                                )}
                                {relForm.errors.relationship_type && <p className="mt-2 text-sm text-rose-700">{relForm.errors.relationship_type}</p>}
                                <button className="mt-4 rounded-full bg-forest px-4 py-2 text-white" disabled={!relForm.data.person_two_id}>Save relationship</button>
                            </form>
                        )}
                    </section>
                )}

                {tab === 'events' && (
                    <section className="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                        <ul className="space-y-4">
                            {events.map((event) => (
                                <li key={event.id}>
                                    <div className="font-medium capitalize">{event.type_label}</div>
                                    <div className="text-sm text-stone-500">{event.date}{event.place ? ` · ${event.place}` : ''}</div>
                                    {event.description && <p className="mt-1 text-stone-700">{event.description}</p>}
                                </li>
                            ))}
                            {!events.length && <li className="text-stone-500">No life events recorded.</li>}
                        </ul>
                        {canContribute && <Link href={route('timeline.index')} className="mt-4 inline-block text-forest underline">Add an event on the timeline</Link>}
                    </section>
                )}

                {tab === 'media' && (
                    <section className="mt-6 grid gap-4 sm:grid-cols-3">
                        {media.map((item) => (
                            <a key={item.id} href={item.url} className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-200">
                                {item.file_type === 'photo' && item.url ? <img src={item.url} alt={item.caption || ''} className="h-40 w-full object-cover" /> : <div className="p-6">{item.caption || item.file_type}</div>}
                            </a>
                        ))}
                        {!media.length && <p className="text-stone-500">No photos or documents yet.</p>}
                    </section>
                )}

                {tab === 'sources' && (
                    <section className="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                        <ul className="space-y-3">
                            {citations.map((c) => (
                                <li key={c.id}>
                                    <div className="font-medium">{c.source.title}</div>
                                    <div className="text-sm text-stone-500">{c.field_name || 'general'} · {c.confidence_level}</div>
                                </li>
                            ))}
                            {!citations.length && <li className="text-stone-500">No sources attached.</li>}
                        </ul>
                        {canContribute && <Link href={route('sources.index')} className="mt-4 inline-block text-forest underline">Manage sources</Link>}
                    </section>
                )}

                {canAdmin && (
                    <button
                        type="button"
                        className="mt-8 text-sm text-rose-700 underline"
                        onClick={() => {
                            if (window.confirm(`Remove ${person.full_name} from the tree? This can be restored later.`)) {
                                router.delete(route('people.destroy', person.id));
                            }
                        }}
                    >
                        Remove this person
                    </button>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
