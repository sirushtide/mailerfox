export function getValueByPath(obj, path) {
    if (!path) return undefined;
    const parts = path.split('.');
    let current = obj;
    for (const part of parts) {
        if (current == null || typeof current !== 'object') return undefined;
        current = current[part];
    }
    return current;
}

export function setValueByPath(obj, path, value) {
    if (!path) return obj;
    const parts = path.split('.');
    const last = parts.pop();
    let current = obj;
    for (const part of parts) {
        if (current[part] == null || typeof current[part] !== 'object') {
            current[part] = {};
        }
        current = current[part];
    }
    current[last] = value;
    return obj;
}

export function createBoundPatch(binds, value) {
    if (typeof binds === 'string') {
        return setValueByPath({}, binds, value);
    }
    if (!binds || typeof binds !== 'object') return {};
    const patch = {};
    for (const key of Object.keys(binds)) {
        const path = binds[key];
        if (path) setValueByPath(patch, path, value);
    }
    return patch;
}

export function resolveUnit(value, fallbackUnit = 'px') {
    if (value === undefined || value === null || value === '') return undefined;
    if (typeof value === 'number') return `${value}${fallbackUnit}`;
    if (typeof value === 'string') {
        const num = Number(value);
        if (Number.isFinite(num)) return `${num}${fallbackUnit}`;
        return value;
    }
    if (value && typeof value === 'object') {
        const unit = value.unit || fallbackUnit;
        const num = Number(value.value);
        if (Number.isFinite(num)) {
            return unit === 'auto' ? 'auto' : `${num}${unit}`;
        }
        if (value.value === 'auto') return 'auto';
    }
    return undefined;
}

export function parseUnit(value) {
    if (value === undefined || value === null || value === '') return { value: '', unit: 'px' };
    if (typeof value === 'number') return { value, unit: 'px' };
    if (typeof value === 'string') {
        const m = value.trim().match(/^(-?\d+(?:\.\d+)?)(px|em|rem|%|vw|vh|deg|s|auto)?$/);
        if (m) return { value: Number(m[1]), unit: m[2] || 'px' };
        return { value: value, unit: 'px' };
    }
    if (value && typeof value === 'object') {
        return { value: value.value ?? '', unit: value.unit || 'px' };
    }
    return { value: '', unit: 'px' };
}

export function isGlobalToken(value) {
    return value && typeof value === 'object' && typeof value.global === 'string' && value.global;
}

export function resolveGlobalToken(value, globalsMap, resolveFn) {
    if (!isGlobalToken(value)) return value;
    const token = value.global;
    if (!globalsMap || typeof resolveFn !== 'function') return value;
    return resolveFn(token, globalsMap);
}

export function resolveColorToken(value, globals) {
    if (isGlobalToken(value)) {
        const id = value.global;
        if (globals && typeof globals.resolveColor === 'function') {
            const resolved = globals.resolveColor(value);
            if (typeof resolved === 'string' && resolved) return resolved;
        }
        const map = globals?.map || globals;
        const fromMap = map?.colors?.[id];
        if (fromMap && typeof fromMap.value === 'string') return fromMap.value;
        const fromList = globals?.colors?.find?.((c) => c && c.id === id);
        if (fromList && typeof fromList.value === 'string') return fromList.value;
        return '#000000';
    }
    return value;
}

export function resolveTypographyToken(value, globals) {
    if (isGlobalToken(value)) {
        return globals?.typography?.[value.global] || globals?.typography?.find?.((t) => t.id === value.global) || {};
    }
    return value;
}

export function resolveButtonToken(value, globals) {
    if (isGlobalToken(value)) {
        return globals?.buttons?.[value.global] || globals?.buttons?.find?.((b) => b.id === value.global) || {};
    }
    return value;
}
