import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const links = [
    { href: 'dashboard', label: 'Dashboard' },
    { href: 'tree.show', label: 'Family Tree' },
    { href: 'people.index', label: 'People' },
    { href: 'timeline.index', label: 'Timeline' },
    { href: 'media.index', label: 'Media' },
    { href: 'sources.index', label: 'Sources' },
    { href: 'path.show', label: 'How related?' },
    { href: 'settings.index', label: 'Settings' },
];

export default function AuthenticatedLayout({ header, children }) {
    const page = usePage();
    const { auth, currentFamily, families, flash } = page.props;
    const user = auth.user;
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const [query, setQuery] = useState('');
    const [notice, setNotice] = useState(flash?.success || null);

    useEffect(() => {
        setNotice(flash?.success || null);
    }, [flash?.success]);

    const search = (e) => {
        e.preventDefault();
        if (!query.trim()) {
            return;
        }
        router.get(route('people.search'), { q: query });
    };

    return (
        <div className="min-h-screen bg-parchment text-ink">
            <nav className="border-b border-stone-200 bg-white/90 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex min-w-0 items-center">
                            <Link href={route('dashboard')} className="flex shrink-0 items-center gap-2 text-forest">
                                <ApplicationLogo className="h-8 w-8" />
                                <span className="hidden font-serif text-lg font-semibold sm:block">Family Tree</span>
                            </Link>
                            <div className="hidden space-x-6 sm:-my-px sm:ms-8 lg:flex">
                                {links.map((item) => (
                                    <NavLink
                                        key={item.href}
                                        href={route(item.href)}
                                        active={route().current(item.href.replace('.index', '*').replace('.show', '*'))}
                                    >
                                        {item.label}
                                    </NavLink>
                                ))}
                            </div>
                        </div>

                        <div className="hidden items-center gap-4 sm:flex">
                            <form onSubmit={search} className="hidden md:block">
                                <label htmlFor="global-search" className="sr-only">
                                    Search for a person
                                </label>
                                <input
                                    id="global-search"
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                    placeholder="Search people"
                                    className="w-48 rounded-full border-stone-300 px-4 py-2 text-sm focus:border-forest focus:ring-forest"
                                />
                            </form>
                            {currentFamily && (
                                <span className="hidden rounded-full bg-forest/10 px-3 py-1 text-xs font-medium text-forest xl:inline">
                                    Private — {currentFamily.name}
                                </span>
                            )}
                            {families?.length > 1 && (
                                <select
                                    aria-label="Switch family"
                                    className="rounded-md border-stone-300 text-sm"
                                    value={currentFamily?.id || ''}
                                    onChange={(e) =>
                                        router.post(route('families.switch', e.target.value))
                                    }
                                >
                                    {families.map((family) => (
                                        <option key={family.id} value={family.id}>
                                            {family.name}
                                        </option>
                                    ))}
                                </select>
                            )}
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <span className="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            className="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-stone-600 hover:text-ink"
                                        >
                                            {user.name}
                                            <svg className="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </Dropdown.Trigger>
                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                    <Dropdown.Link href={route('export.index')}>Print / export</Dropdown.Link>
                                    <Dropdown.Link href={route('logout')} method="post" as="button">
                                        Log out
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>

                        <div className="-me-2 flex items-center lg:hidden">
                            <button
                                onClick={() => setShowingNavigationDropdown((open) => !open)}
                                className="inline-flex items-center justify-center rounded-md p-2 text-stone-500 hover:bg-stone-100"
                                aria-label="Open menu"
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path className={showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div className={(showingNavigationDropdown ? 'block' : 'hidden') + ' lg:hidden'}>
                    <div className="space-y-1 pb-3 pt-2">
                        {links.map((item) => (
                            <ResponsiveNavLink
                                key={item.href}
                                href={route(item.href)}
                                active={route().current(item.href)}
                            >
                                {item.label}
                            </ResponsiveNavLink>
                        ))}
                    </div>
                    <div className="border-t border-stone-200 px-4 py-4">
                        <div className="font-medium text-ink">{user.name}</div>
                        <div className="text-sm text-stone-500">{user.email}</div>
                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>Profile</ResponsiveNavLink>
                            <ResponsiveNavLink method="post" href={route('logout')} as="button">
                                Log out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white/70">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                </header>
            )}

            {notice && (
                <div className="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                    <div className="flex items-start justify-between rounded-lg border border-forest/20 bg-forest/10 px-4 py-3 text-forest">
                        <p>{notice}</p>
                        <button type="button" className="ml-4 text-sm underline" onClick={() => setNotice(null)}>
                            Dismiss
                        </button>
                    </div>
                </div>
            )}

            {flash?.invite_url && (
                <div className="mx-auto max-w-7xl px-4 pt-3 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Invite link: <span className="break-all font-mono">{flash.invite_url}</span>
                    </div>
                </div>
            )}

            <main>{children}</main>
        </div>
    );
}
