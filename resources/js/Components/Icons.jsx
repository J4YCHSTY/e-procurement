/**
 * Kumpulan ikon outline kecil yang dipakai bareng di seluruh aplikasi
 * (sidebar, dashboard, badge status, dsb). Ditulis tangan sebagai inline SVG
 * biar gak nambah dependency baru (gak perlu install package ikon).
 * Semua nerima props biasa (className, dst) yang diteruskan ke elemen <svg>.
 */
const base = {
    fill: 'none',
    viewBox: '0 0 24 24',
    strokeWidth: 1.75,
    stroke: 'currentColor',
};

export function IconHome(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.28 12M4.5 9.75V21a.75.75 0 00.75.75H9.5a.75.75 0 00.75-.75v-4.5a.75.75 0 01.75-.75h2.5a.75.75 0 01.75.75V21a.75.75 0 00.75.75h4.25a.75.75 0 00.75-.75V9.75" />
        </svg>
    );
}

export function IconClipboardList(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 3.75H6.912a2.25 2.25 0 00-2.25 2.25v13.5a2.25 2.25 0 002.25 2.25h10.176a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H15M9 3.75c0 .621.504 1.125 1.125 1.125h3.75c.621 0 1.125-.504 1.125-1.125M9 3.75c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125m-6 8.25h6m-6 3.75h6M9 12h.008v.008H9V12z" />
        </svg>
    );
}

export function IconCheckBadge(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75l1.75 1.75L15 10m5.25 2a9.25 9.25 0 11-18.5 0 9.25 9.25 0 0118.5 0z" />
        </svg>
    );
}

export function IconUser(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
        </svg>
    );
}

export function IconLogout(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0116.5 21h-6a2.25 2.25 0 01-2.25-2.25V15M12 9l-4.5 3 4.5 3M3 12h11.25" />
        </svg>
    );
}

export function IconMenu(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
        </svg>
    );
}

export function IconX(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    );
}

export function IconChevronDown(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    );
}

export function IconPlus(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    );
}

export function IconDevice(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12v9a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 14.25v-9m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0v.75A2.25 2.25 0 0118.75 8.25H5.25A2.25 2.25 0 013 6v-.75" />
        </svg>
    );
}

export function IconApp(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
        </svg>
    );
}

export function IconClock(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
        </svg>
    );
}

export function IconInbox(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 8.25l1.5-4.5A1.5 1.5 0 016 2.75h12a1.5 1.5 0 011.5 1l1.5 4.5m-15 0v9.75A1.5 1.5 0 007.5 21h9a1.5 1.5 0 001.5-1.5V8.25m-15 0h4.5a1 1 0 011 .8l.4 2a1 1 0 001 .8h2.2a1 1 0 001-.8l.4-2a1 1 0 011-.8H21" />
        </svg>
    );
}

export function IconUsers(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0112.749 0zM15.75 8.25a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a6.373 6.373 0 010-1.09" />
        </svg>
    );
}

export function IconLockClosed(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
        </svg>
    );
}

export function IconPower(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
        </svg>
    );
}

export function IconPencil(props) {
    return (
        <svg {...base} {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
        </svg>
    );
}
