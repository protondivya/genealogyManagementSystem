import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

const icons = {
    birth: 'B',
    death: 'D',
    marriage: 'M',
    divorce: 'X',
    migration: '>',
    education: 'E',
    occupation: 'O',
    military_service: 'S',
    census: 'C',
    other: '.',
};

export default function Timeline({ events, filters, people, eventTypes, canContribute }) {
    const form = useForm({
        event_type: 'birth',
        event_date: '',
        event_date_precision: 'exact',
        event_date_text: '',
        place: '',
        description: '',
        person_ids: [],
    });

    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">Family timeline</h1>}>
            <Head title="Timeline" />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <form className="mb-6 flex flex-wrap gap-3" onSubmit={(e) => { e.preventDefault(); }}>
                    <select className="rounded-full border-stone-300" value={filters.person_id || ''} onChange={(e) => router.get(route('timeline.index'), { person_id: e.target.value, type: filters.type })}>
                        <option value="">All people</option>
                        {people.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                    </select>
                    <select className="rounded-full border-stone-300" value={filters.type || ''} onChange={(e) => router.get(route('timeline.index'), { person_id: filters.person_id, type: e.target.value })}>
                        <option value="">All event types</option>
                        {eventTypes.map((t) => <option key={t} value={t} className="capitalize">{t.replace('_', ' ')}</option>)}
                    </select>
                </form>

                <ol className="relative border-l border-stone-300 pl-8">
                    {events.data.map((event) => (
                        <li key={event.id} className="mb-8">
                            <span className="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full bg-forest text-xs text-white">{icons[event.event_type] || '.'}</span>
                            <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
                                <div className="flex justify-between gap-4">
                                    <div>
                                        <div className="font-medium capitalize">{event.type_label}</div>
                                        <div className="text-sm text-stone-500">{event.date}{event.place ? ` · ${event.place}` : ''}</div>
                                    </div>
                                    {canContribute && (
                                        <button type="button" className="text-sm text-rose-700" onClick={() => router.delete(route('events.destroy', event.id))}>Remove</button>
                                    )}
                                </div>
                                {event.description && <p className="mt-2 text-stone-700">{event.description}</p>}
                                <div className="mt-2 flex flex-wrap gap-2 text-sm">
                                    {event.people.map((p) => (
                                        <Link key={p.id} href={route('people.show', p.id)} className="rounded-full bg-parchment px-3 py-1">{p.name} · {p.role}</Link>
                                    ))}
                                </div>
                            </div>
                        </li>
                    ))}
                    {!events.data.length && <li className="text-stone-500">No events yet. Add a birth, marriage, or migration to begin the story.</li>}
                </ol>

                {canContribute && (
                    <form
                        className="mt-10 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(route('events.store'), { preserveScroll: true, onSuccess: () => form.reset('description', 'event_date', 'place') });
                        }}
                    >
                        <h2 className="font-serif text-xl">Add event</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <select className="rounded-md border-stone-300" value={form.data.event_type} onChange={(e) => form.setData('event_type', e.target.value)}>
                                {eventTypes.map((t) => <option key={t} value={t}>{t.replace('_', ' ')}</option>)}
                            </select>
                            <input type="date" className="rounded-md border-stone-300" value={form.data.event_date} onChange={(e) => form.setData('event_date', e.target.value)} />
                            <select className="rounded-md border-stone-300" value={form.data.event_date_precision} onChange={(e) => form.setData('event_date_precision', e.target.value)}>
                                {['exact', 'approximate', 'year', 'circa', 'unknown'].map((p) => <option key={p} value={p}>{p}</option>)}
                            </select>
                            <input className="rounded-md border-stone-300" placeholder="Place" value={form.data.place} onChange={(e) => form.setData('place', e.target.value)} />
                            <select
                                multiple
                                className="rounded-md border-stone-300 sm:col-span-2"
                                value={form.data.person_ids}
                                onChange={(e) => form.setData('person_ids', Array.from(e.target.selectedOptions).map((o) => o.value))}
                            >
                                {people.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                            <textarea className="rounded-md border-stone-300 sm:col-span-2" rows="3" placeholder="Notes" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                        </div>
                        {form.errors.person_ids && <p className="mt-2 text-sm text-rose-700">{form.errors.person_ids}</p>}
                        <button className="mt-4 rounded-full bg-forest px-5 py-2 text-white" disabled={form.processing}>Save event</button>
                    </form>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
