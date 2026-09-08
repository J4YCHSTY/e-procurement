export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-5 py-2.5 text-[13.5px] font-bold text-on-bright transition duration-150 ease-in-out hover:-translate-y-px hover:brightness-105 focus:outline-none focus:ring-4 focus:ring-danger-soft ${
                    disabled ? 'cursor-not-allowed opacity-50 hover:translate-y-0' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
