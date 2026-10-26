function compare(actual, operator, expected) {
    const right = Array.isArray(expected) ? expected : [expected];
    const left = actual === undefined || actual === null ? '' : actual;

    switch (operator) {
        case '==':
        case '===':
        case undefined:
        case null:
            return right.some((v) => left === v || String(left) === String(v));
        case '!=':
        case '!==':
            return !right.some((v) => left === v || String(left) === String(v));
        case 'in':
            return right.some((v) => Array.isArray(v) ? v.includes(left) : v === left || String(v) === String(left));
        case '!in':
            return !right.some((v) => Array.isArray(v) ? v.includes(left) : v === left || String(v) === String(left));
        case '>':
            return Number(left) > Number(right[0]);
        case '>=':
            return Number(left) >= Number(right[0]);
        case '<':
            return Number(left) < Number(right[0]);
        case '<=':
            return Number(left) <= Number(right[0]);
        case 'between':
            return Number(left) >= Number(right[0]) && Number(left) <= Number(right[1]);
        case 'empty':
            return left === '' || left === null || left === undefined || (Array.isArray(left) && left.length === 0);
        case 'not_empty':
            return left !== '' && left !== null && left !== undefined && (!Array.isArray(left) || left.length > 0);
        default:
            return false;
    }
}

export function evaluateCondition(condition, values) {
    if (!condition || typeof condition !== 'object') return true;

    if (Array.isArray(condition)) {
        return condition.every((c) => evaluateCondition(c, values));
    }

    if (condition.relation === 'or') {
        return condition.terms?.some((c) => evaluateCondition(c, values)) ?? true;
    }

    if (condition.terms && Array.isArray(condition.terms)) {
        return condition.terms.every((c) => evaluateCondition(c, values));
    }

    const key = condition.name || condition.key || condition.bind;
    if (!key) {
        // Shorthand condition object: { fieldName: expectedValue } or { fieldName: { operator, value, default } }
        const entries = Object.entries(condition).filter(([k]) => k !== 'default');
        if (entries.length === 0) return true;
        return entries.every(([field, expected]) => {
            if (expected && typeof expected === 'object' && !Array.isArray(expected)) {
                const actual = values[field] !== undefined ? values[field] : expected.default;
                return compare(actual, expected.operator, expected.value);
            }
            const actual = values[field] !== undefined ? values[field] : condition.default;
            return compare(actual, undefined, expected);
        });
    }

    const actual = values[key] !== undefined ? values[key] : condition.default;
    return compare(actual, condition.operator, condition.value);
}

export function getConditionValues(schema, value) {
    const out = {};
    function walk(items) {
        for (const item of items || []) {
            if (!item || typeof item !== 'object') continue;
            const binds = typeof item.bind === 'string'
                ? { [item.name || item.bind.split('.').pop()]: item.bind }
                : (item.bind && typeof item.bind === 'object' && !Array.isArray(item.bind)
                    ? item.bind
                    : null);

            if (binds) {
                for (const [bindKey, path] of Object.entries(binds)) {
                    if (typeof path !== 'string') continue;
                    const key = bindKey === path ? path.split('.').pop() : bindKey;
                    if (!key || out[key] !== undefined) continue;
                    let current = value;
                    for (const part of path.split('.')) {
                        current = current?.[part];
                    }
                    out[key] = current;

                    // Also expose the fully-qualified bind path so nested controls can
                    // be referenced consistently by either shorthand or dotted keys.
                    if (out[path] === undefined) {
                        out[path] = current;
                    }
                }
            }
            if (item.children) walk(item.children);
            if (item.tabs) {
                for (const tab of item.tabs) walk(tab.children);
            }
            if (item.fields) walk(item.fields);
            if (item.items) walk(item.items);
        }
    }
    walk(schema);
    return out;
}
