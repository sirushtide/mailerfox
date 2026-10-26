export const DEFAULT_GLOBALS = {
    colors: [
        { id: 'primary', label: 'Primary', value: '#2563eb' },
        { id: 'secondary', label: 'Secondary', value: '#111827' },
        { id: 'text', label: 'Text', value: '#374151' },
        { id: 'accent', label: 'Accent', value: '#f59e0b' },
        { id: 'muted', label: 'Muted', value: '#9ca3af' },
        { id: 'success', label: 'Success', value: '#22c55e' },
        { id: 'warning', label: 'Warning', value: '#eab308' },
        { id: 'danger', label: 'Danger', value: '#ef4444' },
    ],
    typography: [
        {
            id: 'primary',
            label: 'Primary Heading',
            fontFamily: 'Inter',
            fontWeight: '700',
            fontSize: 32,
            lineHeight: 1.2,
            letterSpacing: 0,
            textTransform: 'none',
        },
        {
            id: 'secondary',
            label: 'Secondary Heading',
            fontFamily: 'Inter',
            fontWeight: '600',
            fontSize: 24,
            lineHeight: 1.3,
            letterSpacing: 0,
            textTransform: 'none',
        },
        {
            id: 'text',
            label: 'Body Text',
            fontFamily: 'Inter',
            fontWeight: '400',
            fontSize: 16,
            lineHeight: 1.6,
            letterSpacing: 0,
            textTransform: 'none',
        },
        {
            id: 'accent',
            label: 'Accent Text',
            fontFamily: 'Inter',
            fontWeight: '500',
            fontSize: 14,
            lineHeight: 1.5,
            letterSpacing: 0,
            textTransform: 'none',
        },
    ],
    buttons: [
        {
            id: 'primary',
            label: 'Primary Button',
            props: {
                textColor: '#ffffff',
                backgroundColor: '#2563eb',
                borderStyle: 'solid',
                borderColor: '#2563eb',
                borderWidth: 1,
                borderRadius: 6,
                paddingTop: 12,
                paddingRight: 24,
                paddingBottom: 12,
                paddingLeft: 24,
            },
        },
        {
            id: 'secondary',
            label: 'Secondary Button',
            props: {
                textColor: '#111827',
                backgroundColor: '#ffffff',
                borderStyle: 'solid',
                borderColor: '#d1d5db',
                borderWidth: 1,
                borderRadius: 6,
                paddingTop: 12,
                paddingRight: 24,
                paddingBottom: 12,
                paddingLeft: 24,
            },
        },
    ],
};

export function normalizeGlobals(globals) {
    const g = globals && typeof globals === 'object' ? globals : {};
    return {
        colors: Array.isArray(g.colors) ? g.colors : DEFAULT_GLOBALS.colors,
        typography: Array.isArray(g.typography) ? g.typography : DEFAULT_GLOBALS.typography,
        buttons: Array.isArray(g.buttons) ? g.buttons : DEFAULT_GLOBALS.buttons,
    };
}

export function toGlobalsMap(globals) {
    const g = normalizeGlobals(globals);
    return {
        colors: g.colors.reduce((acc, c) => { acc[c.id] = c; return acc; }, {}),
        typography: g.typography.reduce((acc, t) => { acc[t.id] = t; return acc; }, {}),
        buttons: g.buttons.reduce((acc, b) => { acc[b.id] = b; return acc; }, {}),
    };
}
