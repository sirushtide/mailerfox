export function mergeClassNames(...args) {
    return args
        .filter((a) => typeof a === 'string' && a.trim())
        .map((a) => a.trim())
        .join(' ');
}
