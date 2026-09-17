import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function PathIndex({ people, from, to, result }) {
    const [a, setA] = useState(from || '');
    const [b, setB] = useState(to || '');

    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">How are they related?</h1>}>
            <Head title="Relationship path" />
            <div className="mx-auto max-w-3xl px-4 py-8">
                <form
                    className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(route('path.show'), { from: a, to: b });
                    }}
                >
                    <p className="text-stone-600">Pick two people to see the shortest family connection.</p>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                        <select className="rounded-md border-stone-300" value={a} onChange={(e) => setA(e.target.value)}>
                            <option value="">First person</option>
                            {people.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select>
                        <select className="rounded-md border-stone-300" value={b} onChange={(e) => setB(e.target.value)}>
                            <option value="">Second person</option>
                            {people.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select>
                    </div>
                    <button className="mt-4 rounded-full bg-forest px-5 py-2 text-white">Find relationship</button>
                </form>

                {result && (
                    <div className="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                        <p className="font-serif text-2xl">{result.phrase}</p>
                        {result.found ? (
                            <ol className="mt-6 space-y-3">
                                {result.steps.map((step, i) => (
                                    <li key={i} className="rounded-xl bg-parchment px-4 py-3">
                                        {step.label}
                                    </li>
                                ))}
                            </ol>
                        ) : (
                            <p className="mt-4 text-stone-600">No path found. You may still need to record a missing parent, sibling, or marriage.</p>
                        )}
                        {result.path?.length > 0 && (
                            <div className="mt-6 flex flex-wrap gap-2">
                                {result.path.map((p) => (
                                    <Link key={p.id} href={route('people.show', p.id)} className="rounded-full bg-forest/10 px-3 py-1 text-forest">{p.full_name}</Link>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
