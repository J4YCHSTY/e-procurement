export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <p
            {...props}
            className={'mt-1.5 text-[12.5px] font-medium text-danger ' + className}
        >
            {message}
        </p>
    ) : null;
}
