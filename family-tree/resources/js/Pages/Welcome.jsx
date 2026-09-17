import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth, canLogin, canRegister }) {
    return (
        <>
            <Head title="Family Tree" />
            <div className="min-h-screen bg-parchment text-ink">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-3 text-forest">
                        <ApplicationLogo className="h-10 w-10" />
                        <span className="font-serif text-xl font-semibold">Family Tree</span>
                    </div>
                    <nav className="flex gap-3 text-sm">
                        {auth.user ? (
                            <Link href={route('dashboard')} className="rounded-full bg-forest px-4 py-2 text-white">
                                Open workspace
                            </Link>
                        ) : (
                            <>
                                {canLogin && (
                                    <Link href={route('login')} className="rounded-full px-4 py-2 text-forest">
                                        Log in
                                    </Link>
                                )}
                                {canRegister && (
                                    <Link href={route('register')} className="rounded-full bg-forest px-4 py-2 text-white">
                                        Create account
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>
                </header>
                <main className="mx-auto max-w-6xl px-6 py-16">
                    <p className="text-sm uppercase tracking-[0.2em] text-forest">Private family history</p>
                    <h1 className="mt-4 max-w-3xl font-serif text-5xl leading-tight">
                        Record relatives, stories, and photographs — then see how everyone is connected.
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-stone-600">
                        Built for families. Approximate dates are welcome. Living people stay private.
                        Invite only the relatives you trust.
                    </p>
                    <div className="mt-10 flex flex-wrap gap-4">
                        <Link href={route('register')} className="rounded-full bg-forest px-6 py-3 text-white">
                            Start your family workspace
                        </Link>
                        <Link href={route('login')} className="rounded-full border border-forest px-6 py-3 text-forest">
                            I already have an account
                        </Link>
                    </div>
                    <div className="mt-16 grid gap-6 md:grid-cols-3">
                        {[
                            ['Add people gently', 'Unknown years, maiden names, and notes about uncertainty are first-class.'],
                            ['See the tree', 'Pan, zoom, and jump from any person. Ask how two relatives are connected.'],
                            ['Keep evidence', 'Attach photos, certificates, and sources with a confidence level.'],
                        ].map(([title, copy]) => (
                            <div key={title} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                                <h2 className="font-serif text-xl">{title}</h2>
                                <p className="mt-2 text-stone-600">{copy}</p>
                            </div>
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
