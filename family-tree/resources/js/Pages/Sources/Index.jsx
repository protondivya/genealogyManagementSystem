import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function SourcesIndex({ sources, types, canContribute }) {
    const form = useForm({
        title: '',
        type: 'document',
        citation_text: '',
        url: '',
    });
    const cite = useForm({
        source_id: '',
        citable_type: 'person',
        citable_id: '',
        field_name: 'birth_date',
        confidence_level: 'probable',
        notes: '',
    });

    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">Sources</h1>}>
            <Head title="Sources" />
            <div className="mx-auto max-w-5xl px-4 py-8">
                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                        <h2 className="font-serif text-xl">Citation list</h2>
                        <ul className="mt-4 space-y-4">
                            {sources.data.map((source) => (
                                <li key={source.id}>
                                    <div className="font-medium">{source.title}</div>
                                    <div className="text-sm capitalize text-stone-500">{source.type} · used {source.citations_count} time{source.citations_count === 1 ? '' : 's'}</div>
                                    {source.citation_text && <p className="mt-1 text-sm text-stone-600">{source.citation_text}</p>}
                                </li>
                            ))}
                            {!sources.data.length && <li className="text-stone-500">No sources yet. Family bibles, census records, and interviews all count.</li>}
                        </ul>
                    </section>
                    {canContribute && (
                        <section className="space-y-6">
                            <form
                                className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200"
                                onSubmit={(e) => { e.preventDefault(); form.post(route('sources.store'), { onSuccess: () => form.reset() }); }}
                            >
                                <h2 className="font-serif text-xl">Add a source</h2>
                                <input className="mt-4 block w-full rounded-md border-stone-300" placeholder="Title, e.g. 1911 census" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                                <select className="mt-3 block w-full rounded-md border-stone-300" value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                    {types.map((t) => <option key={t} value={t}>{t}</option>)}
                                </select>
                                <textarea className="mt-3 block w-full rounded-md border-stone-300" rows="3" placeholder="Citation text" value={form.data.citation_text} onChange={(e) => form.setData('citation_text', e.target.value)} />
                                <input className="mt-3 block w-full rounded-md border-stone-300" placeholder="URL (optional)" value={form.data.url} onChange={(e) => form.setData('url', e.target.value)} />
                                <button className="mt-4 rounded-full bg-forest px-5 py-2 text-white">Save source</button>
                            </form>
                            <form
                                className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200"
                                onSubmit={(e) => { e.preventDefault(); cite.post(route('citations.store'), { onSuccess: () => cite.reset('notes') }); }}
                            >
                                <h2 className="font-serif text-xl">Attach to a fact</h2>
                                <select className="mt-4 block w-full rounded-md border-stone-300" value={cite.data.source_id} onChange={(e) => cite.setData('source_id', e.target.value)}>
                                    <option value="">Choose source</option>
                                    {sources.data.map((s) => <option key={s.id} value={s.id}>{s.title}</option>)}
                                </select>
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    <select className="rounded-md border-stone-300" value={cite.data.citable_type} onChange={(e) => cite.setData('citable_type', e.target.value)}>
                                        <option value="person">Person</option>
                                        <option value="relationship">Relationship</option>
                                        <option value="life_event">Life event</option>
                                    </select>
                                    <input className="rounded-md border-stone-300" placeholder="Record ID" value={cite.data.citable_id} onChange={(e) => cite.setData('citable_id', e.target.value)} />
                                    <input className="rounded-md border-stone-300" placeholder="Field, e.g. birth_date" value={cite.data.field_name} onChange={(e) => cite.setData('field_name', e.target.value)} />
                                    <select className="rounded-md border-stone-300" value={cite.data.confidence_level} onChange={(e) => cite.setData('confidence_level', e.target.value)}>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="probable">Probable</option>
                                        <option value="uncertain">Uncertain</option>
                                    </select>
                                </div>
                                <textarea className="mt-3 block w-full rounded-md border-stone-300" rows="2" placeholder="Notes about uncertainty" value={cite.data.notes} onChange={(e) => cite.setData('notes', e.target.value)} />
                                <button className="mt-4 rounded-full border border-forest px-5 py-2 text-forest">Attach source</button>
                            </form>
                        </section>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
