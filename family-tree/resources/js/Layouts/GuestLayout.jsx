import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-parchment px-4 py-10 sm:justify-center">
            <Link href="/" className="flex items-center gap-3 text-forest">
                <ApplicationLogo className="h-12 w-12" />
                <span className="font-serif text-2xl font-semibold">Family Tree</span>
            </Link>
            <div className="mt-8 w-full overflow-hidden rounded-2xl bg-white px-6 py-8 shadow-sm ring-1 ring-stone-200 sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
