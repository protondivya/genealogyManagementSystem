import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ExportIndex({ people, familyName }) {
    const { canAdmin } = usePage().props;
    const [type, setType] = useState('tree');
    const [root, setRoot] = useState(people[0]?.id || '');
    const [personId, setPersonId] = useState(people[0]?.id || '');
    const [includeLiving, setIncludeLiving] = useState(false);

    const href = type === 'person'
        ? route('export.download', { type: 'person', person_id: personId, include_living: includeLiving ? 1 : 0 })
        : route('export.download', { type: 'tree', root, include_living: includeLiving ? 1 : 0 });

    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">Print and export</h1>}>
            <Head title="Export" />
            <div className="mx-auto max-w-xl px-4 py-8">
                <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                    <p className="text-stone-600">Open a printable page, then use your browser to save as PDF.</p>
                    <div className="mt-4 space-y-3">
                        <label className="flex items-center gap-2">
                            <input type="radio" checked={type === 'tree'} onChange={() => setType('tree')} />
                            Family tree
                        </label>
                        <label className="flex items-center gap-2">
                            <input type="radio" checked={type === 'person'} onChange={() => setType('person')} />
                            Person summary sheet
                        </label>
                        {type === 'tree' ? (
                            <select className="block w-full rounded-md border-stone-300" value={root} onChange={(e) => setRoot(e.target.value)}>
                                {people.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                        ) : (
                            <select className="block w-full rounded-md border-stone-300" value={personId} onChange={(e) => setPersonId(e.target.value)}>
                                {people.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                        )}
                        {canAdmin && (
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={includeLiving} onChange={(e) => setIncludeLiving(e.target.checked)} />
                                Include living-person details in this export
                            </label>
                        )}
                    </div>
                    <a href={href} className="mt-6 inline-block rounded-full bg-forest px-5 py-3 text-white">Open printable page</a>
                    <p className="mt-4 text-sm text-stone-500">Living relatives stay hidden unless an owner or admin opts in.</p>
                    <p className="mt-1 text-sm text-stone-400">{familyName}</p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
