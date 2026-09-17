import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function MediaIndex({ items, people, canContribute }) {
    const [lightbox, setLightbox] = useState(null);
    const form = useForm({
        file: null,
        caption: '',
        person_id: '',
    });

    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">Media archive</h1>}>
            <Head title="Media" />
            <div className="mx-auto max-w-6xl px-4 py-8">
                {canContribute && (
                    <form
                        className="mb-8 rounded-2xl border-2 border-dashed border-stone-300 bg-white p-6"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(route('media.store'), { forceFormData: true, onSuccess: () => form.reset() });
                        }}
                    >
                        <p className="font-medium">Drop a photo or document, or choose a file</p>
                        <input type="file" className="mt-3 block w-full" onChange={(e) => form.setData('file', e.target.files[0])} />
                        <div className="mt-3 grid gap-3 sm:grid-cols-2">
                            <input className="rounded-md border-stone-300" placeholder="Caption" value={form.data.caption} onChange={(e) => form.setData('caption', e.target.value)} />
                            <select className="rounded-md border-stone-300" value={form.data.person_id} onChange={(e) => form.setData('person_id', e.target.value)}>
                                <option value="">Link to a person (optional)</option>
                                {people.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                        </div>
                        <button className="mt-4 rounded-full bg-forest px-5 py-2 text-white" disabled={form.processing || !form.data.file}>Upload</button>
                    </form>
                )}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {items.data.map((item) => (
                        <button key={item.id} type="button" className="overflow-hidden rounded-2xl bg-white text-left shadow-sm ring-1 ring-stone-200" onClick={() => setLightbox(item)}>
                            {item.file_type === 'photo' && item.url ? (
                                <img src={item.url} alt={item.caption || ''} className="h-40 w-full object-cover" />
                            ) : (
                                <div className="flex h-40 items-center justify-center bg-parchment uppercase tracking-wide text-stone-500">{item.file_type}</div>
                            )}
                            <div className="p-3 text-sm">{item.caption || item.original_name || 'Untitled'}</div>
                        </button>
                    ))}
                </div>
                {!items.data.length && <p className="text-stone-500">No photographs or documents yet.</p>}
            </div>
            {lightbox && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-6" onClick={() => setLightbox(null)}>
                    <div className="max-h-full max-w-4xl overflow-auto rounded-2xl bg-white p-4" onClick={(e) => e.stopPropagation()}>
                        {lightbox.file_type === 'photo' && lightbox.url ? (
                            <img src={lightbox.url} alt={lightbox.caption || ''} className="max-h-[80vh] w-auto" />
                        ) : (
                            <a href={lightbox.url} className="text-forest underline">Open file</a>
                        )}
                        <p className="mt-3">{lightbox.caption}</p>
                        <button type="button" className="mt-3 text-sm underline" onClick={() => setLightbox(null)}>Close</button>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
