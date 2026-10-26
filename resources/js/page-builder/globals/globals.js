import { useMemo } from 'react';
import { normalizeGlobals, toGlobalsMap, DEFAULT_GLOBALS } from './defaults.js';

export function mergeGlobals(site, page) {
    const siteNorm = normalizeGlobals(site);
    const pageNorm = page ? normalizeGlobals(page) : { colors: [], typography: [], buttons: [] };

    const mergeCategory = (siteItems, pageItems) => {
        const siteMap = Array.isArray(siteItems) ? siteItems.reduce((acc, it) => { acc[it.id] = it; return acc; }, {}) : {};
        const pageMap = Array.isArray(pageItems) ? pageItems.reduce((acc, it) => { acc[it.id] = it; return acc; }, {}) : {};
        const ids = new Set([...Object.keys(siteMap), ...Object.keys(pageMap)]);
        return Array.from(ids).map((id) => ({ ...siteMap[id], ...pageMap[id], id }));
    };

    return {
        colors: mergeCategory(siteNorm.colors, pageNorm.colors),
        typography: mergeCategory(siteNorm.typography, pageNorm.typography),
        buttons: mergeCategory(siteNorm.buttons, pageNorm.buttons),
    };
}

export function useGlobals(siteGlobals, pageGlobals) {
    return useMemo(() => {
        const merged = mergeGlobals(siteGlobals, pageGlobals);
        const map = toGlobalsMap(merged);

        return {
            ...merged,
            map,
            resolveColor: (value) => {
                if (value && typeof value === 'object' && value.global) {
                    const c = map.colors[value.global];
                    return c ? c.value : '#000000';
                }
                return value || '#000000';
            },
            resolveTypography: (value) => {
                if (value && typeof value === 'object' && value.global) {
                    return map.typography[value.global] || {};
                }
                return value && typeof value === 'object' ? value : {};
            },
            resolveButton: (value) => {
                if (value && typeof value === 'object' && value.global) {
                    return map.buttons[value.global] || {};
                }
                return value && typeof value === 'object' ? value : {};
            },
        };
    }, [siteGlobals, pageGlobals]);
}
