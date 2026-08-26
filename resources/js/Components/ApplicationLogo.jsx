/**
 * Logo aplikasi - menampilkan logo perusahaan (Visinema Pictures) dari public/images.
 * Pakai object-contain supaya rasio gambar aslinya tidak gepeng/terpotong walau
 * dipasang di berbagai ukuran container (sidebar, guest layout, landing page).
 */
export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <img
            {...props}
            src="/images/visinema-pictures.png"
            alt="Visinema Pictures"
            className={'object-contain ' + className}
        />
    );
}
