export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center justify-center gap-2 rounded-xl border-[1.5px] border-line bg-surface px-5 py-2.5 text-[13.5px] font-bold text-ink-muted transition duration-150 ease-in-out hover:border-ink-faint hover:text-ink focus:outline-none focus:ring-4 focus:ring-accent-soft disabled:cursor-not-allowed disabled:opacity-50 ${
                    disabled ? 'cursor-not-allowed opacity-50' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
