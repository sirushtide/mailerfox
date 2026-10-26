import { cloneElement, isValidElement } from 'react';

export function Slot({ children, ...props }) {
    if (isValidElement(children)) {
        return cloneElement(children, {
            ...children.props,
            ...props,
            className: [children.props.className, props.className].filter(Boolean).join(' '),
            onClick: (e) => {
                if (typeof children.props.onClick === 'function') children.props.onClick(e);
                if (typeof props.onClick === 'function') props.onClick(e);
            },
        });
    }

    return <span {...props}>{children}</span>;
}
