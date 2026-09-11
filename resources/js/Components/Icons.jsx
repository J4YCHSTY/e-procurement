/**
 * Kumpulan ikon yang dipakai bareng di seluruh aplikasi (sidebar, dashboard,
 * tabel, badge status, dsb).
 *
 * Gaya ikonnya mengikuti Lucide: kanvas 24x24, stroke 1.8, ujung garis
 * membulat, tanpa fill. Ditulis tangan sebagai inline SVG - bukan install
 * paket `lucide-react` - biar gak nambah dependency baru dan bundle-nya tetap
 * kecil (cuma ikon yang benar-benar dipakai yang ikut ke-build).
 *
 * Semua komponen nerima props biasa (className, dst) yang diteruskan ke <svg>,
 * jadi ukurannya diatur dari luar pakai class Tailwind (h-4 w-4, h-5 w-5, ...).
 */
const base = {
  fill: "none",
  viewBox: "0 0 24 24",
  strokeWidth: 1.8,
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
};

/* --------------------------------- Navigasi -------------------------------- */

export function IconLayoutDashboard(props) {
  return (
    <svg {...base} {...props}>
      <rect x="3" y="3" width="7" height="9" rx="1.5" />
      <rect x="14" y="3" width="7" height="5" rx="1.5" />
      <rect x="14" y="12" width="7" height="9" rx="1.5" />
      <rect x="3" y="16" width="7" height="5" rx="1.5" />
    </svg>
  );
}

export function IconHome(props) {
  return (
    <svg {...base} {...props}>
      <path d="M3 10.5 12 3l9 7.5" />
      <path d="M5.5 9.5V20a1 1 0 0 0 1 1H9.5v-5.5h5V21h3a1 1 0 0 0 1-1V9.5" />
    </svg>
  );
}

export function IconUsers(props) {
  return (
    <svg {...base} {...props}>
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
      <circle cx="9" cy="7" r="4" />
      <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </svg>
  );
}

export function IconUser(props) {
  return (
    <svg {...base} {...props}>
      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
      <circle cx="12" cy="7" r="4" />
    </svg>
  );
}

export function IconLogout(props) {
  return (
    <svg {...base} {...props}>
      <path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3" />
      <path d="m15.5 16.5 4.5-4.5-4.5-4.5" />
      <path d="M20 12H9" />
    </svg>
  );
}

export function IconMenu(props) {
  return (
    <svg {...base} {...props}>
      <path d="M4 6h16M4 12h16M4 18h16" />
    </svg>
  );
}

export function IconX(props) {
  return (
    <svg {...base} {...props}>
      <path d="M18 6 6 18" />
      <path d="m6 6 12 12" />
    </svg>
  );
}

export function IconChevronDown(props) {
  return (
    <svg {...base} {...props}>
      <path d="m6 9 6 6 6-6" />
    </svg>
  );
}

/* --------------------------------- Aksi ----------------------------------- */

export function IconPlus(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="12" cy="12" r="9.5" />
      <path d="M12 8v8M8 12h8" />
    </svg>
  );
}

export function IconPencil(props) {
  return (
    <svg {...base} {...props}>
      <path d="M12 20h9" />
      <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
    </svg>
  );
}

export function IconLockClosed(props) {
  return (
    <svg {...base} {...props}>
      <rect x="5" y="11" width="14" height="10" rx="2" />
      <path d="M8 11V7a4 4 0 0 1 8 0v4" />
    </svg>
  );
}

export function IconPower(props) {
  return (
    <svg {...base} {...props}>
      <path d="M12 2v8" />
      <path d="M18.4 6.6a9 9 0 1 1-12.8 0" />
    </svg>
  );
}

export function IconSearch(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="11" cy="11" r="7.5" />
      <path d="m21 21-4.35-4.35" />
    </svg>
  );
}

export function IconEye(props) {
  return (
    <svg {...base} {...props}>
      <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
      <circle cx="12" cy="12" r="3" />
    </svg>
  );
}

export function IconEyeOff(props) {
  return (
    <svg {...base} {...props}>
      <path d="M10.6 6.7A8.9 8.9 0 0 1 12 6.6c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3 3.9" />
      <path d="M6.5 7.9A17 17 0 0 0 2.5 13s3.5 6.5 9.5 6.5a9.4 9.4 0 0 0 4.2-1" />
      <path d="M10 10.2a2.9 2.9 0 0 0 4.1 4.1" />
      <path d="m3 3 18 18" />
    </svg>
  );
}

/* ------------------------------ Tema (toggle) ------------------------------ */

export function IconSun(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="12" cy="12" r="4.2" />
      <path d="M12 3v2.2M12 18.8V21M4.9 4.9l1.55 1.55M17.55 17.55 19.1 19.1M3 12h2.2M18.8 12H21M4.9 19.1l1.55-1.55M17.55 6.45 19.1 4.9" />
    </svg>
  );
}

export function IconMoon(props) {
  return (
    <svg {...base} {...props}>
      <path d="M20 14.1A8.3 8.3 0 0 1 9.9 4a8.3 8.3 0 1 0 10.1 10.1Z" />
    </svg>
  );
}

/* ------------------------------ Status & data ------------------------------ */

export function IconClipboardList(props) {
  return (
    <svg {...base} {...props}>
      <rect x="8" y="2" width="8" height="4" rx="1" />
      <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
      <path d="M9 12h6" />
      <path d="M9 16h6" />
    </svg>
  );
}

export function IconClock(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="12" cy="12" r="9.5" />
      <polyline points="12 7 12 12 15.5 14" />
    </svg>
  );
}

export function IconCheckBadge(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="12" cy="12" r="9.5" />
      <path d="m8.5 12.5 2.2 2.2 4.8-4.8" />
    </svg>
  );
}

export function IconCheckCircle(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="12" cy="12" r="9.5" />
      <path d="m8.5 12.5 2.2 2.2 4.8-4.8" />
    </svg>
  );
}

export function IconXCircle(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="12" cy="12" r="9.5" />
      <path d="m14.5 9.5-5 5" />
      <path d="m9.5 9.5 5 5" />
    </svg>
  );
}

export function IconCheckCheck(props) {
  return (
    <svg {...base} {...props}>
      <path d="M18 6 7 17l-5-5" />
      <path d="m22 10-7.5 7.5L13 16" />
    </svg>
  );
}

export function IconHistory(props) {
  return (
    <svg {...base} {...props}>
      <path d="M3 3v5h5" />
      <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8" />
      <path d="M12 7v5l4 2" />
    </svg>
  );
}

export function IconInbox(props) {
  return (
    <svg {...base} {...props}>
      <path d="M20 12h-5l-1.5 2.5h-3L9 12H4" />
      <path d="M5.5 5.6 3 12v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6l-2.5-6.4A2 2 0 0 0 16.6 4H7.4a2 2 0 0 0-1.9 1.6Z" />
    </svg>
  );
}

export function IconFile(props) {
  return (
    <svg {...base} {...props}>
      <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
      <polyline points="14 2 14 8 20 8" />
      <path d="M16 13H8" />
      <path d="M16 17H8" />
    </svg>
  );
}

export function IconShield(props) {
  return (
    <svg {...base} {...props}>
      <path d="M12 2 4 5v6c0 5 3.4 8.6 8 11 4.6-2.4 8-6 8-11V5Z" />
      <path d="m9 12 2 2 4-4" />
    </svg>
  );
}

export function IconKey(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="7.5" cy="15.5" r="4.5" />
      <path d="m10.5 12.5 8-8" />
      <path d="m17 8 2 2" />
      <path d="m14.5 10.5 2 2" />
    </svg>
  );
}

export function IconAlertTriangle(props) {
  return (
    <svg {...base} {...props}>
      <path d="M12 3 2 21h20L12 3Z" />
      <path d="M12 10v5" />
      <path d="M12 17.5h.01" />
    </svg>
  );
}

/* ---------------------------- Kategori & profil ---------------------------- */

export function IconDevice(props) {
  return (
    <svg {...base} {...props}>
      <rect x="6" y="6" width="12" height="12" rx="2" />
      <rect x="10" y="10" width="4" height="4" />
      <path d="M10 2v2M14 2v2M10 20v2M14 20v2M2 10h2M2 14h2M20 10h2M20 14h2" />
    </svg>
  );
}

export function IconMonitor(props) {
  return (
    <svg {...base} {...props}>
      <rect x="2" y="4" width="20" height="13" rx="2" />
      <path d="M8 21h8" />
      <path d="M12 17v4" />
    </svg>
  );
}

export function IconApp(props) {
  return (
    <svg {...base} {...props}>
      <polyline points="16 18 22 12 16 6" />
      <polyline points="8 6 2 12 8 18" />
    </svg>
  );
}

export function IconMail(props) {
  return (
    <svg {...base} {...props}>
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="m3.5 6.5 8.5 6.5 8.5-6.5" />
    </svg>
  );
}

export function IconBuilding(props) {
  return (
    <svg {...base} {...props}>
      <rect x="4" y="3" width="10" height="18" rx="1" />
      <path d="M14 8h6v13" />
      <path d="M8 7h.01M8 11h.01M8 15h.01" />
    </svg>
  );
}

export function IconBriefcase(props) {
  return (
    <svg {...base} {...props}>
      <rect x="2" y="7" width="20" height="14" rx="2" />
      <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
    </svg>
  );
}

export function IconUpload(props) {
  return (
    <svg {...base} {...props}>
      <path d="M12 16V4" />
      <path d="m7.5 8.5 4.5-4.5 4.5 4.5" />
      <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
    </svg>
  );
}

export function IconTrash(props) {
  return (
    <svg {...base} {...props}>
      <path d="M4 7h16" />
      <path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
      <path d="M6.5 7 7 20a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l.5-13" />
      <path d="M10 11v6M14 11v6" />
    </svg>
  );
}

export function IconImage(props) {
  return (
    <svg {...base} {...props}>
      <rect x="3" y="4" width="18" height="16" rx="2" />
      <circle cx="8.5" cy="9.5" r="1.5" />
      <path d="m4 17 4.5-4.5 3 3L15 12l5 5" />
    </svg>
  );
}

/**
 * Truk pengiriman - dipakai buat status "Barang Dalam Perjalanan".
 */
export function IconTruck(props) {
  return (
    <svg {...base} {...props}>
      <path d="M3 7.5A1.5 1.5 0 0 1 4.5 6H14a1 1 0 0 1 1 1v9H4.5A1.5 1.5 0 0 1 3 14.5v-7Z" />
      <path d="M15 10h3.2a1 1 0 0 1 .83.44l1.8 2.7a1 1 0 0 1 .17.56V15a1 1 0 0 1-1 1h-5v-6Z" />
      <circle cx="7.5" cy="18" r="1.8" />
      <circle cx="17" cy="18" r="1.8" />
    </svg>
  );
}

/**
 * Pena di atas dokumen - penanda BAST yang menunggu tanda tangan.
 */
export function IconSignature(props) {
  return (
    <svg {...base} {...props}>
      <path d="M3 18c2.2 0 2.2-9 4.4-9s2.2 9 4.4 9c1.5 0 2-2 3.2-2" />
      <path d="M15 20h6" />
      <path d="M17.5 12.8 20 10.3a1.3 1.3 0 0 1 1.9 1.9l-2.5 2.5-2.4.6.5-2.5Z" />
    </svg>
  );
}

/**
 * Panah kembali - dipakai di tautan "Kembali" halaman detail.
 */
export function IconArrowLeft(props) {
  return (
    <svg {...base} {...props}>
      <path d="M19 12H5" />
      <path d="m11 18-6-6 6-6" />
    </svg>
  );
}

/**
 * Titik linimasa dengan garis - penanda kejadian yang sudah lewat.
 */
export function IconDotCircle(props) {
  return (
    <svg {...base} {...props}>
      <circle cx="12" cy="12" r="8.5" />
      <circle cx="12" cy="12" r="3" />
    </svg>
  );
}
