import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import {
    IconChevronDown,
    IconLayoutDashboard,
    IconLogout,
    IconMenu,
    IconUser,
    IconUsers,
    IconX,
} from '@/Components/Icons';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const NAV_ITEMS = [
    { name: 'Dashboard', route: 'dashboard', icon: IconLayoutDashboard },
    {
        name: 'Manajemen User',
        route: 'users.index',
        icon: IconUsers,
        managementOnly: true,
    },
    { name: 'Profil Saya', route: 'profile.edit', icon: IconUser },
];

function SidebarNav({ onNavigate, canManageUsers }) {
    const items = NAV_ITEMS.filter(
        (item) => !item.managementOnly || canManageUsers,
    );

    return (
        <nav className="flex flex-1 flex-col gap-[3px] px-3.5 py-4">
            {items.map((item) => {
                const active = route().current(item.route);
                const Icon = item.icon;

                return (
                    <Link
                        key={item.route}
                        href={route(item.route)}
                        onClick={onNavigate}
                        className={`flex items-center gap-[11px] rounded-[11px] px-3.5 py-2.5 text-[13.5px] font-semibold transition ${
                            active
                                ? 'bg-accent-soft text-accent-deep'
                                : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'
                        }`}
                    >
                        <Icon
                            className={`h-[18px] w-[18px] shrink-0 transition ${
                                active ? 'text-accent' : 'text-ink-faint'
                            }`}
                        />
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
            href={route('dashboard')}
            className="flex h-[92px] shrink-0 items-center justify-center border-b border-line px-5"
        >
            <ApplicationLogo className="h-[34px] w-auto shrink-0" />
        </Link>
    );
}

function SidebarFooter() {
    return (
        <div className="border-t border-line px-5 py-4 text-[11px] text-ink-faint">
            &copy; {new Date().getFullYear()} Visinema Pictures
        </div>
    );
}

export default function AuthenticatedLayout({
    header,
    title,
    subtitle,
    children,
}) {
    const user = usePage().props.auth.user;
    const [mobileOpen, setMobileOpen] = useState(false);
    // Sengaja baca can_manage_users (BUKAN role === 'it') - akses menu ini
    // independen dari role approval user, lihat App\Policies\UserPolicy.
    const canManageUsers = user.can_manage_users;

    return (
        <div className="flex min-h-screen bg-canvas">
            {/* Sidebar - desktop */}
            <aside className="sticky top-0 hidden h-screen w-[248px] shrink-0 flex-col border-r border-line bg-surface lg:flex">
                <SidebarBrand />
                <SidebarNav canManageUsers={canManageUsers} />
                <SidebarFooter />
            </aside>

            {/* Sidebar - mobile, drawer overlay */}
            {mobileOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div
                        className="fixed inset-0 bg-overlay"
                        onClick={() => setMobileOpen(false)}
                    />
                    <aside className="relative flex h-full w-[264px] flex-col border-r border-line bg-surface shadow-card">
                        <button
                            onClick={() => setMobileOpen(false)}
                            className="absolute right-3 top-3 rounded-lg p-2 text-ink-faint transition hover:bg-surface-sunken hover:text-ink"
                            aria-label="Tutup menu"
                        >
                            <IconX className="h-5 w-5" />
                        </button>
                        <SidebarBrand />
                        <SidebarNav
                            canManageUsers={canManageUsers}
                            onNavigate={() => setMobileOpen(false)}
                        />
                        <SidebarFooter />
                    </aside>
                </div>
            )}

            <div className="flex min-w-0 flex-1 flex-col">
                {/* Top bar */}
                <header className="sticky top-0 z-20 flex items-center justify-between gap-5 border-b border-line bg-surface-glass px-5 py-4 backdrop-blur-[10px] sm:px-8 sm:py-5">
                    <div className="flex min-w-0 items-center gap-3">
                        <button
                            onClick={() => setMobileOpen(true)}
                            className="rounded-lg p-2 text-ink-muted transition hover:bg-surface-sunken hover:text-ink lg:hidden"
                            aria-label="Buka menu"
                        >
                            <IconMenu className="h-5 w-5" />
                        </button>

                        <div className="min-w-0">
                            {title ? (
                                <>
                                    <h1 className="truncate text-[17px] font-extrabold tracking-[-0.01em] text-ink sm:text-[19px]">
                                        {title}
                                    </h1>
                                    {subtitle && (
                                        <p className="mt-0.5 truncate text-[12.5px] text-ink-muted">
                                            {subtitle}
                                        </p>
                                    )}
                                </>
                            ) : (
                                header
                            )}
                        </div>
                    </div>

                    <div className="flex shrink-0 items-center gap-2.5">
                        <ThemeToggle />

                        <Dropdown align="right" width="48">
                            <Dropdown.Trigger>
                                <button className="flex items-center gap-2.5 rounded-full border border-line bg-surface py-[5px] pe-2.5 ps-[5px] transition hover:border-accent">
                                    <span className="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-full bg-accent-soft text-[13px] font-bold text-accent-deep">
                                        {user.name?.charAt(0).toUpperCase()}
                                    </span>
                                    <span className="hidden text-left sm:block">
                                        <span className="block text-[12.5px] font-bold leading-tight text-ink">
                                            {user.name?.split(' ')[0]}
                                        </span>
                                        <span className="block text-[11px] leading-tight text-ink-muted">
                                            {user.position ?? user.role}
                                        </span>
                                    </span>
                                    <IconChevronDown className="hidden h-[15px] w-[15px] text-ink-faint sm:block" />
                                </button>
                            </Dropdown.Trigger>

                            <Dropdown.Content>
                                <Dropdown.Link
                                    href={route('profile.edit')}
                                    className="flex items-center gap-2.5"
                                >
                                    <IconUser className="h-4 w-4 text-ink-faint" />
                                    Profil Saya
                                </Dropdown.Link>
                                <Dropdown.Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="flex items-center gap-2.5"
                                >
                                    <IconLogout className="h-4 w-4 text-ink-faint" />
                                    Log Out
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </header>

                {/*
                    Kontainer konten dipusatkan (mx-auto) dan dibatasi lebarnya.
                    Tanpa mx-auto, di layar lebar kontennya nempel ke kiri dan
                    nyisa ruang kosong besar di kanan.
                */}
                <main className="mx-auto flex w-full max-w-[1280px] flex-col gap-[22px] px-5 pb-12 pt-7 sm:px-8 lg:px-10">
                    {children}
                </main>
            </div>
        </div>
    );
}
