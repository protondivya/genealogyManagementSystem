import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
    });

    return (
        <AuthenticatedLayout header={<h1 className="font-serif text-2xl">Create a family workspace</h1>}>
            <Head title="New family" />
            <div className="mx-auto max-w-xl px-4 py-10">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('families.store'));
                    }}
                    className="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-stone-200"
                >
                    <p className="text-stone-600">Give this family a name the whole household will recognise.</p>
                    <div className="mt-6">
                        <InputLabel htmlFor="name" value="Family name" />
                        <TextInput id="name" className="mt-1 block w-full" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                        <InputError message={errors.name} className="mt-2" />
                    </div>
                    <div className="mt-4">
                        <InputLabel htmlFor="description" value="Description (optional)" />
                        <textarea
                            id="description"
                            className="mt-1 block w-full rounded-md border-stone-300"
                            rows="3"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                    </div>
                    <PrimaryButton className="mt-6" disabled={processing}>Create workspace</PrimaryButton>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
