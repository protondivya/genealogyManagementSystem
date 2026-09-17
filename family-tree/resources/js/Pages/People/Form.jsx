import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

const precisions = [
    ['unknown', 'Unknown'],
    ['exact', 'Exact'],
    ['approximate', 'Approximate'],
    ['year', 'Year only'],
    ['month', 'Month and year'],
    ['circa', 'Circa'],
    ['before', 'Before'],
    ['after', 'After'],
    ['range', 'Range'],
];

export default function Form({ person }) {
    const editing = Boolean(person);
    const { data, setData, post, processing, errors } = useForm({
        first_name: person?.first_name || '',
        middle_name: person?.middle_name || '',
        last_name: person?.last_name || '',
        maiden_name: person?.maiden_name || '',
        gender: person?.gender || 'unknown',
        birth_date: person?.birth_date || '',
        birth_date_precision: person?.birth_date_precision || 'unknown',
        birth_place: person?.birth_place || '',
        death_date: person?.death_date || '',
        death_date_precision: person?.death_date_precision || 'unknown',
        death_place: person?.death_place || '',
        is_living: person ? person.is_living : true,
        notes: person?.notes || '',
        photo: null,
        ...(editing ? { _method: 'patch' } : {}),
    });

    const submit = (e) => {
        e.preventDefault();
        post(editing ? route('people.update', person.id) : route('people.store'), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">{editing ? 'Edit family member' : 'Add a family member'}</h1>}>
            <Head title={editing ? 'Edit person' : 'Add person'} />
            <form onSubmit={submit} className="mx-auto max-w-3xl px-4 py-8">
                <div className="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-stone-200">
                    <p className="text-stone-600">Leave anything blank if you are unsure. Approximate years are perfectly fine.</p>
                    <div className="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="first_name" value="First name" />
                            <TextInput id="first_name" className="mt-1 block w-full" value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} required />
                            <InputError message={errors.first_name} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="middle_name" value="Middle name" />
                            <TextInput id="middle_name" className="mt-1 block w-full" value={data.middle_name} onChange={(e) => setData('middle_name', e.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="last_name" value="Last name" />
                            <TextInput id="last_name" className="mt-1 block w-full" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="maiden_name" value="Maiden name" />
                            <TextInput id="maiden_name" className="mt-1 block w-full" value={data.maiden_name} onChange={(e) => setData('maiden_name', e.target.value)} />
                        </div>
                    </div>
                    <div className="mt-4">
                        <InputLabel htmlFor="gender" value="Gender" />
                        <select id="gender" className="mt-1 block w-full rounded-md border-stone-300" value={data.gender} onChange={(e) => setData('gender', e.target.value)}>
                            <option value="unknown">Unknown</option>
                            <option value="female">Female</option>
                            <option value="male">Male</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div className="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="birth_date" value="Birth date" />
                            <TextInput id="birth_date" type="date" className="mt-1 block w-full" value={data.birth_date || ''} onChange={(e) => setData('birth_date', e.target.value)} />
                            <select className="mt-2 block w-full rounded-md border-stone-300" value={data.birth_date_precision} onChange={(e) => setData('birth_date_precision', e.target.value)}>
                                {precisions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                            </select>
                        </div>
                        <div>
                            <InputLabel htmlFor="birth_place" value="Birthplace" />
                            <TextInput id="birth_place" className="mt-1 block w-full" value={data.birth_place} onChange={(e) => setData('birth_place', e.target.value)} />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={data.is_living} onChange={(e) => setData('is_living', e.target.checked)} />
                                This person is living
                            </label>
                        </div>
                        {!data.is_living && (
                            <>
                                <div>
                                    <InputLabel htmlFor="death_date" value="Death date" />
                                    <TextInput id="death_date" type="date" className="mt-1 block w-full" value={data.death_date || ''} onChange={(e) => setData('death_date', e.target.value)} />
                                    <select className="mt-2 block w-full rounded-md border-stone-300" value={data.death_date_precision} onChange={(e) => setData('death_date_precision', e.target.value)}>
                                        {precisions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <InputLabel htmlFor="death_place" value="Place of death" />
                                    <TextInput id="death_place" className="mt-1 block w-full" value={data.death_place} onChange={(e) => setData('death_place', e.target.value)} />
                                </div>
                            </>
                        )}
                    </div>
                    <div className="mt-4">
                        <InputLabel htmlFor="photo" value="Profile photo" />
                        <input id="photo" type="file" accept="image/*" className="mt-1 block w-full text-sm" onChange={(e) => setData('photo', e.target.files[0])} />
                    </div>
                    <div className="mt-4">
                        <InputLabel htmlFor="notes" value="Notes" />
                        <textarea id="notes" rows="4" className="mt-1 block w-full rounded-md border-stone-300" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Anything uncertain, family stories, or context" />
                    </div>
                    <PrimaryButton className="mt-6" disabled={processing}>{editing ? 'Save changes' : 'Add person'}</PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
