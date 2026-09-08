import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    // `form-field` didefinisikan di resources/css/app.css - satu style input
    // yang dipakai bareng sama select & textarea di seluruh aplikasi.
    return (
        <input
            {...props}
            type={type}
            className={'form-field ' + className}
            ref={localRef}
        />
    );
});
