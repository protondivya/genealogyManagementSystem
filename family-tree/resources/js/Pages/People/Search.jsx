import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Search({ results, q }) {
    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">Search results</h1>}>
            <Head title="Search" />
            <div className="mx-auto max-w-3xl px-4 py-8">
                <p className="text-stone-600">Matches for “{q}”</p>
                <ul className="mt-4 divide-y divide-stone-100 rounded-2xl bg-white shadow-sm ring-1 ring-stone-200">
                    {results.map((person) => (
                        <li key={person.id} className="flex items-center justify-between p-4">
                            <div>
                                <div className="font-medium">{person.full_name}</div>
                                <div className="text-sm text-stone-500">{person.life_span}</div>
                            </div>
                            <div className="flex gap-3 text-sm">
                                <Link href={route('people.show', person.id)} className="text-forest">View profile</Link>
                                <Link href={route('tree.show', { root: person.id })} className="text-forest">View in tree</Link>
                            </div>
                        </li>
                    ))}
                    {!results.length && <li className="p-6 text-stone-500">No people found.</li>}
                </ul>
            </div>
        </AuthenticatedLayout>
    );
}
