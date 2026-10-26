import React, { useEffect, useRef } from 'react';
import { Range, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { GradFlow } from 'gradflow';

export const PRESET_CATEGORIES = [
    { value: 'css', label: 'CSS' },
    { value: 'svg', label: 'SVG' },
    { value: 'webgl', label: 'Shaders' },
];

const COMMON_PARAMS = {
    color1: '#6366f1',
    color2: '#ec4899',
    color3: '#8b5cf6',
    opacity: 1,
    speed: 10,
    scale: 1,
};

export const SECTION_BACKGROUND_PRESETS = [
    { key: 'css-none', label: 'None', category: 'css', defaultParams: {} },
    {
        key: 'css-mesh-soft',
        label: 'Soft Mesh',
        category: 'css',
        defaultParams: { color1: '#6366f1', color2: '#ec4899', color3: '#8b5cf6', opacity: 1, speed: 0, scale: 1 },
    },
    {
        key: 'css-mesh-vivid',
        label: 'Vivid Mesh',
        category: 'css',
        defaultParams: { color1: '#f59e0b', color2: '#ef4444', color3: '#8b5cf6', opacity: 1, speed: 0, scale: 1 },
    },
    {
        key: 'css-animated-gradient',
        label: 'Animated Gradient',
        category: 'css',
        defaultParams: { color1: '#6366f1', color2: '#ec4899', color3: '#8b5cf6', opacity: 1, speed: 10, scale: 1 },
    },
    {
        key: 'css-blobs',
        label: 'Blurry Blobs',
        category: 'css',
        defaultParams: { color1: '#6366f1', color2: '#ec4899', color3: '#8b5cf6', opacity: 0.8, speed: 10, scale: 1 },
    },
    {
        key: 'css-stripes',
        label: 'Stripes',
        category: 'css',
        defaultParams: { color1: '#6366f1', color2: '#ffffff', opacity: 0.2, speed: 0, scale: 1 },
    },
    {
        key: 'css-dots',
        label: 'Polka Dots',
        category: 'css',
        defaultParams: { color1: '#6366f1', color2: '#ffffff', opacity: 0.15, speed: 0, scale: 1 },
    },
    {
        key: 'svg-blob-1',
        label: 'Organic Blob',
        category: 'svg',
        defaultParams: { color1: '#6366f1', color2: '#ec4899', opacity: 0.8, speed: 0, scale: 1 },
    },
    {
        key: 'svg-blob-2',
        label: 'Double Blob',
        category: 'svg',
        defaultParams: { color1: '#6366f1', color2: '#ec4899', color3: '#8b5cf6', opacity: 0.8, speed: 0, scale: 1 },
    },
    {
        key: 'svg-waves',
        label: 'Waves',
        category: 'svg',
        defaultParams: { color1: '#6366f1', color2: '#ec4899', opacity: 0.3, speed: 0, scale: 1 },
    },
    {
        key: 'gradflow-aurora',
        label: 'Aurora',
        category: 'webgl',
        defaultParams: {
            color1: { r: 6, g: 20, b: 12 },
            color2: { r: 0, g: 0, b: 0 },
            color3: { r: 74, g: 255, b: 128 },
            speed: 0.8,
            scale: 1,
            type: 'silk',
            noise: 0.12
        },
    },
    {
        key: 'gradflow-silk',
        label: 'Silk',
        category: 'webgl',
        defaultParams: {
            color1: { r: 225, g: 112, b: 113 },
            color2: { r: 72, g: 65, b: 37 },
            color3: { r: 79, g: 12, b: 249 },
            speed: 0.8,
            scale: 1,
            type: 'silk',
            noise: 0.12
        },
    },
    {
        key: 'gradflow-cosmic',
        label: 'Cosmic',
        category: 'webgl',
        defaultParams: {
            color1: { r: 91, g: 33, b: 182 },
            color2: { r: 30, g: 64, b: 175 },
            color3: { r: 8, g: 5, b: 24 },
            speed: 0.4,
            scale: 1.2,
            type: 'silk',
            noise: 0.1
        },
    },
    {
        key: 'gradflow-matrix',
        label: 'Matrix',
        category: 'webgl',
        defaultParams: {
        color1: { r: 6, g: 20, b: 12 },
        color2: { r: 0, g: 0, b: 0 },
        color3: { r: 74, g: 255, b: 128 },
        speed: 0.8,
        scale: 1,
        type: 'silk',
        noise: 0.12
      },
    },
    {
        key: 'gradflow-mystic',
        label: 'Mystic',
        category: 'webgl',
        defaultParams: {
            color1: { r: 210, g: 170, b: 255 },
            color2: { r: 10, g: 0, b: 30 },
            color3: { r: 80, g: 30, b: 130 },
            speed: 0.7,
            scale: 1.8,
            type: 'smoke',
            noise: 0.16
        },
    },
    {
        key: 'gradflow-plasma',
        label: 'Plasma',
        category: 'webgl',
        defaultParams: {
            color1: { r: 200, g: 60, b: 255 },
            color2: { r: 30, g: 0, b: 60 },
            color3: { r: 255, g: 110, b: 60 },
            speed: 0.6,
            scale: 1.2,
            type: 'silk',
            noise: 0.18 
        },
    },
    {
        key: 'gradflow-neon',
        label: 'Neon',
        category: 'webgl',
        defaultParams: {
            color1: { r: 0, g: 255, b: 191 },
            color2: { r: 32, g: 66, b: 146 },
            color3: { r: 9, g: 159, b: 109 },
            speed: 0.9,
            scale: 1.2,
            type: 'animated',
            noise: 0.12
        },
    },
    {
        key: 'gradflow-electric',
        label: 'Electric',
        category: 'webgl',
        defaultParams: {
            color1: { r: 14, g: 82, b: 255 },
            color2: { r: 130, g: 220, b: 255 },
            color3: { r: 80, g: 41, b: 219 },
            speed: 0.9,
            scale: 2,
            type: 'animated',
            noise: 0.16
        },
    },
    {
        key: 'gradflow-wave',
        label: 'Wave',
        category: 'webgl',
        defaultParams: {
            color1: { r: 35, g: 8, b: 135 },
            color2: { r: 72, g: 36, b: 255 },
            color3: { r: 36, g: 255, b: 171 },
            speed: 0.9,
            scale: 1.1,
            type: 'wave',
            noise: 0.2
        },
    },
    {
        key: 'gradflow-stripe',
        label: 'Stripe',
        category: 'webgl',
        defaultParams: {
            color1: { r: 35, g: 8, b: 135 },
            color2: { r: 72, g: 36, b: 255 },
            color3: { r: 36, g: 255, b: 171 },
            speed: 0.9,
            scale: 1.1,
            type: 'stripe',
            noise: 0.2
        },
    },
    {
        key: 'gradflow-mesh',
        label: 'Mesh',
        category: 'webgl',
        defaultParams: {
            color1: { r: 35, g: 8, b: 135 },
            color2: { r: 72, g: 36, b: 255 },
            color3: { r: 36, g: 255, b: 171 },
            speed: 0.9,
            scale: 3,
            type: 'mesh',
            noise: 0.2
        },
    },
    {
        key: 'gradflow-animated',
        label: 'Animated',
        category: 'webgl',
        defaultParams: {
            color1: { r: 35, g: 8, b: 135 },
            color2: { r: 72, g: 36, b: 255 },
            color3: { r: 36, g: 255, b: 171 },
            speed: 0.9,
            scale: 3,
            type: 'animated',
            noise: 0.2
        },
    },
];

function normalizeParams(preset, params) {
    return { ...(preset ? preset.defaultParams : COMMON_PARAMS), ...(params || {}) };
}

export function getPresetByKey(key) {
    return SECTION_BACKGROUND_PRESETS.find((p) => p.key === key) || SECTION_BACKGROUND_PRESETS[0];
}

function isGradFlowPreset(key) {
    return typeof key === 'string' && key.startsWith('gradflow-');
}

const GRADFLOW_TYPE_NUMBER = {
    linear: 0, conic: 1, animated: 2, wave: 3, silk: 4, smoke: 5, stripe: 6, mesh: 7, aurora: 8,
};

function getWebGLShaderName(key) {
    if (key === 'webgl-noise') return 'noise';
    if (key === 'webgl-gradient-flow') return 'gradientFlow';
    if (key === 'webgl-vibrant-waves') return 'vibrantWaves';
    if (key === 'webgl-radiant-rings') return 'radiantRings';
    if (key === 'webgl-aurora') return 'aurora';
    return 'gradientFlow';
}

function interpolateColor(a, b, amount) {
    const ah = parseInt(a.replace(/#/g, ''), 16);
    const bh = parseInt(b.replace(/#/g, ''), 16);
    const ar = (ah >> 16) & 0xff;
    const ag = (ah >> 8) & 0xff;
    const ab = ah & 0xff;
    const br = (bh >> 16) & 0xff;
    const bg = (bh >> 8) & 0xff;
    const bb = bh & 0xff;
    const rr = Math.round(ar + (br - ar) * amount);
    const rg = Math.round(ag + (bg - ag) * amount);
    const rb = Math.round(ab + (bb - ab) * amount);
    return `rgb(${rr}, ${rg}, ${rb})`;
}

function colorToCss(c) {
    if (!c) return '';
    if (typeof c === 'string') return c;
    if (typeof c === 'object' && 'r' in c && 'g' in c && 'b' in c) {
        const a = 'a' in c && c.a != null ? c.a : 1;
        return a < 1 ? `rgba(${c.r},${c.g},${c.b},${a})` : `rgb(${c.r},${c.g},${c.b})`;
    }
    return '';
}

// --- CSS style generators (used for canvas preview and inline style) ---

export function getPresetCanvasStyle(presetOrKey, params) {
    const preset = typeof presetOrKey === 'string' ? getPresetByKey(presetOrKey) : presetOrKey;
    if (!preset || preset.key === 'css-none') return {};
    const p = normalizeParams(preset, params);
    const c1 = colorToCss(p.color1) || COMMON_PARAMS.color1;
    const c2 = colorToCss(p.color2) || COMMON_PARAMS.color2;
    const c3 = colorToCss(p.color3) || COMMON_PARAMS.color3;
    const opacity = Number.isFinite(Number(p.opacity)) ? Math.max(0, Math.min(1, Number(p.opacity))) : 1;
    const scale = Number.isFinite(Number(p.scale)) ? Math.max(0.1, Math.min(5, Number(p.scale))) : 1;

    if (preset.category === 'css') {
        switch (preset.key) {
            case 'css-mesh-soft': {
                return {
                    backgroundImage: `radial-gradient(at 0% 0%, ${c1} 0px, transparent 50%), radial-gradient(at 100% 0%, ${c2} 0px, transparent 50%), radial-gradient(at 100% 100%, ${c3} 0px, transparent 50%), radial-gradient(at 0% 100%, ${interpolateColor(c1, c2, 0.5)} 0px, transparent 50%)`,
                    backgroundColor: interpolateColor(c1, '#ffffff', 0.92),
                    opacity,
                };
            }
            case 'css-mesh-vivid': {
                return {
                    backgroundImage: `radial-gradient(at 0% 0%, ${c1} 0px, transparent 50%), radial-gradient(at 100% 0%, ${c2} 0px, transparent 50%), radial-gradient(at 100% 100%, ${c3} 0px, transparent 50%), radial-gradient(at 50% 50%, ${c2} 0px, transparent 70%)`,
                    backgroundColor: c3,
                    opacity,
                };
            }
            case 'css-animated-gradient': {
                return {
                    backgroundImage: `linear-gradient(135deg, ${c1}, ${c2}, ${c3}, ${c1})`,
                    backgroundSize: `${400 / scale}% ${400 / scale}%`,
                    opacity,
                };
            }
            case 'css-blobs': {
                return {
                    backgroundImage: `radial-gradient(circle at 20% 30%, ${c1} 0%, transparent 40%), radial-gradient(circle at 80% 70%, ${c2} 0%, transparent 40%), radial-gradient(circle at 50% 50%, ${c3} 0%, transparent 50%)`,
                    backgroundColor: interpolateColor(c2, '#ffffff', 0.9),
                    filter: `blur(${8 * scale}px)`,
                    opacity,
                };
            }
            case 'css-stripes': {
                const size = 40 * scale;
                return {
                    backgroundImage: `repeating-linear-gradient(45deg, ${c1} 0px, ${c1} ${size / 2}px, ${c2} ${size / 2}px, ${c2} ${size}px)`,
                    opacity,
                };
            }
            case 'css-dots': {
                const size = 24 * scale;
                return {
                    backgroundImage: `radial-gradient(${c1} ${2 * scale}px, transparent ${2 * scale}px)`,
                    backgroundSize: `${size}px ${size}px`,
                    backgroundColor: c2,
                    opacity,
                };
            }
            default:
                return {};
        }
    }

    if (preset.category === 'svg') {
        return {
            backgroundColor: c2,
            opacity,
        };
    }

    if (preset.category === 'webgl') {
        return {
            backgroundColor: c1,
            opacity,
        };
    }

    return {};
}

export function getPresetAnimationClass(presetOrKey, params) {
    const preset = typeof presetOrKey === 'string' ? getPresetByKey(presetOrKey) : presetOrKey;
    if (!preset || preset.key === 'css-none') return '';
    const p = normalizeParams(preset, params);
    if (preset.category === 'css' && preset.key === 'css-animated-gradient') {
        return 'mp-gradient-animated';
    }
    if (preset.category === 'css' && preset.key === 'css-blobs') {
        return 'mp-blobs-animated';
    }
    return '';
}

export function getPresetKeyframes(presetOrKey) {
    const preset = typeof presetOrKey === 'string' ? getPresetByKey(presetOrKey) : presetOrKey;
    if (!preset) return '';
    if (preset.category === 'css' && preset.key === 'css-blobs') {
        return `@keyframes mp-blobs-float {0%{background-position:0% 0%;}50%{background-position:100% 100%;}100%{background-position:0% 0%;}}`;
    }
    return '';
}

export function getPresetOverlayMarkup(presetOrKey, params, sectionId) {
    const preset = typeof presetOrKey === 'string' ? getPresetByKey(presetOrKey) : presetOrKey;
    if (!preset || preset.key === 'css-none') return '';
    const p = normalizeParams(preset, params);
    const c1 = colorToCss(p.color1) || COMMON_PARAMS.color1;
    const c2 = colorToCss(p.color2) || COMMON_PARAMS.color2;
    const c3 = colorToCss(p.color3) || COMMON_PARAMS.color3;
    const c4 = colorToCss(p.color4) || '#ec4899';
    const c5 = colorToCss(p.color5) || '#f59e0b';
    const opacity = Number.isFinite(Number(p.opacity)) ? Math.max(0, Math.min(1, Number(p.opacity))) : 1;
    const scale = Number.isFinite(Number(p.scale)) ? Math.max(0.1, Math.min(5, Number(p.scale))) : 1;
    const safeId = (sectionId || 'preset').replace(/[^a-z0-9_-]/gi, '');

    if (preset.category === 'svg') {
        const svgStyle = `position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;opacity:${opacity};`;
        if (preset.key === 'svg-blob-1') {
            return (
                `<svg class="mp-bg-preset" style="${svgStyle}" viewBox="0 0 100 100" preserveAspectRatio="none">` +
                `<defs><linearGradient id="g-${safeId}" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="${c1}"/><stop offset="100%" stop-color="${c2}"/></linearGradient></defs>` +
                `<path d="M20,50 Q30,10 50,30 T80,50 T60,80 T20,50" fill="url(#g-${safeId})" opacity="0.8" transform="scale(${scale})"/></svg>`
            );
        }
        if (preset.key === 'svg-blob-2') {
            return (
                `<svg class="mp-bg-preset" style="${svgStyle}" viewBox="0 0 100 100" preserveAspectRatio="none">` +
                `<circle cx="25" cy="30" r="${18 * scale}" fill="${c1}" opacity="0.6"/>` +
                `<circle cx="75" cy="60" r="${24 * scale}" fill="${c2}" opacity="0.6"/>` +
                `<circle cx="50" cy="80" r="${14 * scale}" fill="${c3}" opacity="0.5"/></svg>`
            );
        }
        if (preset.key === 'svg-waves') {
            return (
                `<svg class="mp-bg-preset" style="${svgStyle}" viewBox="0 0 1200 120" preserveAspectRatio="none">` +
                `<defs><linearGradient id="wg-${safeId}" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="${c1}" stop-opacity="0.4"/><stop offset="100%" stop-color="${c2}" stop-opacity="0.4"/></linearGradient></defs>` +
                `<path d="M0,60 C300,10 600,110 900,60 C1050,35 1150,85 1200,60 V120 H0 Z" fill="url(#wg-${safeId})"/>` +
                `<path d="M0,80 C200,40 500,120 800,80 C1000,60 1100,100 1200,80 V120 H0 Z" fill="${c2}" opacity="0.2" transform="scale(${scale})"/></svg>`
            );
        }
    }

    if (preset.category === 'webgl') {
        if (isGradFlowPreset(preset.key)) {
            const gfType = p.type || 'aurora';
            const gfNoise = Number.isFinite(Number(p.noise)) ? Number(p.noise) : 0.3;
            const gfSpeed = Number.isFinite(Number(p.speed)) ? Number(p.speed) : 0.5;
            return (
                `<canvas class="mp-bg-preset mp-webgl-canvas mp-gradflow-canvas" data-mp-preset="gradflow" data-mp-gf-type="${gfType}" data-mp-colors="${[c1, c2, c3].join(',')}" data-mp-speed="${gfSpeed}" data-mp-scale="${scale}" data-mp-noise="${gfNoise}" id="mp-webgl-${safeId}" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;opacity:${opacity};"></canvas>`
            );
        }
        const shaderName = getWebGLShaderName(preset.key);
        return (
            `<canvas class="mp-bg-preset mp-webgl-canvas" data-mp-preset="${shaderName}" data-mp-colors="${[c1, c2, c3, c4, c5].join(',')}" data-mp-speed="${p.speed || 10}" data-mp-scale="${scale}" id="mp-webgl-${safeId}" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;opacity:${opacity};"></canvas>`
        );
    }

    return '';
}

// --- React overlay for SVG / WebGL in the builder canvas ---

export function SectionBackgroundPresetOverlay({ presetOrKey, params, sectionId }) {
    const preset = typeof presetOrKey === 'string' ? getPresetByKey(presetOrKey) : presetOrKey;
    if (!preset || preset.key === 'css-none') return null;

    if (preset.category === 'css') {
        const cls = getPresetAnimationClass(preset, params);
        return (
            <div
                className={`absolute inset-0 pointer-events-none z-0 ${cls}`}
                style={getPresetCanvasStyle(preset, params)}
            />
        );
    }

    if (preset.category === 'svg') {
        return (
            <div
                className="absolute inset-0 pointer-events-none z-0"
                dangerouslySetInnerHTML={{ __html: getPresetOverlayMarkup(preset, params, sectionId) }}
            />
        );
    }

    if (preset.category === 'webgl') {
        if (isGradFlowPreset(preset.key)) {
            return <GradFlowPresetCanvas preset={preset} params={params} sectionId={sectionId} />;
        }
        return <WebGLPresetCanvas preset={preset} params={params} sectionId={sectionId} />;
    }

    return null;
}

function WebGLPresetCanvas({ preset, params, sectionId }) {
    const canvasRef = useRef(null);
    const rafRef = useRef(null);
    const glRef = useRef(null);
    const p = normalizeParams(preset, params);
    const c1 = colorToCss(p.color1) || COMMON_PARAMS.color1;
    const c2 = colorToCss(p.color2) || COMMON_PARAMS.color2;
    const c3 = colorToCss(p.color3) || COMMON_PARAMS.color3;
    const c4 = colorToCss(p.color4) || '#ec4899';
    const c5 = colorToCss(p.color5) || '#f59e0b';
    const speed = Number.isFinite(Number(p.speed)) ? Number(p.speed) : 10;
    const scale = Number.isFinite(Number(p.scale)) ? Math.max(0.1, Math.min(5, Number(p.scale))) : 1;
    const opacity = Number.isFinite(Number(p.opacity)) ? Math.max(0, Math.min(1, Number(p.opacity))) : 1;

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!gl) return;
        glRef.current = gl;

        const shaderName = getWebGLShaderName(preset.key);
        const program = createWebGLProgram(gl, shaderName);
        if (!program) return;

        const positionBuffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, -1, 1, 1, -1, 1, 1]), gl.STATIC_DRAW);

        const positionLocation = gl.getAttribLocation(program, 'a_position');
        const resolutionLocation = gl.getUniformLocation(program, 'u_resolution');
        const timeLocation = gl.getUniformLocation(program, 'u_time');
        const color1Location = gl.getUniformLocation(program, 'u_color1');
        const color2Location = gl.getUniformLocation(program, 'u_color2');
        const color3Location = gl.getUniformLocation(program, 'u_color3');
        const color4Location = gl.getUniformLocation(program, 'u_color4');
        const color5Location = gl.getUniformLocation(program, 'u_color5');
        const scaleLocation = gl.getUniformLocation(program, 'u_scale');

        function resize() {
            const rect = canvas.getBoundingClientRect();
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            canvas.width = Math.max(1, Math.floor(rect.width * dpr));
            canvas.height = Math.max(1, Math.floor(rect.height * dpr));
            gl.viewport(0, 0, canvas.width, canvas.height);
        }

        resize();
        const ro = new ResizeObserver(resize);
        ro.observe(canvas);

        let start = performance.now();
        function render() {
            const rect = canvas.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) {
                rafRef.current = requestAnimationFrame(render);
                return;
            }
            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.useProgram(program);
            gl.enableVertexAttribArray(positionLocation);
            gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
            gl.vertexAttribPointer(positionLocation, 2, gl.FLOAT, false, 0, 0);
            gl.uniform2f(resolutionLocation, canvas.width, canvas.height);
            gl.uniform1f(timeLocation, (performance.now() - start) / 1000 * (speed / 10));
            gl.uniform3fv(color1Location, hexToRgb(c1));
            gl.uniform3fv(color2Location, hexToRgb(c2));
            gl.uniform3fv(color3Location, hexToRgb(c3));
            gl.uniform3fv(color4Location, hexToRgb(c4));
            gl.uniform3fv(color5Location, hexToRgb(c5));
            gl.uniform1f(scaleLocation, scale);
            gl.drawArrays(gl.TRIANGLES, 0, 6);
            rafRef.current = requestAnimationFrame(render);
        }
        render();

        return () => {
            ro.disconnect();
            if (rafRef.current) cancelAnimationFrame(rafRef.current);
            gl.deleteProgram(program);
        };
    }, [preset.key, c1, c2, c3, c4, c5, speed, scale]);

    return (
        <canvas
            ref={canvasRef}
            className="absolute inset-0 w-full h-full pointer-events-none z-0"
            style={{ opacity }}
        />
    );
}

function hexToRgb(hex) {
    const h = parseInt(String(hex).replace(/#/g, ''), 16);
    if (Number.isNaN(h)) return [0, 0, 0];
    return [((h >> 16) & 0xff) / 255, ((h >> 8) & 0xff) / 255, (h & 0xff) / 255];
}

function GradFlowPresetCanvas({ preset, params, sectionId }) {
    const p = normalizeParams(preset, params);
    const c1 = p.color1 || COMMON_PARAMS.color1;
    const c2 = p.color2 || COMMON_PARAMS.color2;
    const c3 = p.color3 || COMMON_PARAMS.color3;
    const speed = Number.isFinite(Number(p.speed)) ? Number(p.speed) : 0.5;
    const scale = Number.isFinite(Number(p.scale)) ? Math.max(0.1, Math.min(5, Number(p.scale))) : 1.2;
    const type = p.type || 'aurora';
    const noise = Number.isFinite(Number(p.noise)) ? Number(p.noise) : 0.3;
    const opacity = Number.isFinite(Number(p.opacity)) ? Math.max(0, Math.min(1, Number(p.opacity))) : 1;

    return (
        <div className="absolute inset-0 pointer-events-none z-0" style={{ opacity }}>
            <GradFlow
                config={{ color1: c1, color2: c2, color3: c3, speed, scale, type, noise }}
                className="w-full h-full block select-none touch-none"
            />
        </div>
    );
}

const SHADER_SOURCES = {
    noise: {
        vs: `attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }`,
        fs: `precision mediump float;
uniform vec2 u_resolution;
uniform float u_time;
uniform vec3 u_color1;
uniform vec3 u_color2;
uniform vec3 u_color3;
uniform float u_scale;
float rand(vec2 n) { return fract(sin(dot(n, vec2(12.9898, 78.233))) * 43758.5453); }
float noise(vec2 p) { vec2 i = floor(p); vec2 f = fract(p); f = f * f * (3.0 - 2.0 * f); float a = rand(i); float b = rand(i + vec2(1.0, 0.0)); float c = rand(i + vec2(0.0, 1.0)); float d = rand(i + vec2(1.0, 1.0)); return mix(mix(a, b, f.x), mix(c, d, f.x), f.y); }
float fbm(vec2 p) { float v = 0.0; float a = 0.5; for (int i = 0; i < 5; i++) { v += a * noise(p); p *= 2.0; a *= 0.5; } return v; }
void main() { vec2 uv = gl_FragCoord.xy / u_resolution.xy; float n = fbm(uv * 3.0 * u_scale + u_time * 0.3); vec3 col = mix(u_color1, u_color2, n); col = mix(col, u_color3, smoothstep(0.4, 0.6, n)); gl_FragColor = vec4(col, 1.0); }`,
    },
    gradientFlow: {
        vs: `attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }`,
        fs: `precision mediump float;
uniform vec2 u_resolution;
uniform float u_time;
uniform vec3 u_color1;
uniform vec3 u_color2;
uniform vec3 u_color3;
uniform float u_scale;
void main() { vec2 uv = gl_FragCoord.xy / u_resolution.xy; float t = u_time * 0.2; float v = sin(uv.x * 3.0 * u_scale + t) * 0.5 + 0.5; v = mix(v, sin(uv.y * 2.0 * u_scale - t * 0.7) * 0.5 + 0.5, 0.5); vec3 col = mix(u_color1, u_color2, v); col = mix(col, u_color3, sin((uv.x + uv.y) * 2.0 * u_scale + t) * 0.5 + 0.5); gl_FragColor = vec4(col, 1.0); }`,
    },
    vibrantWaves: {
        vs: `attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }`,
        fs: `precision mediump float; uniform vec2 u_resolution; uniform float u_time; uniform vec3 u_color1; uniform vec3 u_color2; uniform vec3 u_color3; uniform float u_scale; float hash(vec2 p){ return fract(sin(dot(p, vec2(127.1,311.7)))*43758.5453); } float vnoise(vec2 p){ vec2 i=floor(p); vec2 f=fract(p); f=f*f*f*(f*(f*6.0-15.0)+10.0); float a=hash(i); float b=hash(i+vec2(1.0,0.0)); float c=hash(i+vec2(0.0,1.0)); float d=hash(i+vec2(1.0,1.0)); return mix(mix(a,b,f.x),mix(c,d,f.x),f.y); } float fbm(vec2 p){ float v=0.0; float a=0.5; mat2 m=mat2(1.6,1.2,-1.2,1.6); for(int i=0;i<5;i++){ v+=a*vnoise(p); p=m*p; a*=0.5; } return v; } void main(){ vec2 uv=gl_FragCoord.xy/u_resolution.xy; float aspect=u_resolution.x/u_resolution.y; vec2 p=vec2(uv.x*aspect, uv.y); float t=u_time*0.12; float s=u_scale; float warp=fbm(p*0.7*s+vec2(t*0.3,-t*0.2)); float axis=dot(p, normalize(vec2(1.0,0.85))); float bandCenter=1.0+0.4*sin(t*0.6); float arc=sin(p.x*1.4+t*0.5)*0.22; float a=(axis-bandCenter-arc-(warp-0.5)*0.8)/max(0.3,s); vec3 col=mix(u_color2,u_color3,smoothstep(-0.45,0.55,a)); col+=u_color2*exp(-a*a*6.0)*0.18; float core=exp(-a*a*14.0); col=mix(col,u_color1,clamp(core,0.0,1.0)); col+=u_color1*exp(-a*a*45.0)*0.5; gl_FragColor=vec4(col,1.0); }`,
    },
    radiantRings: {
        vs: `attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }`,
        fs: `precision mediump float; uniform vec2 u_resolution; uniform float u_time; uniform vec3 u_color1; uniform vec3 u_color2; uniform vec3 u_color3; uniform float u_scale; void main(){ vec2 uv=gl_FragCoord.xy/u_resolution.xy; float aspect=u_resolution.x/u_resolution.y; vec2 p=vec2(uv.x*aspect, uv.y); vec2 center=vec2(0.5*aspect, -0.08); float t=u_time*0.12; float s=u_scale; float d=distance(p, center); float level=clamp(1.0-d*0.6, 0.0, 1.0); float ri=d*6.0*s - t; float band=fract(ri); vec3 col=mix(u_color3, u_color2, level*0.85); col*=0.8+0.2*smoothstep(0.0,0.2,band); float rim=smoothstep(0.85,1.0,band); col=mix(col, u_color1, rim*level*0.7); col+=u_color2*pow(level,2.2)*0.45; gl_FragColor=vec4(col,1.0); }`,
    },
    aurora: {
        vs: `attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }`,
        fs: `precision mediump float;
uniform vec2 u_resolution;
uniform float u_time;
uniform vec3 u_color1;
uniform vec3 u_color2;
uniform vec3 u_color3;
uniform vec3 u_color4;
uniform vec3 u_color5;
uniform float u_scale;
float hash(vec2 p){ return fract(sin(dot(p, vec2(127.1,311.7)))*43758.5453); }
float vnoise(vec2 p){ vec2 i=floor(p); vec2 f=fract(p); f=f*f*f*(f*(f*6.0-15.0)+10.0); float a=hash(i); float b=hash(i+vec2(1.0,0.0)); float c=hash(i+vec2(0.0,1.0)); float d=hash(i+vec2(1.0,1.0)); return mix(mix(a,b,f.x),mix(c,d,f.x),f.y); }
float fbm(vec2 p){ float v=0.0; float a=0.5; mat2 m=mat2(1.6,1.2,-1.2,1.6); for(int i=0;i<5;i++){ v+=a*vnoise(p); p=m*p; a*=0.5; } return v; }
float wave(vec2 p, float t, float freq, float amp, float phase){
  return sin(p.x*freq+t*0.6+phase)*amp
       + sin(p.x*freq*1.7+t*0.4+phase*1.3)*amp*0.5
       + sin(p.x*freq*0.6+t*0.8+phase*0.7)*amp*0.3;
}
void main(){
  vec2 uv=gl_FragCoord.xy/u_resolution.xy;
  float aspect=u_resolution.x/u_resolution.y;
  vec2 p=vec2(uv.x*aspect, uv.y);
  float t=u_time*0.15;
  float s=u_scale;
  vec2 q;
  q.x=fbm(p*1.2*s+vec2(0.0, t*0.25));
  q.y=fbm(p*1.2*s+vec2(5.2, t*0.18));
  vec2 r;
  r.x=fbm(p*1.5*s+3.0*q+vec2(t*0.1, 0.0));
  r.y=fbm(p*1.5*s+3.0*q+vec2(0.0, t*0.12));
  float warp=(r.x-0.5)*0.35+(r.y-0.5)*0.25;
  float y=uv.y+warp;
  float band1Y=0.78+wave(p, t, 2.0*s, 0.06, 0.0);
  float band2Y=0.62+wave(p, t, 1.7*s, 0.07, 1.5);
  float band3Y=0.45+wave(p, t, 2.3*s, 0.05, 3.0);
  float band4Y=0.28+wave(p, t, 1.9*s, 0.06, 4.5);
  float band5Y=0.12+wave(p, t, 2.1*s, 0.04, 2.0);
  float b1=smoothstep(0.12, 0.0, abs(y-band1Y));
  float b2=smoothstep(0.14, 0.0, abs(y-band2Y));
  float b3=smoothstep(0.13, 0.0, abs(y-band3Y));
  float b4=smoothstep(0.15, 0.0, abs(y-band4Y));
  float b5=smoothstep(0.12, 0.0, abs(y-band5Y));
  float glow1=smoothstep(0.3, 0.0, abs(y-band1Y));
  float glow2=smoothstep(0.32, 0.0, abs(y-band2Y));
  float glow3=smoothstep(0.35, 0.0, abs(y-band3Y));
  float glow4=smoothstep(0.33, 0.0, abs(y-band4Y));
  float glow5=smoothstep(0.3, 0.0, abs(y-band5Y));
  float centerGlow=smoothstep(0.25, 0.0, abs(y-0.5))*smoothstep(0.4, 0.0, abs(uv.x-0.5));
  vec3 col=vec3(0.0);
  col+=u_color2*glow1*0.35;
  col+=u_color3*glow2*0.32;
  col+=u_color4*glow3*0.38;
  col+=u_color4*glow4*0.25;
  col+=u_color5*glow5*0.30;
  col+=u_color1*centerGlow*0.45;
  col+=u_color2*b1*0.5;
  col+=u_color3*b2*0.5;
  col+=u_color4*b3*0.55;
  col+=u_color4*b4*0.4;
  col+=u_color5*b5*0.45;
  float n=fbm(p*0.8*s+4.0*r);
  col=mix(col, u_color3, smoothstep(0.4, 0.6, n)*0.08);
  col=mix(col, u_color4, smoothstep(0.3, 0.5, r.x)*0.06);
  col*=0.85+0.15*smoothstep(0.0, 0.5, n);
  float vig=1.0-smoothstep(0.45, 1.0, distance(uv, vec2(0.5, 0.5)));
  col*=0.75+0.25*vig;
  gl_FragColor=vec4(col, 1.0);
}`,
    },
};

function createWebGLProgram(gl, shaderName) {
    const src = SHADER_SOURCES[shaderName];
    if (!src) return null;
    const vs = compileShader(gl, src.vs, gl.VERTEX_SHADER);
    const fs = compileShader(gl, src.fs, gl.FRAGMENT_SHADER);
    if (!vs || !fs) return null;
    const program = gl.createProgram();
    gl.attachShader(program, vs);
    gl.attachShader(program, fs);
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return null;
    return program;
}

function compileShader(gl, source, type) {
    const shader = gl.createShader(type);
    gl.shaderSource(shader, source);
    gl.compileShader(shader);
    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
        gl.deleteShader(shader);
        return null;
    }
    return shader;
}

// --- Inspector UI ---

export function SectionBackgroundPresetInspector({ value, onChange }) {
    const current = value || {};
    const currentKey = current.key || 'css-none';
    const preset = getPresetByKey(currentKey);
    const params = normalizeParams(preset, current.params);
    const category = current.category || preset.category;

    const setKey = (key) => {
        const nextPreset = getPresetByKey(key);
        onChange({ key, category: nextPreset.category, params: { ...nextPreset.defaultParams } });
    };

    const setParam = (patch) => {
        onChange({ ...current, params: { ...params, ...patch } });
    };

    return (
        <div className="space-y-4">
            <div className="flex gap-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-1">
                {PRESET_CATEGORIES.map((cat) => (
                    <button
                        key={cat.value}
                        type="button"
                        onClick={() => {
                            const first = SECTION_BACKGROUND_PRESETS.find((p) => p.category === cat.value);
                            if (first) setKey(first.key);
                        }}
                        className={`flex-1 text-xs font-medium h-7 rounded-md transition-colors ${
                            category === cat.value
                                ? 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white'
                                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5'
                        }`}
                    >
                        {cat.label}
                    </button>
                ))}
            </div>

            <div className="grid grid-cols-2 gap-2">
                {SECTION_BACKGROUND_PRESETS.filter((p) => p.category === category).map((p) => (
                    <button
                        key={p.key}
                        type="button"
                        onClick={() => setKey(p.key)}
                        className={`rounded-md border p-2 text-left text-xs transition-colors ${
                            currentKey === p.key
                                ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'
                                : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5'
                        }`}
                    >
                        <div
                            className="h-10 w-full rounded bg-gray-100 dark:bg-gray-800 mb-1"
                            style={getPresetPreviewBoxStyle(p, params)}
                        />
                        {p.label}
                    </button>
                ))}
            </div>

            {currentKey !== 'css-none' ? (
                <div className="space-y-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                    {'color1' in params ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color 1</label>
                            <div className="flex justify-end">
                                <ColorInput value={params.color1 || ''} onChange={(v) => setParam({ color1: v })} fallback="#6366f1" />
                            </div>
                        </div>
                    ) : null}
                    {'color2' in params ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color 2</label>
                            <div className="flex justify-end">
                                <ColorInput value={params.color2 || ''} onChange={(v) => setParam({ color2: v })} fallback="#ec4899" />
                            </div>
                        </div>
                    ) : null}
                    {'color3' in params ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color 3</label>
                            <div className="flex justify-end">
                                <ColorInput value={params.color3 || ''} onChange={(v) => setParam({ color3: v })} fallback="#8b5cf6" />
                            </div>
                        </div>
                    ) : null}
                    {'color4' in params ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color 4</label>
                            <div className="flex justify-end">
                                <ColorInput value={params.color4 || ''} onChange={(v) => setParam({ color4: v })} fallback="#ec4899" />
                            </div>
                        </div>
                    ) : null}
                    {'color5' in params ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color 5</label>
                            <div className="flex justify-end">
                                <ColorInput value={params.color5 || ''} onChange={(v) => setParam({ color5: v })} fallback="#f59e0b" />
                            </div>
                        </div>
                    ) : null}
                    {'opacity' in params ? (
                        <div>
                            <div className="flex items-center justify-between mb-1">
                                <span className="text-xs text-gray-500">Opacity</span>
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{Math.round((params.opacity ?? 1) * 100)}%</span>
                            </div>
                            <Range min={0} max={1} step={0.05} value={String(params.opacity ?? 1)} onChange={(e) => setParam({ opacity: Number(e.target.value) })} />
                        </div>
                    ) : null}
                    {'type' in params && isGradFlowPreset(currentKey) ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <div className="w-36">
                                <Select
                                    value={params.type || 'aurora'}
                                    onChange={(v) => setParam({ type: v })}
                                    options={[
                                        { value: 'aurora', label: 'Aurora' },
                                        { value: 'mesh', label: 'Mesh' },
                                        { value: 'silk', label: 'Silk' },
                                        { value: 'smoke', label: 'Smoke' },
                                        { value: 'wave', label: 'Wave' },
                                        { value: 'animated', label: 'Animated' },
                                        { value: 'linear', label: 'Linear' },
                                        { value: 'conic', label: 'Conic' },
                                        { value: 'stripe', label: 'Stripe' },
                                    ]}
                                />
                            </div>
                        </div>
                    ) : null}
                    {'noise' in params && isGradFlowPreset(currentKey) ? (
                        <div>
                            <div className="flex items-center justify-between mb-1">
                                <span className="text-xs text-gray-500">Noise</span>
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{params.noise ?? 0.3}</span>
                            </div>
                            <Range min={0} max={0.5} step={0.01} value={String(params.noise ?? 0.3)} onChange={(e) => setParam({ noise: Number(e.target.value) })} />
                        </div>
                    ) : null}
                    {'speed' in params && (preset.category === 'css' || preset.category === 'webgl') ? (
                        <div>
                            <div className="flex items-center justify-between mb-1">
                                <span className="text-xs text-gray-500">Speed</span>
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{params.speed ?? 10}</span>
                            </div>
                            {isGradFlowPreset(currentKey) ? (
                                <Range min={0} max={3} step={0.1} value={String(params.speed ?? 0.5)} onChange={(e) => setParam({ speed: Number(e.target.value) })} />
                            ) : (
                                <Range min={0} max={30} step={1} value={String(params.speed ?? 10)} onChange={(e) => setParam({ speed: Number(e.target.value) })} />
                            )}
                        </div>
                    ) : null}
                    {'scale' in params ? (
                        <div>
                            <div className="flex items-center justify-between mb-1">
                                <span className="text-xs text-gray-500">Scale</span>
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{params.scale ?? 1}</span>
                            </div>
                            <Range min={0.1} max={3} step={0.1} value={String(params.scale ?? 1)} onChange={(e) => setParam({ scale: Number(e.target.value) })} />
                        </div>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}

function getPresetPreviewBoxStyle(preset, params) {
    if (preset.category === 'css') {
        return getPresetCanvasStyle(preset, params);
    }
    const p = normalizeParams(preset, params);
    const c1 = colorToCss(p.color1) || COMMON_PARAMS.color1;
    const c2 = colorToCss(p.color2) || COMMON_PARAMS.color2;
    const c3 = colorToCss(p.color3) || COMMON_PARAMS.color3;
    if (preset.category === 'svg') {
        return {
            backgroundColor: c2,
            backgroundImage: `radial-gradient(circle at 30% 30%, ${c1} 0%, transparent 40%), radial-gradient(circle at 70% 70%, ${c2} 0%, transparent 40%)`,
            opacity: p.opacity ?? 1,
        };
    }
    if (preset.category === 'webgl') {
        if (isGradFlowPreset(preset.key)) {
            const type = p.type || 'aurora';
            if (type === 'aurora') {
                return {
                    backgroundImage: `radial-gradient(at 50% 100%, ${c3} 0%, transparent 50%), radial-gradient(at 30% 20%, ${c1} 0%, transparent 40%), radial-gradient(at 70% 30%, ${c2} 0%, transparent 40%)`,
                    backgroundColor: '#0a0a1a',
                    opacity: p.opacity ?? 1,
                };
            }
            if (type === 'smoke') {
                return {
                    backgroundImage: `radial-gradient(at 30% 40%, ${c3} 0%, transparent 50%), radial-gradient(at 70% 60%, ${c2} 0%, transparent 50%)`,
                    backgroundColor: c1,
                    opacity: p.opacity ?? 1,
                };
            }
            if (type === 'silk') {
                return {
                    backgroundImage: `linear-gradient(135deg, ${c1}, ${c2}, ${c3}, ${c1})`,
                    opacity: p.opacity ?? 1,
                };
            }
            if (type === 'wave') {
                return {
                    backgroundImage: `linear-gradient(180deg, ${c1} 0%, ${c2} 50%, ${c3} 100%)`,
                    opacity: p.opacity ?? 1,
                };
            }
            return {
                backgroundImage: `radial-gradient(at 20% 30%, ${c1} 0%, transparent 40%), radial-gradient(at 80% 70%, ${c2} 0%, transparent 40%), radial-gradient(at 50% 50%, ${c3} 0%, transparent 50%)`,
                backgroundColor: c2,
                opacity: p.opacity ?? 1,
            };
        }
        if (preset.key === 'webgl-aurora') {
            const c4 = p.color4 || '#ec4899';
            const c5 = p.color5 || '#f59e0b';
            return {
                backgroundImage: `radial-gradient(at 50% 100%, ${c5} 0%, transparent 40%), radial-gradient(at 30% 20%, ${c2} 0%, transparent 40%), radial-gradient(at 70% 30%, ${c3} 0%, transparent 40%), radial-gradient(at 50% 50%, ${c4} 0%, transparent 40%), radial-gradient(at 50% 60%, ${c1} 0%, transparent 30%)`,
                backgroundColor: '#0a0a1a',
                opacity: p.opacity ?? 1,
            };
        }
        return {
            backgroundImage: `linear-gradient(135deg, ${c1}, ${c2}, ${c3})`,
            opacity: p.opacity ?? 1,
        };
    }
    return {};
}
