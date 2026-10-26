export function makePrefixedWidget(widget, prefix) {
    const props = widget?.props || {};
    const prefixedProps = {};
    for (const [k, v] of Object.entries(props)) {
        if (k.startsWith(prefix)) {
            prefixedProps[k.slice(prefix.length)] = v;
        }
    }
    return { ...widget, props: prefixedProps };
}

export function makePrefixedOnChange(onChange, prefix, allProps) {
    return (patch) => {
        const newProps = { ...allProps };
        for (const [k, v] of Object.entries(patch?.props || {})) {
            newProps[prefix + k] = v;
        }
        onChange({ props: newProps });
    };
}

export function makePrefixedProps(props, prefix) {
    const out = {};
    for (const [k, v] of Object.entries(props || {})) {
        if (k.startsWith(prefix)) {
            out[k.slice(prefix.length)] = v;
        }
    }
    return out;
}
