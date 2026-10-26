export const HOVER_EFFECT_OPTIONS = [
    { value: '', label: 'None' },
    { value: 'magnetic', label: 'Magnetic Underline' },
    { value: 'slide', label: 'Slide Underline' },
    { value: 'text-lift', label: 'Text Lift' },
    { value: 'arrow-shift', label: 'Arrow Shift' },
    { value: 'blur-focus', label: 'Blur Focus' },
    { value: 'elastic', label: 'Elastic Underline' },
    { value: 'gradient-shift', label: 'Gradient Shift' },
    { value: 'text-flip', label: 'Text Flip' },
];

const UNDERLINE_BASE = 'content:"";position:absolute;left:0;bottom:-3px;height:2px;width:100%;background:currentColor;border-radius:2px;';

export function hoverEffectNeedsMagnetic(effect) {
    return effect === 'magnetic';
}

/**
 * Returns scoped CSS for the chosen hover effect.
 * @param {string} effect
 * @param {string} containerSel e.g. ".mp-links.mp-links-abc"
 * @param {string} linkClass e.g. ".mp-link"
 */
export function hoverEffectCss(effect, containerSel, linkClass) {
    const linkSel = `${containerSel} ${linkClass}`;
    switch (effect) {
        case 'magnetic':
            return `${linkSel}{position:relative;}\n${linkSel}::after{${UNDERLINE_BASE}transform:scaleX(0);transform-origin:var(--mp-mag-origin,50%);transition:transform .35s cubic-bezier(.22,1,.36,1);}\n${linkSel}:hover::after{transform:scaleX(1);}`;
        case 'slide':
            return `${linkSel}{position:relative;}\n${linkSel}::after{${UNDERLINE_BASE}transform:scaleX(0);transform-origin:right center;transition:transform .3s ease;}\n${linkSel}:hover::after{transform:scaleX(1);transform-origin:left center;}`;
        case 'text-lift':
            return `${linkSel}{transition:transform .3s cubic-bezier(.22,1,.36,1);}\n${linkSel}:hover{transform:translateY(-3px);}`;
        case 'arrow-shift':
            return `${linkSel}::after{content:"→";display:inline-block;vertical-align:middle;align-self:center;line-height:1;max-width:0;margin-left:0;overflow:hidden;opacity:0;transform:translateX(-4px);transition:max-width .3s cubic-bezier(.22,1,.36,1),margin-left .3s cubic-bezier(.22,1,.36,1),opacity .3s ease,transform .3s cubic-bezier(.22,1,.36,1);}\n${linkSel}:hover::after{max-width:2em;margin-left:6px;opacity:1;transform:translateX(0);}`;
        case 'blur-focus':
            return `${linkSel}{transition:filter .3s ease,opacity .3s ease;}\n${containerSel}:hover ${linkClass}{filter:blur(1.5px);opacity:.55;}\n${linkSel}:hover{filter:none;opacity:1;}`;
        case 'elastic':
            return `${linkSel}{position:relative;}\n${linkSel}::after{${UNDERLINE_BASE}transform:scaleX(0);transform-origin:left center;transition:transform .6s cubic-bezier(.34,1.56,.64,1);}\n${linkSel}:hover::after{transform:scaleX(1);}`;
        case 'gradient-shift':
            return `${linkSel}{background-image:linear-gradient(90deg,currentColor 0%,currentColor 45%,#6366f1 70%,#a855f7 100%);background-size:200% 100%;background-position:0% 0;-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;transition:background-position .5s ease;}\n${linkSel}:hover{background-position:100% 0;}`;
        case 'text-flip':
            return `${linkSel}{position:relative;overflow:hidden;}\n${linkSel} .mp-link-text{display:block;transition:transform .4s cubic-bezier(.22,1,.36,1);}\n${linkSel}::after{content:attr(data-text);position:absolute;left:0;top:0;width:100%;transform:translateY(-100%);transition:transform .4s cubic-bezier(.22,1,.36,1);}\n${linkSel}:hover .mp-link-text{transform:translateY(100%);}\n${linkSel}:hover::after{transform:translateY(0);}`;
        default:
            return '';
    }
}
