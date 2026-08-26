import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import {
    IconChevronDown,
    IconClipboardList,
    IconHome,
    IconLogout,
    IconMenu,
    IconUser,
    IconUsers,
    IconX,
} from '@/Components/Icons';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const NAV_ITEMS = [
    { name: 'Dashboard', route: 'dashboard', icon: IconHome },
    { name: 'Manajemen User', route: 'users.index', icon: IconUsers, itOnly: true },
    { name: 'Profil Saya', route: 'profile.edit', icon: IconUser },
];

function SidebarNav({ onNavigate, isIt }) {
    const items = NAV_ITEMS.filter((item) => !item.itOnly || isIt);

    return (
        <nav className="flex-1 space-y-1 px-3">
            {items.map((item) => {
                const active = route().current(item.route);
                const Icon = item.icon;

                return (
                    <Link
                        key={item.route}
                        href={route(item.route)}
                        onClick={onNavigate}
                        className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                            active
                                ? 'bg-brand-50 text-brand-700'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                        }`}
                    >
                        <Icon className={`h-5 w-5 ${active ? 'text-brand-600' : 'text-slate-400'}`} />
                        {item.name}
                    </Link>
                );
            })}
        </nav>
    );
}

function SidebarBrand() {
    return (
        <Link
            href="/"
            className="flex h-28 items-center justify-center border-b border-slate-100 px-6"
        >
            <ApplicationLogo className="h-20 w-auto shrink-0" />
        </Link>
    );
}

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const [mobileOpen, setMobileOpen] = useState(false);
    const isIt = user.role === 'it';

    return (
        <div className="min-h-screen bg-slate-50">
            {/* Sidebar - desktop, fixed di kiri */}
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white lg:flex">
                <SidebarBrand />
                <SidebarNav isIt={isIt} />
                <div className="border-t border-slate-100 p-4 text-xs text-slate-400">
                    &copy; {new Date().getFullYear ? new Date().getFullYear() : ''} E-Procurement
                </div>
            </aside>

            {/* Sidebar - mobile, drawer overlay */}
            {mobileOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div
                        className="fixed inset-0 bg-slate-900/50"
                        onClick={() => setMobileOpen(false)}
                    />
                    <aside className="relative flex h-full w-64 flex-col bg-white shadow-xl">
                        <div className="flex items-center justify-between pe-3">
                            <SidebarBrand />
                            <button
                                onClick={() => setMobileOpen(false)}
                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                            >
                                <IconX className="h-5 w-5" />
                            </button>
                        </div>
                        <SidebarNav isIt={isIt} onNavigate={() => setMobileOpen(false)} />
                    </aside>
                </div>
            )}

            <div className="flex min-h-screen flex-col lg:pl-64">
                {/* Top bar */}
                <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button
                        onClick={() => setMobileOpen(true)}
                        className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                    >
                        <IconMenu className="h-5 w-5" />
                    </button>

                    <div className="min-w-0 flex-1">
                        {header ?? (
                            <span className="text-sm font-medium text-slate-400">
                                &nbsp;
                            </span>
                        )}
                    </div>

                    <Dropdown align="right" width="48">
                        <Dropdown.Trigger>
                            <button className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50">
                                <span className="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                    {user.name?.charAt(0).toUpperCase()}
                                </span>
                                <span className="hidden text-left sm:block">
                                    <span className="block text-sm font-medium leading-tight text-slate-700">
                                        {user.name}
                                    </span>
                                    <span className="block text-xs leading-tight text-slate-400">
                                        {user.position ?? user.role}
                                    </span>
                                </span>
                                <IconChevronDown className="hidden h-4 w-4 text-slate-400 sm:block" />
                            </button>
                        </Dropdown.Trigger>

                        <Dropdown.Content contentClasses="py-1.5 bg-white">
                            <Dropdown.Link href={route('profile.edit')} className="flex items-center gap-2">
                                <IconUser className="h-4 w-4 text-slate-400" />
                                Profil Saya
                            </Dropdown.Link>
                            <Dropdown.Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="flex items-center gap-2"
                            >
                                <IconLogout className="h-4 w-4 text-slate-400" />
                                Log Out
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </header>

                <main className="flex-1">{children}</main>
            </div>
        </div>
    );
}
