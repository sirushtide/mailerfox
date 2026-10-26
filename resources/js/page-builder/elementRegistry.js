const elements = new Map();

export function registerElement(def) {
    if (!def || typeof def !== 'object' || typeof def.type !== 'string') {
        console.warn('registerElement called with invalid definition', def);
        return def;
    }
    elements.set(def.type, {
        type: def.type,
        label: def.label || def.type,
        group: def.group || 'Basic',
        icon: def.icon || null,
        defaultProps: def.defaultProps || {},
        render: def.render || (() => null),
        schema: def.schema || { content: [], style: [], advanced: [] },
    });
    return def;
}

export function getElement(type) {
    return elements.get(type) || null;
}

export function getElementDefaults(type) {
    const el = getElement(type);
    return el ? { ...el.defaultProps } : {};
}

export function listElements() {
    return Array.from(elements.values());
}

export function listPalette(mode = 'page') {
    const paletteMode = typeof mode === 'string' ? mode : mode?.mode || 'page';
    const all = listElements();

    if (paletteMode === 'email') {
        const emailTypes = new Set([
            'heading',
            'rich_text',
            'image',
            'button',
            'button_group',
            'divider',
            'spacer',
            'logo',
            'link',
            'rating',
            'social_icons',
            'copyright',
            'badge',
            'icon',
        ]);
        return all.filter((el) => emailTypes.has(el.type));
    }

    // Elements hidden from the page/form builders. Kept registered so existing
    // content still renders; only removed from the insert palette.
    const hidden = new Set(['ai_prompt_input', 'divider', 'spacer']);
    if (paletteMode === 'form') {
        return all.filter((el) => !hidden.has(el.type));
    }
    return all.filter((el) => !el.type.startsWith('form_') && !hidden.has(el.type));
}

export function renderElementPreview(widget, helpers = {}) {
    if (!widget || typeof widget !== 'object') return null;
    const el = getElement(widget.type);
    if (!el || typeof el.render !== 'function') return null;
    return el.render(widget, helpers);
}

export function hasElementSchema(type, tab) {
    const el = getElement(type);
    if (!el || !el.schema) return false;
    return Array.isArray(el.schema[tab]) && el.schema[tab].length > 0;
}

export function getElementSchema(type, tab) {
    const el = getElement(type);
    if (!el || !el.schema) return [];
    return Array.isArray(el.schema[tab]) ? el.schema[tab] : [];
}
