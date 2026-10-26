export const CAROUSEL_DEFAULTS = {
    perPage: 1,
    perPageTablet: 2,
    perPageMobile: 1,
    gap: 16,
    loop: true,
    autoplay: false,
    interval: 5,
    speed: 500,
    effect: 'slide',
    easing: 'ease',
    direction: 'horizontal',
    pauseOnHover: true,
    arrowsShow: true,
    arrowsPosition: 'sides',
    dotsShow: true,
    dotsPosition: 'bottom-center',
};

const ARROW_POSITIONS = ['sides', 'top-left', 'top-right', 'bottom-center'];
const DOT_POSITIONS = ['bottom-center', 'bottom-left', 'bottom-right', 'top-right'];

function clampInt(value, fallback, min, max) {
    const n = Number(value);
    if (!Number.isFinite(n)) return fallback;
    return Math.max(min, Math.min(max, Math.round(n)));
}

function boolOr(value, fallback) {
    if (value === undefined || value === null) return fallback;
    return value !== false && value !== 'false' && value !== 0 && value !== '0';
}

export function normalizeCarouselConfig(raw) {
    const c = raw && typeof raw === 'object' ? raw : {};
    return {
        perPage: clampInt(c.perPage, CAROUSEL_DEFAULTS.perPage, 1, 6),
        perPageTablet: clampInt(c.perPageTablet, CAROUSEL_DEFAULTS.perPageTablet, 1, 6),
        perPageMobile: clampInt(c.perPageMobile, CAROUSEL_DEFAULTS.perPageMobile, 1, 6),
        gap: clampInt(c.gap, CAROUSEL_DEFAULTS.gap, 0, 120),
        loop: boolOr(c.loop, CAROUSEL_DEFAULTS.loop),
        autoplay: boolOr(c.autoplay, CAROUSEL_DEFAULTS.autoplay),
        interval: clampInt(c.interval, CAROUSEL_DEFAULTS.interval, 1, 60),
        speed: clampInt(c.speed, CAROUSEL_DEFAULTS.speed, 100, 4000),
        effect: c.effect === 'fade' ? 'fade' : 'slide',
        easing: typeof c.easing === 'string' && c.easing.trim() ? c.easing.trim() : CAROUSEL_DEFAULTS.easing,
        direction: c.direction === 'vertical' ? 'vertical' : 'horizontal',
        pauseOnHover: boolOr(c.pauseOnHover, CAROUSEL_DEFAULTS.pauseOnHover),
        arrowsShow: boolOr(c.arrowsShow, CAROUSEL_DEFAULTS.arrowsShow),
        arrowsPosition: ARROW_POSITIONS.includes(c.arrowsPosition) ? c.arrowsPosition : CAROUSEL_DEFAULTS.arrowsPosition,
        dotsShow: boolOr(c.dotsShow, CAROUSEL_DEFAULTS.dotsShow),
        dotsPosition: DOT_POSITIONS.includes(c.dotsPosition) ? c.dotsPosition : CAROUSEL_DEFAULTS.dotsPosition,
    };
}

// Responsive slides-per-view. Uses the configured tablet/mobile values and
// falls back to the desktop value when one isn't set.
export function resolveResponsivePerPage(config, viewportWidth) {
    const c = typeof config === 'object' && config !== null ? config : { perPage: config };
    const base = Math.max(1, Math.min(6, Math.round(Number(c.perPage) || 1)));
    const tablet = Number.isFinite(Number(c.perPageTablet)) ? Math.max(1, Math.min(6, Math.round(Number(c.perPageTablet)))) : base;
    const mobile = Number.isFinite(Number(c.perPageMobile)) ? Math.max(1, Math.min(6, Math.round(Number(c.perPageMobile)))) : base;
    const w = Number(viewportWidth) || (typeof window !== 'undefined' ? window.innerWidth : 1280);
    if (w < 640) return mobile;
    if (w < 1024) return tablet;
    return base;
}

export function chunkSlides(items, perPage) {
    const list = Array.isArray(items) ? items : [];
    const size = Math.max(1, Math.min(6, Math.round(Number(perPage) || 1)));
    const pages = [];
    for (let i = 0; i < list.length; i += size) {
        pages.push(list.slice(i, i + size));
    }
    return pages;
}

// Carousel state lives as flat, namespaced props (props.carouselX) so the
// widget prop merge (which is shallow) never clobbers sibling settings.
export const CAROUSEL_PROP_DEFAULTS = {
    carouselPerPage: 1,
    carouselPerPageTablet: 2,
    carouselPerPageMobile: 1,
    carouselGap: 16,
    carouselLoop: true,
    carouselAutoplay: false,
    carouselInterval: 5,
    carouselSpeed: 500,
    carouselEffect: 'slide',
    carouselDirection: 'horizontal',
    carouselPauseOnHover: true,
    carouselArrowsShow: true,
    carouselArrowsPosition: 'sides',
    carouselDotsShow: true,
    carouselDotsPosition: 'bottom-center',
};

export const CAROUSEL_CONTENT_KEYS = Object.keys(CAROUSEL_PROP_DEFAULTS);

export function carouselConfigFromProps(props) {
    const p = props || {};
    return normalizeCarouselConfig({
        perPage: p.carouselPerPage,
        perPageTablet: p.carouselPerPageTablet,
        perPageMobile: p.carouselPerPageMobile,
        gap: p.carouselGap,
        loop: p.carouselLoop,
        autoplay: p.carouselAutoplay,
        interval: p.carouselInterval,
        speed: p.carouselSpeed,
        effect: p.carouselEffect,
        direction: p.carouselDirection,
        pauseOnHover: p.carouselPauseOnHover,
        arrowsShow: p.carouselArrowsShow,
        arrowsPosition: p.carouselArrowsPosition,
        dotsShow: p.carouselDotsShow,
        dotsPosition: p.carouselDotsPosition,
    });
}

const SIDES = ['Top', 'Right', 'Bottom', 'Left'];

function cssColor(value) {
    return typeof value === 'string' && value.trim() ? value.trim() : '';
}

function num(value) {
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
}

function borderCss(p, prefix) {
    const out = [];
    const style = p[`${prefix}BorderStyle`];
    if (typeof style === 'string' && style && style !== 'default') out.push(`border-style:${style}`);
    const color = cssColor(p[`${prefix}BorderColor`]);
    if (color) out.push(`border-color:${color}`);
    for (const side of SIDES) {
        const w = num(p[`${prefix}BorderWidth${side}`]);
        if (w !== null) out.push(`border-${side.toLowerCase()}-width:${w}px`);
    }
    return out;
}

function backgroundCss(p, prefix) {
    const out = [];
    if (p[`${prefix}BackgroundType`] === 'gradient') {
        const from = cssColor(p[`${prefix}GradientFrom`]) || '#111827';
        const to = cssColor(p[`${prefix}GradientTo`]) || '#3b82f6';
        out.push(`background:linear-gradient(135deg,${from},${to})`);
    } else {
        const color = cssColor(p[`${prefix}BackgroundColor`]);
        if (color) out.push(`background:${color}`);
    }
    return out;
}

function shadowCss(p, prefix) {
    const color = cssColor(p[`${prefix}ShadowColor`]);
    if (!color) return [];
    const x = num(p[`${prefix}ShadowX`]) ?? 0;
    const y = num(p[`${prefix}ShadowY`]) ?? 0;
    const blur = num(p[`${prefix}ShadowBlur`]) ?? 0;
    const spread = num(p[`${prefix}ShadowSpread`]) ?? 0;
    return [`box-shadow:${x}px ${y}px ${blur}px ${spread}px ${color}`];
}

function sizeCss(p, prefix) {
    const out = [];
    const w = num(p[`${prefix}Width`]);
    const h = num(p[`${prefix}Height`]);
    if (w !== null) out.push(`width:${w}px`);
    if (h !== null) out.push(`height:${h}px`);
    return out;
}

function paddingCss(p, prefix) {
    const out = [];
    for (const side of SIDES) {
        const pad = num(p[`${prefix}Padding${side}`]);
        if (pad !== null) out.push(`padding-${side.toLowerCase()}:${pad}px`);
    }
    return out;
}

function marginCss(p, prefix) {
    const out = [];
    for (const side of SIDES) {
        const mar = num(p[`${prefix}Margin${side}`]);
        if (mar !== null) out.push(`margin-${side.toLowerCase()}:${mar}px`);
    }
    return out;
}

function radiusCss(p, prefix) {
    const out = [];
    const map = [
        ['Top', 'border-top-left-radius'],
        ['Right', 'border-top-right-radius'],
        ['Bottom', 'border-bottom-right-radius'],
        ['Left', 'border-bottom-left-radius'],
    ];
    for (const [side, prop] of map) {
        const v = num(p[`${prefix}BorderRadius${side}`]);
        if (v !== null) out.push(`${prop}:${v}px`);
    }
    if (out.length === 0) {
        const all = num(p[`${prefix}BorderRadius`]);
        if (all !== null) out.push(`border-radius:${all}px`);
    }
    return out;
}

// Builds the scoped CSS for arrow (normal/hover) and dots (normal/active)
// styling. Shared shape between the React canvas and the PHP renderer.
export function buildCarouselCss(props, scope) {
    const p = props || {};
    const sel = `.${scope}`;
    let css = '';

    const arrowNormal = [...borderCss(p, 'arrow'), ...radiusCss(p, 'arrow'), ...backgroundCss(p, 'arrow'), ...shadowCss(p, 'arrow'), ...sizeCss(p, 'arrow'), ...paddingCss(p, 'arrow')];
    const iconColor = cssColor(p.arrowIconColor);
    if (iconColor) arrowNormal.push(`color:${iconColor}`);
    if (arrowNormal.length) css += `${sel} .mp-carousel__arrow{${arrowNormal.join(';')};}`;

    const arrowHover = [...borderCss(p, 'arrowHover'), ...radiusCss(p, 'arrowHover'), ...backgroundCss(p, 'arrowHover'), ...shadowCss(p, 'arrowHover'), ...sizeCss(p, 'arrowHover'), ...paddingCss(p, 'arrowHover')];
    const hoverIconColor = cssColor(p.arrowHoverIconColor);
    if (hoverIconColor) arrowHover.push(`color:${hoverIconColor}`);
    if (arrowHover.length) css += `${sel} .mp-carousel__arrow:hover{${arrowHover.join(';')};}`;

    // Arrow margin applies to the whole arrow group (the pair), and the gap
    // controls the spacing between the two arrows.
    const arrowMargin = marginCss(p, 'arrow');
    if (arrowMargin.length) css += `${sel} .mp-carousel__arrows{${arrowMargin.join(';')};}`;
    const arrowGap = num(p.arrowGap);
    if (arrowGap !== null) css += `${sel} .mp-carousel__arrows{gap:${arrowGap}px;}`;

    // dotMargin* offsets the whole dots cluster from its anchor (see Carousel.jsx
    // dotsLayout), so per-dot margin is intentionally not emitted here.
    const dotNormal = [...backgroundCss(p, 'dot'), ...shadowCss(p, 'dot'), ...sizeCss(p, 'dot')];
    if (dotNormal.length) css += `${sel} .mp-carousel__dot{${dotNormal.join(';')};}`;
    const dotGap = num(p.dotGap);
    if (dotGap !== null) css += `${sel} .mp-carousel__dots{gap:${dotGap}px;}`;

    const dotActive = [...backgroundCss(p, 'dotActive'), ...shadowCss(p, 'dotActive'), ...sizeCss(p, 'dotActive')];
    if (dotActive.length) css += `${sel} .mp-carousel__dot.is-active{${dotActive.join(';')};}`;
    const dotActiveGap = num(p.dotActiveGap);
    if (dotActiveGap !== null) css += `${sel} .mp-carousel__dot.is-active{margin-left:${dotActiveGap}px;margin-right:${dotActiveGap}px;}`;

    return css;
}

