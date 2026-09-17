import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ families }) {
    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">Your families</h1>}>
            <Head title="Families" />
            <div className="mx-auto max-w-3xl px-4 py-10">
                <div className="space-y-4">
                    {families.map((family) => (
                        <button
                            key={family.id}
                            type="button"
                            onClick={() => router.post(route('families.switch', family.id))}
                            className="block w-full rounded-2xl bg-white p-6 text-left shadow-sm ring-1 ring-stone-200"
                        >
                            <div className="font-serif text-xl">{family.name}</div>
                            <div className="text-sm text-stone-500">{family.people_count || 0} people · {family.pivot?.role || family.role}</div>
                        </button>
                    ))}
                </div>
                <Link href={route('families.create')} className="mt-6 inline-block rounded-full bg-forest px-5 py-3 text-white">
                    Create another family
                </Link>
            </div>
        </AuthenticatedLayout>
    );
}
