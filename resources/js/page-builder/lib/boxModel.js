import { getValueByPath, setValueByPath } from '../controls/bind.js';

export function toFiniteNumberOrUndefined(v) {
    const n = Number(v);
    return Number.isFinite(n) ? n : undefined;
}

export function inferLinked4(a, b, c, d, fallback = true) {
    const allDefined = [a, b, c, d].every((x) => x !== undefined);
    if (!allDefined) return fallback;
    return a === b && b === c && c === d;
}

export function set4Linked(nextLinked, curr, keys, fallbackValue) {
    const next = { ...curr };
    setValueByPath(next, keys.linked, nextLinked);
    if (nextLinked) {
        const v =
            toFiniteNumberOrUndefined(getValueByPath(curr, keys.t)) ??
            toFiniteNumberOrUndefined(getValueByPath(curr, keys.r)) ??
            toFiniteNumberOrUndefined(getValueByPath(curr, keys.b)) ??
            toFiniteNumberOrUndefined(getValueByPath(curr, keys.l)) ??
            toFiniteNumberOrUndefined(fallbackValue);
        setValueByPath(next, keys.t, v);
        setValueByPath(next, keys.r, v);
        setValueByPath(next, keys.b, v);
        setValueByPath(next, keys.l, v);
        if (keys.legacy) {
            setValueByPath(next, keys.legacy, v);
        }
        if (keys.legacyX && keys.legacyY) {
            setValueByPath(next, keys.legacyX, v);
            setValueByPath(next, keys.legacyY, v);
        }
    } else {
        if (keys.legacy) {
            setValueByPath(next, keys.legacy, undefined);
        }
        if (keys.legacyX && keys.legacyY) {
            setValueByPath(next, keys.legacyX, undefined);
            setValueByPath(next, keys.legacyY, undefined);
        }
    }
    return next;
}

export function patch4(curr, keys, sideKey, rawValue) {
    const next = { ...curr };
    const v =
        rawValue === '' || rawValue === null || rawValue === undefined
            ? undefined
            : toFiniteNumberOrUndefined(rawValue);
    const linked = getValueByPath(curr, keys.linked) !== false;
    if (linked) {
        setValueByPath(next, keys.t, v);
        setValueByPath(next, keys.r, v);
        setValueByPath(next, keys.b, v);
        setValueByPath(next, keys.l, v);
        if (keys.legacy) {
            setValueByPath(next, keys.legacy, v);
        }
        if (keys.legacyX && keys.legacyY) {
            setValueByPath(next, keys.legacyX, v);
            setValueByPath(next, keys.legacyY, v);
        }
        return next;
    }
    setValueByPath(next, sideKey, v);
    return next;
}
