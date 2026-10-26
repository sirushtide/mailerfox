import React, { useEffect, useMemo, useState } from 'react';
import { normalizeCarouselConfig, chunkSlides, buildCarouselCss, resolveResponsivePerPage } from './config.js';
import { svgWithWidth } from '../../lib/svg.js';
import { BuilderViewportContext } from '../../controls/builderMode.js';

// The builder canvas is a fixed-width preview, so window.innerWidth does not
// reflect the selected device. Map the active builder viewport to a
// representative width so slides-per-view matches the device toggle.
const BUILDER_DEVICE_WIDTH = { desktop: 1280, tablet: 768, mobile: 390 };

function ArrowIcon({ props, dir }) {
    const p = props || {};
    const url = typeof p.arrowIconUrl === 'string' ? p.arrowIconUrl : '';
    const svg = typeof p.arrowIconSvg === 'string' ? p.arrowIconSvg : '';
    const source = p.arrowIconSource === 'svg' || p.arrowIconSource === 'url' ? p.arrowIconSource : '';
    const useSvg = svg !== '' && (source === 'svg' || url === '');

    const flip = dir === 'prev' ? { transform: 'scaleX(-1)' } : undefined;

    if (useSvg) {
        return <span style={flip} dangerouslySetInnerHTML={{ __html: svgWithWidth(svg, '18px', '18px') }} />;
    }
    if (url) {
        return <img src={url} alt="" style={{ width: 18, height: 18, objectFit: 'contain', display: 'block', ...(flip || {}) }} />;
    }
    return dir === 'prev' ? (
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m15 18-6-6 6-6" /></svg>
    ) : (
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m9 18 6-6-6-6" /></svg>
    );
}

function ArrowButton({ props, dir, onClick, style }) {
    return (
        <button
            type="button"
            className="mp-carousel__arrow"
            data-prev={dir === 'prev' ? '1' : undefined}
            data-next={dir === 'next' ? '1' : undefined}
            aria-label={dir === 'prev' ? 'Previous' : 'Next'}
            onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                onClick();
            }}
            style={style}
        >
            <ArrowIcon props={props} dir={dir} />
        </button>
    );
}

function arrowLayout(position) {
    if (position === 'top-left') {
        return { wrap: { position: 'absolute', top: 8, left: 8, display: 'flex', gap: 6, zIndex: 6 } };
    }
    if (position === 'top-right') {
        return { wrap: { position: 'absolute', top: 8, right: 8, display: 'flex', gap: 6, zIndex: 6 } };
    }
    if (position === 'bottom-center') {
        return { wrap: { position: 'absolute', bottom: 8, left: '50%', transform: 'translateX(-50%)', display: 'flex', gap: 6, zIndex: 6 } };
    }
    return {
        wrap: null,
        prev: { position: 'absolute', top: '50%', left: 12, transform: 'translateY(-50%)', zIndex: 6 },
        next: { position: 'absolute', top: '50%', right: 12, transform: 'translateY(-50%)', zIndex: 6 },
    };
}

function dotsLayout(position, styleProps) {
    const p = styleProps || {};
    const n = (v) => (v !== null && v !== undefined && v !== '' && Number.isFinite(Number(v)) ? Number(v) : 0);
    const dx = n(p.dotMarginLeft) - n(p.dotMarginRight);
    const dy = n(p.dotMarginTop) - n(p.dotMarginBottom);
    if (position === 'bottom-left') return { position: 'absolute', bottom: 8 - dy, left: 8 + dx, zIndex: 6 };
    if (position === 'bottom-right') return { position: 'absolute', bottom: 8 - dy, right: 8 - dx, zIndex: 6 };
    if (position === 'top-right') return { position: 'absolute', top: 8 + dy, right: 8 - dx, zIndex: 6 };
    return { position: 'absolute', bottom: 8 - dy, left: '50%', transform: 'translateX(-50%)', marginLeft: dx, zIndex: 6 };
}

export function Carousel({ items, config, renderItem, className = '', style, styleProps = {} }) {
    const cfg = normalizeCarouselConfig(config);
    const viewportCtx = React.useContext(BuilderViewportContext);
    const builderDevice = viewportCtx && typeof viewportCtx.viewport === 'string' ? viewportCtx.viewport : '';
    const [viewportWidth, setViewportWidth] = useState(() => (typeof window !== 'undefined' ? window.innerWidth : 1280));
    const effectiveViewportWidth = BUILDER_DEVICE_WIDTH[builderDevice] ?? viewportWidth;
    const perPage = resolveResponsivePerPage(cfg, effectiveViewportWidth);
    const pages = chunkSlides(items, perPage);
    const count = pages.length;
    const [page, setPage] = useState(0);
    const [paused, setPaused] = useState(false);

    const scope = useMemo(() => `mpc-${Math.random().toString(36).slice(2, 9)}`, []);
    const css = useMemo(() => buildCarouselCss(styleProps, scope), [styleProps, scope]);

    useEffect(() => {
        if (typeof window === 'undefined') return undefined;
        const onResize = () => setViewportWidth(window.innerWidth);
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, []);

    useEffect(() => {
        if (page > count - 1) setPage(0);
    }, [count, page]);

    useEffect(() => {
        if (!cfg.autoplay || count <= 1) return undefined;
        const id = window.setInterval(() => {
            if (paused) return;
            setPage((p) => (p + 1) % count);
        }, Math.max(1, cfg.interval) * 1000);
        return () => window.clearInterval(id);
    }, [cfg.autoplay, cfg.interval, count, paused]);

    if (count === 0) {
        return <div className={`mp-carousel ${className}`} style={style} />;
    }

    const go = (dir) => {
        setPage((p) => {
            let next = p + dir;
            if (next < 0) next = cfg.loop ? count - 1 : 0;
            if (next >= count) next = cfg.loop ? 0 : count - 1;
            return next;
        });
    };

    const isFade = cfg.effect === 'fade';
    const transition = `transform ${cfg.speed}ms ${cfg.easing}, opacity ${cfg.speed}ms ${cfg.easing}`;
    const vertical = cfg.direction === 'vertical';
    const arrows = arrowLayout(cfg.arrowsPosition);
    const dotsPos = dotsLayout(cfg.dotsPosition, styleProps);

    return (
        <div
            className={`mp-carousel ${scope} ${className}`}
            style={{ position: 'relative', ...(style || {}) }}
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
        >
            {css ? <style dangerouslySetInnerHTML={{ __html: css }} /> : null}
            <div className="mp-carousel__viewport" style={{ overflow: 'hidden', position: 'relative' }}>
                <div
                    className="mp-carousel__track"
                    style={
                        isFade
                            ? { position: 'relative' }
                            : {
                                  display: 'flex',
                                  flexWrap: 'nowrap',
                                  flexDirection: vertical ? 'column' : 'row',
                                  transform: vertical ? `translateY(-${page * 100}%)` : `translateX(-${page * 100}%)`,
                                  transition,
                              }
                    }
                >
                    {pages.map((group, pi) => (
                        <div
                            key={pi}
                            className="mp-carousel__page"
                            style={{
                                width: '100%',
                                minWidth: '100%',
                                flex: '0 0 100%',
                                display: 'flex',
                                flexWrap: 'nowrap',
                                flexDirection: 'row',
                                gap: `${cfg.gap}px`,
                                ...(isFade
                                    ? {
                                          position: pi === 0 ? 'relative' : 'absolute',
                                          top: 0,
                                          left: 0,
                                          opacity: pi === page ? 1 : 0,
                                          transition,
                                          pointerEvents: pi === page ? 'auto' : 'none',
                                      }
                                    : {}),
                            }}
                        >
                            {group.map((item, ii) => {
                                const idx = pi * perPage + ii;
                                return (
                                    <div key={idx} className="mp-carousel__slide" style={{ flex: '1 1 0%', width: 0, minWidth: 0 }}>
                                        {renderItem(item, idx)}
                                    </div>
                                );
                            })}
                        </div>
                    ))}
                </div>
            </div>

            {cfg.arrowsShow && count > 1 ? (
                arrows.wrap ? (
                    <div className="mp-carousel__arrows" style={arrows.wrap}>
                        <ArrowButton props={styleProps} dir="prev" onClick={() => go(-1)} />
                        <ArrowButton props={styleProps} dir="next" onClick={() => go(1)} />
                    </div>
                ) : (
                    <>
                        <ArrowButton props={styleProps} dir="prev" onClick={() => go(-1)} style={arrows.prev} />
                        <ArrowButton props={styleProps} dir="next" onClick={() => go(1)} style={arrows.next} />
                    </>
                )
            ) : null}

            {cfg.dotsShow && count > 1 ? (
                <div className="mp-carousel__dots" style={{ display: 'flex', ...dotsPos }}>
                    {pages.map((_, di) => (
                        <button
                            key={di}
                            type="button"
                            className={`mp-carousel__dot ${di === page ? 'is-active' : ''}`}
                            aria-label={`Go to slide ${di + 1}`}
                            onClick={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                setPage(di);
                            }}
                        />
                    ))}
                </div>
            ) : null}
        </div>
    );
}
