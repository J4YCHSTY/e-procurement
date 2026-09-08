export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'h-4 w-4 rounded border-[1.5px] border-line bg-canvas text-accent shadow-none focus:ring-4 focus:ring-accent-soft focus:ring-offset-0 ' +
                className
            }
        />
    );
}
