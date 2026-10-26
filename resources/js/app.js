import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import '@hotwired/turbo';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

// AI agents + model selector build v2
window.__aiAgentBuildVersion = 'v2';
Alpine.plugin(collapse);
window.Alpine = Alpine;

const SIDEBAR_SCROLL_STORAGE_KEY = 'mailpurse.sidebar.scrollTop';

const getSidebarNav = (root = document) => root.querySelector('aside[data-sidebar="app"] nav');

const saveSidebarScrollPosition = () => {
    const sidebarNav = getSidebarNav();

    if (!sidebarNav) {
        return;
    }

    window.sessionStorage.setItem(SIDEBAR_SCROLL_STORAGE_KEY, String(sidebarNav.scrollTop || 0));
};

const applySidebarScrollPosition = (sidebarNav) => {
    const savedScrollTop = window.sessionStorage.getItem(SIDEBAR_SCROLL_STORAGE_KEY);

    if (!sidebarNav || savedScrollTop === null) {
        return;
    }

    sidebarNav.scrollTop = Number.parseInt(savedScrollTop, 10) || 0;
};

const restoreSidebarScrollPosition = (root = document) => {
    const sidebarNav = getSidebarNav(root);

    if (!sidebarNav) {
        return;
    }

    applySidebarScrollPosition(sidebarNav);

    window.requestAnimationFrame(() => {
        applySidebarScrollPosition(sidebarNav);
    });
};

document.addEventListener('scroll', (event) => {
    if (event.target instanceof HTMLElement && event.target.matches('aside[data-sidebar="app"] nav')) {
        saveSidebarScrollPosition();
    }
}, true);

document.addEventListener('DOMContentLoaded', () => {
    restoreSidebarScrollPosition(document);
});
document.addEventListener('turbo:before-render', (event) => {
    if (event.detail && event.detail.newBody instanceof HTMLElement) {
        restoreSidebarScrollPosition(event.detail.newBody);
    }
});
document.addEventListener('turbo:load', () => {
    restoreSidebarScrollPosition(document);
});

try {
    gsap.registerPlugin(ScrollTrigger);
} catch (e) {
    // ignore
}

try {
    gsap.registerPlugin(ScrollTrigger);
} catch (e) {
    // ignore
}

function initSliders() {
    const roots = document.querySelectorAll('.mp-slider');
    if (!roots || roots.length === 0) {
        return;
    }

    const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

    roots.forEach((root) => {
        try {
            const track = root.querySelector('.mp-slider__track');
            if (!track) {
                return;
            }

            const getSlides = () => (track && track.children ? Array.from(track.children) : []);

            const initialSlides = getSlides();
            if (initialSlides.length <= 1) {
                return;
            }

            const intervalRaw = Number(root.getAttribute('data-mp-slider-interval'));
            const intervalSeconds = Number.isFinite(intervalRaw) ? clamp(intervalRaw, 1, 60) : 4;
            const autoplay = root.getAttribute('data-mp-slider-autoplay') !== '0';
            const hasArrows = root.getAttribute('data-mp-slider-arrows') !== '0';

            const perPageRaw = Number(root.getAttribute('data-mp-slider-per-page'));
            const perPage = Number.isFinite(perPageRaw) ? clamp(Math.round(perPageRaw), 1, 6) : 1;

            const ease = root.getAttribute('data-mp-slider-ease') || 'ease';

            const configKey = `${intervalSeconds}|${autoplay ? '1' : '0'}|${hasArrows ? '1' : '0'}|${perPage}|${ease}`;

            const existingBtnPrev = root.querySelector('[data-mp-slider-prev]');
            const existingBtnNext = root.querySelector('[data-mp-slider-next]');
            const hasExistingButtons = Boolean(existingBtnPrev && existingBtnNext);
            const hasExistingTimer = Boolean(root.__mpSlider && root.__mpSlider.timerId);

            if (root.dataset && root.dataset.mpSliderInit === '1' && root.dataset.mpSliderInitConfig === configKey) {
                const arrowsOk = !hasArrows || hasExistingButtons;
                const autoplayOk = !autoplay || hasExistingTimer;
                if (arrowsOk && autoplayOk) {
                    return;
                }
            }

            // Cleanup previous init (live updates)
            if (root.__mpSlider && typeof root.__mpSlider === 'object') {
                try {
                    if (root.__mpSlider.timerId) {
                        window.clearInterval(root.__mpSlider.timerId);
                    }
                    if (root.__mpSlider.onResize) {
                        window.removeEventListener('resize', root.__mpSlider.onResize);
                    }
                    if (root.__mpSlider.onMouseEnter) {
                        root.removeEventListener('mouseenter', root.__mpSlider.onMouseEnter);
                    }
                    if (root.__mpSlider.onMouseLeave) {
                        root.removeEventListener('mouseleave', root.__mpSlider.onMouseLeave);
                    }
                    if (root.__mpSlider.onPrev && root.__mpSlider.btnPrev) {
                        root.__mpSlider.btnPrev.removeEventListener('click', root.__mpSlider.onPrev);
                    }
                    if (root.__mpSlider.onNext && root.__mpSlider.btnNext) {
                        root.__mpSlider.btnNext.removeEventListener('click', root.__mpSlider.onNext);
                    }
                } catch (e) {
                    // ignore
                }
            }

            // JS carousel uses transforms for easing.
            if (root.style) {
                root.style.overflowX = 'hidden';
                if (!root.style.position || root.style.position === '') {
                    root.style.position = 'relative';
                }
            }

            if (track.style) {
                track.style.willChange = 'transform';
            }

            let index = 0;
            let isPaused = false;

            const getMaxStartIndex = () => {
                const count = getSlides().length;
                return Math.max(0, count - perPage);
            };

            const applyTransform = () => {
                const slides = getSlides();
                if (slides.length <= 0) {
                    return;
                }
                const maxStart = getMaxStartIndex();
                index = clamp(index, 0, maxStart);
                const el = slides[index];
                if (!el) {
                    return;
                }
                const x = typeof el.offsetLeft === 'number' ? el.offsetLeft : 0;
                track.style.transition = `transform 0.4s ${ease}`;
                track.style.transform = `translateX(${-x}px)`;
            };

            const prev = (e) => {
                if (e && typeof e.preventDefault === 'function') e.preventDefault();
                const maxStart = getMaxStartIndex();
                const nextIndex = index - perPage;
                if (nextIndex < 0) {
                    index = index <= 0 ? maxStart : 0;
                } else {
                    index = nextIndex;
                }
                applyTransform();
            };

            const next = (e) => {
                if (e && typeof e.preventDefault === 'function') e.preventDefault();
                const maxStart = getMaxStartIndex();
                const nextIndex = index + perPage;
                if (nextIndex > maxStart) {
                    index = index >= maxStart ? 0 : maxStart;
                } else {
                    index = nextIndex;
                }
                applyTransform();
            };

            let btnPrev = root.querySelector('[data-mp-slider-prev]');
            let btnNext = root.querySelector('[data-mp-slider-next]');

            const ensureButton = (dir) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute(dir === 'prev' ? 'data-mp-slider-prev' : 'data-mp-slider-next', '');
                btn.setAttribute('aria-label', dir === 'prev' ? 'Previous' : 'Next');
                btn.style.position = 'absolute';
                btn.style.top = '50%';
                btn.style.transform = 'translateY(-50%)';
                btn.style.zIndex = '5';
                btn.style.display = 'inline-flex';
                btn.style.alignItems = 'center';
                btn.style.justifyContent = 'center';
                btn.style.border = '1px solid transparent';
                btn.style.cursor = 'pointer';
                btn.style.userSelect = 'none';
                if (dir === 'prev') {
                    btn.style.left = '12px';
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>';
                } else {
                    btn.style.right = '12px';
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';
                }
                return btn;
            };

            if (hasArrows) {
                if (!btnPrev) {
                    btnPrev = ensureButton('prev');
                    root.appendChild(btnPrev);
                }
                if (!btnNext) {
                    btnNext = ensureButton('next');
                    root.appendChild(btnNext);
                }
                if (btnPrev) btnPrev.addEventListener('click', prev);
                if (btnNext) btnNext.addEventListener('click', next);
            }

            // Apply arrow styling from CSS vars
            try {
                const cs = window.getComputedStyle(root);
                const sizeStr = cs.getPropertyValue('--mp-slider-arrow-size').trim();
                const radiusStr = cs.getPropertyValue('--mp-slider-arrow-radius').trim();
                const bg = cs.getPropertyValue('--mp-slider-arrow-bg').trim();
                const col = cs.getPropertyValue('--mp-slider-arrow-color').trim();
                const border = cs.getPropertyValue('--mp-slider-arrow-border').trim();

                const size = Number.parseInt(sizeStr || '36', 10);
                const radius = Number.parseInt(radiusStr || '999', 10);

                [btnPrev, btnNext].filter(Boolean).forEach((btn) => {
                    btn.style.width = `${Number.isFinite(size) ? size : 36}px`;
                    btn.style.height = `${Number.isFinite(size) ? size : 36}px`;
                    btn.style.borderRadius = `${Number.isFinite(radius) ? radius : 999}px`;
                    btn.style.background = bg || 'rgba(255,255,255,0.92)';
                    btn.style.borderColor = border || 'rgba(0,0,0,0.12)';
                    btn.style.color = col || '#111827';
                });
            } catch (e) {
                // ignore
            }

            const onMouseEnter = () => {
                isPaused = true;
            };
            const onMouseLeave = () => {
                isPaused = false;
            };
            root.addEventListener('mouseenter', onMouseEnter);
            root.addEventListener('mouseleave', onMouseLeave);

            const onResize = () => {
                applyTransform();
            };
            window.addEventListener('resize', onResize);

            let timerId = null;
            if (autoplay) {
                timerId = window.setInterval(() => {
                    if (isPaused) return;
                    next();
                }, Math.round(intervalSeconds * 1000));
            }

            // Initial alignment
            window.requestAnimationFrame(() => {
                applyTransform();
            });

            root.__mpSlider = {
                timerId,
                onResize,
                onMouseEnter,
                onMouseLeave,
                btnPrev,
                btnNext,
                onPrev: prev,
                onNext: next,
            };

            if (root.dataset) {
                root.dataset.mpSliderInit = '1';
                root.dataset.mpSliderInitConfig = configKey;
            }
        } catch (e) {
            // ignore
        }
    });
}


function initCarousels() {
    const roots = document.querySelectorAll('.mp-carousel[data-mp-carousel]');
    if (!roots || roots.length === 0) {
        return;
    }

    const clamp = (n, min, max) => Math.max(min, Math.min(max, n));
    const resolvePerPage = (cfg, w) => {
        const base = clamp(Math.round(Number(cfg.perPage) || 1), 1, 6);
        const tablet = Number.isFinite(Number(cfg.perPageTablet)) ? clamp(Math.round(Number(cfg.perPageTablet)), 1, 6) : base;
        const mobile = Number.isFinite(Number(cfg.perPageMobile)) ? clamp(Math.round(Number(cfg.perPageMobile)), 1, 6) : base;
        if (w < 640) return mobile;
        if (w < 1024) return tablet;
        return base;
    };

    roots.forEach((root) => {
        try {
            let cfg = {};
            try {
                cfg = JSON.parse(root.getAttribute('data-mp-carousel') || '{}') || {};
            } catch (e) {
                cfg = {};
            }

            const track = root.querySelector('.mp-carousel__track');
            if (!track) {
                return;
            }
            const viewport = root.querySelector('.mp-carousel__viewport');
            const allSlides = Array.from(root.querySelectorAll('.mp-carousel__slide'));
            if (allSlides.length === 0) {
                return;
            }

            const loop = cfg.loop !== false;
            const autoplay = cfg.autoplay === true;
            const intervalSeconds = clamp(Number(cfg.interval) || 5, 1, 60);
            const speed = clamp(Number(cfg.speed) || 500, 100, 4000);
            const effect = cfg.effect === 'fade' ? 'fade' : 'slide';
            const vertical = cfg.direction === 'vertical';
            const pauseOnHover = cfg.pauseOnHover !== false;
            const basePerPage = clamp(Math.round(Number(cfg.perPage) || 1), 1, 6);
            const gap = clamp(Number(cfg.gap) || 0, 0, 120);

            const configKey = [loop, autoplay, intervalSeconds, speed, effect, vertical, pauseOnHover, basePerPage, gap, allSlides.length].join('|');
            if (root.dataset.mpCarouselInit === '1' && root.dataset.mpCarouselInitConfig === configKey) {
                return;
            }

            const prev = root.__mpCarousel;
            if (prev && typeof prev === 'object') {
                try {
                    if (prev.timerId) window.clearInterval(prev.timerId);
                    if (prev.onResize) window.removeEventListener('resize', prev.onResize);
                    if (prev.onEnter) root.removeEventListener('mouseenter', prev.onEnter);
                    if (prev.onLeave) root.removeEventListener('mouseleave', prev.onLeave);
                    if (prev.onPrev && prev.btnPrev) prev.btnPrev.removeEventListener('click', prev.onPrev);
                    if (prev.onNext && prev.btnNext) prev.btnNext.removeEventListener('click', prev.onNext);
                    (prev.dotHandlers || []).forEach(([el, fn]) => el.removeEventListener('click', fn));
                    if (prev.onDown) root.removeEventListener('pointerdown', prev.onDown);
                    if (prev.onUp) root.removeEventListener('pointerup', prev.onUp);
                } catch (e) {
                    // ignore
                }
            }

            if (root.style && !root.style.position) {
                root.style.position = 'relative';
            }
            if (viewport && viewport.style) {
                viewport.style.overflow = 'hidden';
                viewport.style.position = 'relative';
            }
            track.style.display = 'flex';
            track.style.width = '100%';
            track.style.flexDirection = vertical ? 'column' : 'row';

            let index = 0;
            let paused = false;
            let pageEls = [];
            let count = 1;
            let currentPerPage = resolvePerPage(cfg, window.innerWidth);

            const dotsContainer = root.querySelector('.mp-carousel__dots');
            const dotHandlers = [];

            const buildPages = () => {
                track.innerHTML = '';
                pageEls = [];
                const eff = currentPerPage;
                let page = null;
                allSlides.forEach((slide, i) => {
                    if (i % eff === 0) {
                        page = document.createElement('div');
                        page.className = 'mp-carousel__page';
                        page.style.display = 'flex';
                        page.style.gap = `${gap}px`;
                        page.style.width = '100%';
                        if (vertical) {
                            page.style.minWidth = '0';
                        } else {
                            page.style.flex = '0 0 100%';
                            page.style.minWidth = '100%';
                        }
                        if (effect === 'fade') {
                            page.style.transition = `opacity ${speed}ms ease`;
                        }
                        pageEls.push(page);
                        track.appendChild(page);
                    }
                    slide.style.flex = '1 1 0%';
                    slide.style.width = '0';
                    slide.style.minWidth = '0';
                    if (page) page.appendChild(slide);
                });
                count = Math.max(1, pageEls.length);
            };

            const buildDots = () => {
                dotHandlers.forEach(([el, fn]) => el.removeEventListener('click', fn));
                dotHandlers.length = 0;
                if (!dotsContainer) return;
                dotsContainer.innerHTML = '';
                for (let i = 0; i < count; i++) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `mp-carousel__dot${i === 0 ? ' is-active' : ''}`;
                    btn.setAttribute('data-mp-carousel-dot', '');
                    btn.setAttribute('aria-label', `Go to slide ${i + 1}`);
                    const fn = (e) => {
                        e.preventDefault();
                        index = i;
                        apply();
                    };
                    btn.addEventListener('click', fn);
                    dotHandlers.push([btn, fn]);
                    dotsContainer.appendChild(btn);
                }
            };

            const updateControls = () => {
                root.querySelectorAll('.mp-carousel__arrow').forEach((arrowBtn) => {
                    arrowBtn.style.display = count > 1 ? '' : 'none';
                });
                if (dotsContainer) {
                    dotsContainer.style.display = count > 1 ? '' : 'none';
                }
            };

            const apply = () => {
                if (effect === 'fade') {
                    pageEls.forEach((pg, i) => {
                        pg.style.opacity = i === index ? '1' : '0';
                        pg.style.pointerEvents = i === index ? 'auto' : 'none';
                        if (i === 0) {
                            pg.style.position = 'relative';
                        } else {
                            pg.style.position = 'absolute';
                            pg.style.top = '0';
                            pg.style.left = '0';
                        }
                    });
                } else {
                    track.style.transition = `transform ${speed}ms ease`;
                    track.style.transform = vertical ? `translateY(-${index * 100}%)` : `translateX(-${index * 100}%)`;
                }
                if (dotsContainer) {
                    Array.from(dotsContainer.children).forEach((dot, i) => dot.classList.toggle('is-active', i === index));
                }
                updateControls();
            };

            const go = (dir) => {
                let next = index + dir;
                if (next < 0) next = loop ? count - 1 : 0;
                if (next >= count) next = loop ? 0 : count - 1;
                index = next;
                apply();
            };

            buildPages();
            buildDots();

            const btnPrev = root.querySelector('[data-mp-carousel-prev]');
            const btnNext = root.querySelector('[data-mp-carousel-next]');
            const onPrev = (e) => {
                e.preventDefault();
                go(-1);
            };
            const onNext = (e) => {
                e.preventDefault();
                go(1);
            };
            if (btnPrev) btnPrev.addEventListener('click', onPrev);
            if (btnNext) btnNext.addEventListener('click', onNext);

            const onEnter = () => {
                paused = true;
            };
            const onLeave = () => {
                paused = false;
            };
            if (pauseOnHover) {
                root.addEventListener('mouseenter', onEnter);
                root.addEventListener('mouseleave', onLeave);
            }

            let timerId = null;
            if (autoplay) {
                timerId = window.setInterval(() => {
                    if (!paused) go(1);
                }, Math.round(intervalSeconds * 1000));
            }

            const onResize = () => {
                const eff = resolvePerPage(cfg, window.innerWidth);
                if (eff === currentPerPage) {
                    apply();
                    return;
                }
                currentPerPage = eff;
                index = 0;
                buildPages();
                buildDots();
                apply();
            };
            window.addEventListener('resize', onResize);

            let startX = 0;
            let startY = 0;
            let tracking = false;
            const onDown = (e) => {
                tracking = true;
                startX = e.clientX;
                startY = e.clientY;
            };
            const onUp = (e) => {
                if (!tracking) return;
                tracking = false;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                if (vertical ? Math.abs(dy) > 40 : Math.abs(dx) > 40) {
                    go((vertical ? dy : dx) < 0 ? 1 : -1);
                }
            };
            root.addEventListener('pointerdown', onDown);
            root.addEventListener('pointerup', onUp);

            window.requestAnimationFrame(apply);

            root.__mpCarousel = { timerId, onResize, onEnter, onLeave, onPrev, onNext, btnPrev, btnNext, dotHandlers, onDown, onUp };
            if (root.dataset) {
                root.dataset.mpCarouselInit = '1';
                root.dataset.mpCarouselInitConfig = configKey;
            }
        } catch (e) {
            // ignore
        }
    });
}

function initMobileMenus() {
    const roots = document.querySelectorAll('.mp-mobile-menu');
    if (!roots || roots.length === 0) {
        return;
    }

    roots.forEach((root) => {
        try {
            if (root.dataset && root.dataset.mpMobileMenuInit === '1') return;

            const toggle = root.querySelector('.mp-mobile-menu__toggle');
            const overlay = root.querySelector('.mp-mobile-menu__overlay');
            const drawer = root.querySelector('.mp-mobile-menu__drawer');

            // A fixed drawer is trapped by transformed/filtered ancestors (e.g.
            // animated/sticky header sections), which makes it render inside the
            // header instead of the viewport. Move overlay + drawer to <body> so
            // the drawer always covers the whole screen.
            if (overlay && overlay.parentElement !== document.body) {
                document.body.appendChild(overlay);
            }
            if (drawer && drawer.parentElement !== document.body) {
                document.body.appendChild(drawer);
            }

            const open = () => {
                if (overlay) overlay.classList.add('is-open');
                if (drawer) drawer.classList.add('is-open');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
                document.body.classList.add('mp-mobile-menu-open');
            };
            const shut = () => {
                if (overlay) overlay.classList.remove('is-open');
                if (drawer) drawer.classList.remove('is-open');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('mp-mobile-menu-open');
            };

            if (toggle) toggle.addEventListener('click', open);
            if (overlay) overlay.addEventListener('click', shut);
            if (drawer) {
                const closeBtn = drawer.querySelector('.mp-mobile-menu__close');
                if (closeBtn) closeBtn.addEventListener('click', shut);
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') shut();
            });

            if (root.dataset) root.dataset.mpMobileMenuInit = '1';
        } catch (e) {
            // ignore
        }
    });
}

let __mpSliderObserverSetup = false;
function setupSliderAutoReinit() {
    if (__mpSliderObserverSetup) return;
    __mpSliderObserverSetup = true;

    const debounce = (fn, waitMs) => {
        let t = null;
        return () => {
            if (t) window.clearTimeout(t);
            t = window.setTimeout(() => {
                t = null;
                fn();
            }, waitMs);
        };
    };

    const schedule = debounce(() => {
        try {
            initSliders();
        } catch (e) {
            // ignore
        }
        try {
            initCarousels();
        } catch (e) {
            // ignore
        }
        try {
            initMobileMenus();
        } catch (e) {
            // ignore
        }
    }, 80);

    try {
        const root = document.body;
        if (!root) return;

        const obs = new MutationObserver((mutations) => {
            for (const m of mutations) {
                if (m.type === 'childList') {
                    try {
                        const t = m.target;
                        if (
                            t &&
                            t.nodeType === 1 &&
                            (t.classList?.contains('mp-slider') || t.closest?.('.mp-slider') || t.classList?.contains('mp-carousel') || t.closest?.('.mp-carousel'))
                        ) {
                            schedule();
                            return;
                        }
                    } catch (e) {
                        // ignore
                    }

                    const added = m.addedNodes ? Array.from(m.addedNodes) : [];
                    if (
                        added.some((n) =>
                            n &&
                            n.nodeType === 1 &&
                            (n.classList?.contains('mp-slider') || n.querySelector?.('.mp-slider') || n.classList?.contains('mp-carousel') || n.querySelector?.('.mp-carousel'))
                        )
                    ) {
                        schedule();
                        return;
                    }
                }

                if (m.type === 'attributes') {
                    const t = m.target;
                    if (t && t.nodeType === 1 && (t.classList?.contains('mp-slider') || t.classList?.contains('mp-carousel'))) {
                        schedule();
                        return;
                    }
                }
            }
        });

        obs.observe(root, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: [
                'data-mp-slider-per-page',
                'data-mp-slider-interval',
                'data-mp-slider-ease',
                'data-mp-slider-autoplay',
                'data-mp-slider-arrows',
                'data-mp-carousel',
                'style',
            ],
        });
    } catch (e) {
        // ignore
    }
}

function initFaqsCarousels() {
    const roots = document.querySelectorAll('.mp-faqs[data-mp-faqs-carousel="1"]');
    if (!roots || roots.length === 0) {
        return;
    }

    const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

    roots.forEach((root) => {
        try {
            const speedRaw = Number(root.getAttribute('data-mp-faqs-carousel-speed'));
            const intervalSeconds = Number.isFinite(speedRaw) ? clamp(speedRaw, 1, 60) : 4;
            const ease = root.getAttribute('data-mp-faqs-carousel-ease') || 'ease';

            const arrowsAttr = root.getAttribute('data-mp-faqs-carousel-arrows');
            const hasArrows = arrowsAttr !== '0';

            const configKey = `${intervalSeconds}|${ease}|${hasArrows ? '1' : '0'}`;

            if (root.dataset && root.dataset.mpFaqsCarouselInit === '1' && root.dataset.mpFaqsCarouselInitConfig === configKey) {
                return;
            }

            // Cleanup previous init (in case of live updates)
            if (root.__mpFaqsCarousel && typeof root.__mpFaqsCarousel === 'object') {
                try {
                    if (root.__mpFaqsCarousel.timerId) {
                        window.clearInterval(root.__mpFaqsCarousel.timerId);
                    }
                    if (root.__mpFaqsCarousel.onResize) {
                        window.removeEventListener('resize', root.__mpFaqsCarousel.onResize);
                    }
                    if (root.__mpFaqsCarousel.onMouseEnter) {
                        root.removeEventListener('mouseenter', root.__mpFaqsCarousel.onMouseEnter);
                    }
                    if (root.__mpFaqsCarousel.onMouseLeave) {
                        root.removeEventListener('mouseleave', root.__mpFaqsCarousel.onMouseLeave);
                    }
                } catch (e) {
                    // ignore
                }
            }

            const track = root.querySelector('.mp-faqs__track');
            const viewport = root.querySelector('.mp-faqs__viewport');
            if (!track || !viewport) {
                return;
            }

            const transitionSeconds = 0.4;

            let index = 0;

            const getCount = () => {
                const slides = track.children ? Array.from(track.children) : [];
                return slides.length;
            };

            const applyTransform = () => {
                const count = getCount();
                if (count <= 0) {
                    return;
                }
                index = ((index % count) + count) % count;
                track.style.transition = `transform ${transitionSeconds}s ${ease}`;
                track.style.transform = `translateX(${-index * 100}%)`;
            };

            const prev = () => {
                index -= 1;
                applyTransform();
            };

            const next = () => {
                index += 1;
                applyTransform();
            };

            if (hasArrows) {
                const btnPrev = root.querySelector('[data-mp-faqs-prev]');
                const btnNext = root.querySelector('[data-mp-faqs-next]');
                if (btnPrev) {
                    btnPrev.addEventListener('click', (e) => {
                        e.preventDefault();
                        prev();
                    });
                }
                if (btnNext) {
                    btnNext.addEventListener('click', (e) => {
                        e.preventDefault();
                        next();
                    });
                }
            }

            // Ensure correct initial positioning once layout is ready.
            window.requestAnimationFrame(() => {
                applyTransform();
            });

            let isPaused = false;
            const onMouseEnter = () => {
                isPaused = true;
            };
            const onMouseLeave = () => {
                isPaused = false;
            };
            root.addEventListener('mouseenter', onMouseEnter);
            root.addEventListener('mouseleave', onMouseLeave);

            const timerId = window.setInterval(() => {
                if (isPaused) return;
                next();
            }, Math.round(intervalSeconds * 1000));

            // Re-apply on resize (viewport width changes).
            const onResize = () => {
                applyTransform();
            };
            window.addEventListener('resize', onResize);

            root.__mpFaqsCarousel = { timerId, onResize, onMouseEnter, onMouseLeave };

            if (root.dataset) {
                root.dataset.mpFaqsCarouselInit = '1';
                root.dataset.mpFaqsCarouselInitConfig = configKey;
            }
        } catch (e) {
            // ignore
        }
    });
}

function initFaqsAccordions() {
    const roots = document.querySelectorAll('.mp-faqs');
    if (!roots || roots.length === 0) {
        return;
    }

    const DURATION = 300;
    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    roots.forEach((root) => {
        if (root.dataset.mpFaqsAccordionInit === '1') {
            return;
        }
        root.dataset.mpFaqsAccordionInit = '1';

        const animateItem = (item, wantOpen) => {
            const content = item.querySelector('.mp-faqs__a');
            if (!content || prefersReducedMotion || typeof content.animate !== 'function') {
                item.classList.remove('mp-faqs__item--closing');
                item.open = wantOpen;
                return;
            }
            if (item.dataset.mpFaqsAnimating === '1') {
                return;
            }
            item.dataset.mpFaqsAnimating = '1';
            content.style.overflow = 'hidden';

            if (wantOpen) {
                item.classList.remove('mp-faqs__item--closing');
                item.open = true;
                const target = content.offsetHeight;
                const anim = content.animate(
                    [
                        { height: '0px', marginTop: '0px', opacity: 0 },
                        { height: `${target}px`, marginTop: '8px', opacity: 1 },
                    ],
                    { duration: DURATION, easing: 'ease' }
                );
                anim.onfinish = () => {
                    content.style.overflow = '';
                    delete item.dataset.mpFaqsAnimating;
                };
            } else {
                const start = content.offsetHeight;
                item.classList.add('mp-faqs__item--closing');
                const anim = content.animate(
                    [
                        { height: `${start}px`, marginTop: '8px', opacity: 1 },
                        { height: '0px', marginTop: '0px', opacity: 0 },
                    ],
                    { duration: DURATION, easing: 'ease' }
                );
                anim.onfinish = () => {
                    item.open = false;
                    item.classList.remove('mp-faqs__item--closing');
                    content.style.overflow = '';
                    delete item.dataset.mpFaqsAnimating;
                };
            }
        };

        root.addEventListener('click', (e) => {
            const summary = e.target.closest ? e.target.closest('summary') : null;
            if (!summary) {
                return;
            }
            const item = summary.closest('.mp-faqs__item');
            if (!item || !root.contains(item)) {
                return;
            }
            e.preventDefault();
            const wantOpen = !item.open;

            root.querySelectorAll('.mp-faqs__item').forEach((other) => {
                if (other !== item && other.open && other.dataset.mpFaqsAnimating !== '1') {
                    animateItem(other, false);
                }
            });

            animateItem(item, wantOpen);
        });
    });
}

const __parseGsapConfig = (raw) => {
    if (!raw || typeof raw !== 'string') return null;
    try {
        const cfg = JSON.parse(raw);
        if (!cfg || typeof cfg !== 'object') return null;
        if (cfg.enabled !== true) return null;
        return cfg;
    } catch (e) {
        return null;
    }
};

const __getSplitItems = (el, split) => {
    const mode = typeof split === 'string' ? split : '';
    if (!mode) return null;

    // Restore original HTML before any new split
    try {
        if (el.dataset && typeof el.dataset.mpGsapSplitOriginal === 'string' && el.dataset.mpGsapSplitOriginal !== '') {
            el.innerHTML = el.dataset.mpGsapSplitOriginal;
        }
    } catch (e) {
        // ignore
    }

    const root = el;
    if (!root) return null;

    const text = (root.textContent || '').replace(/\s+/g, ' ').trim();
    if (!text) return null;

    try {
        if (el.dataset && (!el.dataset.mpGsapSplitOriginal || el.dataset.mpGsapSplitOriginal === '')) {
            el.dataset.mpGsapSplitOriginal = root.innerHTML;
        }
    } catch (e) {
        // ignore
    }

    const makeSpan = (t, cls) => {
        const s = document.createElement('span');
        s.className = cls;
        s.textContent = t;
        s.style.display = 'inline-block';
        return s;
    };

    const copyHighlightStyle = (span, highlight) => {
        span.className = `${highlight.className} mp-gsap-split`.trim();
        let css = highlight.style && highlight.style.cssText ? highlight.style.cssText : '';
        const isAnimatedGradient = /(?:^|[;\s])animation\s*:/i.test(css);
        if (isAnimatedGradient) {
            css = css
                .replace(/(?:[-\w]*background[-\w]*|animation(?:-[a-z]+)?)\s*:[^;]+;?/gi, '')
                .trim();
        }
        if (css) span.style.cssText = css;
        span.style.display = 'inline-block';
    };

    // Walk existing text nodes and replace them with split spans in-place so parent
    // markup (e.g. mp-text-highlight spans) is preserved. If a text node sits inside
    // a highlight span, copy that highlight class/style to each split child so the
    // color/gradient background-clip still applies to the visible text.
    const splitTextNodes = (splitMode) => {
        const findHighlightAncestor = (node) => {
            let el = node.parentElement;
            while (el && el !== root) {
                if (el.classList && el.classList.contains('mp-text-highlight')) {
                    return el;
                }
                el = el.parentElement;
            }
            return null;
        };

        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
        const nodes = [];
        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }
        const items = [];
        nodes.forEach((node) => {
            const value = node.textContent || '';
            if (!value) return;
            const parent = node.parentNode;
            if (!parent) return;

            const highlight = findHighlightAncestor(node);

            if (splitMode === 'chars') {
                const frag = document.createDocumentFragment();
                Array.from(value).forEach((ch) => {
                    const span = makeSpan(ch === ' ' ? '\u00A0' : ch, 'mp-gsap-split');
                    if (highlight) {
                        copyHighlightStyle(span, highlight);
                    }
                    frag.appendChild(span);
                    items.push(span);
                });
                parent.replaceChild(frag, node);
            } else if (splitMode === 'words') {
                const frag = document.createDocumentFragment();
                const parts = value.split(' ');
                parts.forEach((w, i) => {
                    const span = makeSpan(w, 'mp-gsap-split');
                    if (highlight) {
                        copyHighlightStyle(span, highlight);
                    }
                    frag.appendChild(span);
                    items.push(span);
                    if (i !== parts.length - 1) frag.appendChild(document.createTextNode(' '));
                });
                parent.replaceChild(frag, node);
            }
        });
        return items;
    };

    if (mode === 'chars') {
        return splitTextNodes('chars');
    }

    if (mode === 'words') {
        return splitTextNodes('words');
    }

    if (mode === 'lines') {
        // Conservative: split by <br> if present, otherwise split by sentence-ish chunks.
        const html = root.innerHTML;
        const hasBr = /<br\s*\/?\s*>/i.test(html);
        let lines = [];
        if (hasBr) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const parts = [];
            tmp.childNodes.forEach((n) => {
                if (n.nodeType === 1 && n.nodeName === 'BR') {
                    parts.push('\n');
                } else {
                    parts.push(n.textContent || '');
                }
            });
            lines = parts.join('').split('\n').map((s) => s.replace(/\s+/g, ' ').trim()).filter(Boolean);
        } else {
            lines = text.split(/(?<=[.!?])\s+/).map((s) => s.trim()).filter(Boolean);
            if (lines.length === 0) lines = [text];
        }

        // Preserve a single highlight style when the whole content was highlighted.
        let highlightClass = '';
        let highlightStyle = '';
        try {
            const highlight = root.querySelector('.mp-text-highlight');
            if (highlight && (highlight.textContent || '').replace(/\s+/g, ' ').trim() === text) {
                highlightClass = highlight.className || '';
                highlightStyle = highlight.getAttribute('style') || '';
            }
        } catch (e) {
            // ignore
        }

        root.innerHTML = '';
        const frag = document.createDocumentFragment();
        lines.forEach((ln, i) => {
            const span = makeSpan(ln, 'mp-gsap-split');
            span.style.display = 'block';
            if (highlightClass) {
                span.className = `${highlightClass} mp-gsap-split`.trim();
                if (highlightStyle) span.setAttribute('style', `${highlightStyle}; display: block;`.replace(/;\s*;/g, ';'));
            }
            frag.appendChild(span);
            if (i !== lines.length - 1) frag.appendChild(document.createElement('br'));
        });
        root.appendChild(frag);
        return Array.from(root.querySelectorAll('.mp-gsap-split'));
    }

    return null;
};

const __gsapPresetVars = (preset) => {
    switch (preset) {
        case 'fade-in':
            return { from: { opacity: 0 }, to: { opacity: 1 } };
        case 'scale-in':
            return { from: { opacity: 0, scale: 0.9 }, to: { opacity: 1, scale: 1 } };
        case 'slide-left':
            return { from: { opacity: 0, x: -40 }, to: { opacity: 1, x: 0 } };
        case 'slide-right':
            return { from: { opacity: 0, x: 40 }, to: { opacity: 1, x: 0 } };
        case 'blurred-in':
            return { from: { opacity: 0, filter: 'blur(8px)' }, to: { opacity: 1, filter: 'blur(0px)' } };
        case 'from-x':
            return { from: { opacity: 0, x: 60 }, to: { opacity: 1, x: 0 } };
        case 'from-y':
            return { from: { opacity: 0, y: 60 }, to: { opacity: 1, y: 0 } };
        case 'fade-up':
        default:
            return { from: { opacity: 0, y: 40 }, to: { opacity: 1, y: 0 } };
    }
};

const __initGsapAnimations = () => {
    const nodes = document.querySelectorAll('[data-mp-gsap]');
    if (!nodes || nodes.length === 0) {
        document.documentElement.classList.add('mp-gsap-ready');
        return;
    }

    nodes.forEach((el) => {
        const raw = el.getAttribute('data-mp-gsap');
        const cfg = __parseGsapConfig(raw);

        // Cleanup if config disabled / removed
        if (!cfg) {
            if (el.__mpGsapTween && typeof el.__mpGsapTween.kill === 'function') {
                try { el.__mpGsapTween.kill(); } catch (e) {}
            }
            if (el.__mpGsapScrollTrigger && typeof el.__mpGsapScrollTrigger.kill === 'function') {
                try { el.__mpGsapScrollTrigger.kill(); } catch (e) {}
            }
            el.__mpGsapTween = null;
            el.__mpGsapScrollTrigger = null;
            if (el.dataset) {
                delete el.dataset.mpGsapInit;
                delete el.dataset.mpGsapInitConfig;
            }
            return;
        }

        const cfgKey = raw;
        if (el.dataset && el.dataset.mpGsapInit === '1' && el.dataset.mpGsapInitConfig === cfgKey) {
            return;
        }

        // Re-init when config changes
        if (el.__mpGsapTween && typeof el.__mpGsapTween.kill === 'function') {
            try { el.__mpGsapTween.kill(); } catch (e) {}
        }
        if (el.__mpGsapScrollTrigger && typeof el.__mpGsapScrollTrigger.kill === 'function') {
            try { el.__mpGsapScrollTrigger.kill(); } catch (e) {}
        }

        const preset = typeof cfg.preset === 'string' ? cfg.preset : 'fade-up';
        const trigger = typeof cfg.trigger === 'string' ? cfg.trigger : 'scroll';
        const duration = Number.isFinite(Number(cfg.duration)) ? Number(cfg.duration) : 0.8;
        const delay = Number.isFinite(Number(cfg.delay)) ? Number(cfg.delay) : 0;
        const ease = typeof cfg.ease === 'string' ? cfg.ease : 'power2.out';
        const start = Number.isFinite(Number(cfg.start)) ? Math.max(0, Math.min(100, Number(cfg.start))) : 80;
        const once = cfg.once !== false;
        const stagger = Number.isFinite(Number(cfg.stagger)) ? Math.max(0, Math.min(2, Number(cfg.stagger))) : 0;
        const split = typeof cfg.split === 'string' ? cfg.split : '';
        const splitStagger = Number.isFinite(Number(cfg.splitStagger)) ? Math.max(0, Math.min(2, Number(cfg.splitStagger))) : 0.04;

        const vars = __gsapPresetVars(preset);

        const splitRoot = split
            ? (el.querySelector('.mp-heading') || el.querySelector('.mp-richtext') || el)
            : null;
        const splitItems = splitRoot && split ? __getSplitItems(splitRoot, split) : null;

        // If we're animating split parts, ensure the wrapper isn't hidden by preset CSS.
        // The preset classes (gsap-fade-up, etc.) apply opacity/transform on the wrapper.
        // When we animate only the split spans, the wrapper must stay visible.
        if (splitItems && splitItems.length) {
            try {
                // Override CSS opacity:0 on the wrapper.
                el.style.opacity = '1';

                // Only neutralize transform if the wrapper doesn't already have an inline transform
                // (we don't want to clobber user transform controls).
                if (!el.style.transform || el.style.transform === '') {
                    el.style.transform = 'none';
                }
            } catch (e) {
                // ignore
            }
        }

        let targets = splitItems && splitItems.length
            ? splitItems
            : (stagger > 0 ? Array.from(el.querySelectorAll('.mp-grid > .mp-col')) : [el]);

        if (targets.length === 0) targets = [el];

        try {
            gsap.set(targets, vars.from);

            const actualStagger = splitItems && splitItems.length ? splitStagger : (stagger > 0 ? stagger : 0);

            if (trigger === 'load') {
                el.__mpGsapTween = gsap.to(targets, { ...vars.to, duration, delay, ease, stagger: actualStagger });
            } else {
                const tween = gsap.to(targets, {
                    ...vars.to,
                    duration,
                    delay,
                    ease,
                    stagger: actualStagger,
                    paused: true,
                });

                const st = ScrollTrigger.create({
                    trigger: el,
                    start: `top ${start}%`,
                    once,
                    onEnter: () => tween.play(),
                    onEnterBack: once ? undefined : () => tween.restart(),
                });

                el.__mpGsapTween = tween;
                el.__mpGsapScrollTrigger = st;
            }
        } catch (e) {
            // ignore
        }

        if (el.dataset) {
            el.dataset.mpGsapInit = '1';
            el.dataset.mpGsapInitConfig = cfgKey;
        }
    });

    try {
        ScrollTrigger.refresh();
    } catch (e) {
        // ignore
    }

    // Signal that the reveal script ran; the CSS initial (hidden) states are
    // only applied while JS is present, and content is force-revealed if this
    // flag never appears (see public layout fallback timer).
    document.documentElement.classList.add('mp-gsap-ready');
};

window.__mailpurseInitGsapAnimations = __initGsapAnimations;

const __maybeLoadPageBuilder = () => {
    try {
        if (!document.getElementById('page-builder-react')) return;
        if (window.__mailpursePageBuilderLoaded) return;
        window.__mailpursePageBuilderLoaded = true;
        window.__mailpursePageBuilderImportOk = false;
        window.__mailpursePageBuilderImportError = null;
        window.__mailpursePageBuilderImportPromise = import('./page-builder/main/index.js')
            .then((mod) => {
                if (mod && typeof mod.__initPageBuilder === 'function') {
                    mod.__initPageBuilder();
                }
                window.__mailpursePageBuilderImportOk = true;
            })
            .catch((e) => {
                window.__mailpursePageBuilderImportError = e;
            });
    } catch (e) {
        // ignore
    }
};

document.addEventListener('DOMContentLoaded', __maybeLoadPageBuilder);
document.addEventListener('turbo:load', __maybeLoadPageBuilder);

document.addEventListener('DOMContentLoaded', __initGsapAnimations);
document.addEventListener('turbo:load', __initGsapAnimations);

if (window.Turbo && window.Turbo.session) {
    window.Turbo.session.drive = true;
}

if (
    window.Turbo
    && window.Turbo.config
    && typeof window.Turbo.config.drive === 'object'
    && window.Turbo.config.drive !== null
) {
    window.Turbo.config.drive.progressBarDelay = 0;
} else if (window.Turbo && typeof window.Turbo.setProgressBarDelay === 'function') {
    window.Turbo.setProgressBarDelay(0);
}

// Force a full page reload when navigating to/from the campaign create or
// edit pages. The Plate-based email content editor is loaded via @push('scripts')
// and its module DOMContentLoaded listener does not re-fire on Turbo visits,
// so the editor would never boot. A hard navigation guarantees a fresh boot.
(function forceReloadForCampaignWizard() {
    const isCampaignWizardPath = (urlString) => {
        if (!urlString) {
            return false;
        }
        try {
            const url = new URL(urlString, window.location.origin);
            // matches /customer/campaigns/create and /customer/campaigns/{id}/edit
            return /\/customer\/campaigns\/(?:create|[^/]+\/edit)\/?$/.test(url.pathname);
        } catch (e) {
            return false;
        }
    };

    document.addEventListener('turbo:before-visit', (event) => {
        const target = event && event.detail ? event.detail.url : null;
        const leavingWizard = isCampaignWizardPath(window.location.href);
        const enteringWizard = isCampaignWizardPath(target);

        if (enteringWizard || leavingWizard) {
            try {
                event.preventDefault();
            } catch (_) {
            }
            if (target) {
                window.location.href = target;
            }
        }
    });
})();

Alpine.data('templateImportModal', (opts = {}) => ({
    importOpen: false,
    canImport: !!opts.canImport,
    canAi: !!opts.canAi,
    tab: opts.initialTab || 'templates',
    loading: false,
    previewLoading: false,
    importing: false,
    error: '',
    gallery: [],
    categories: [],
    activeCategory: 'all',
    selected: null,
    preview: null,
    previewModalOpen: false,
    previewModalItem: null,
    previewModalLoading: false,
    previewModalData: null,
    ai: {
        provider: 'chatgpt',
        model: '',
        prompt: '',
        loading: false,
        error: '',
    },

    init() {
        if (this.$watch) {
            this.$watch('ai.provider', () => {
                const allowed = this.aiModelsForProvider();
                if (this.ai.model && !allowed.includes(this.ai.model)) {
                    this.ai.model = '';
                }
            });
        }
    },

    aiModelsForProvider() {
        const provider = (this.ai && typeof this.ai.provider === 'string') ? this.ai.provider : 'chatgpt';

        if (window.AI_PROVIDER_MODELS && window.AI_PROVIDER_MODELS[provider]) {
            return window.AI_PROVIDER_MODELS[provider];
        }

        if (provider === 'claude') {
            return ['claude-haiku-4-5-20251001', 'claude-sonnet-4-5', 'claude-sonnet-4-6', 'claude-opus-4-5', 'claude-opus-4-6', 'claude-opus-4-7', 'claude-opus-4-8'];
        }

        return ['gpt-5', 'gpt-5-mini', 'gpt-5.2', 'gpt-5-nano', 'gpt-4.1'];
    },

    open() {
        this.importOpen = true;
        this.error = '';
        this.selected = null;
        this.preview = null;
        this.activeCategory = 'all';
        this.previewModalOpen = false;
        this.previewModalItem = null;
        this.previewModalData = null;
        this.ai.error = '';

        if (this.tab !== 'ai' && !this.canImport) {
            this.tab = 'ai';
        }

        if (this.tab !== 'ai' && this.canImport) {
            this.loadGallery();
        }
    },

    close() {
        this.importOpen = false;
        this.error = '';
    },

    setTab(tab) {
        if (tab === 'templates' && !this.canImport) {
            this.tab = 'ai';
            return;
        }
        if (tab === 'ai' && !this.canAi) {
            return;
        }

        this.tab = tab;
        this.error = '';
        this.selected = null;
        this.preview = null;
        this.activeCategory = 'all';
        this.ai.error = '';

        if (this.tab !== 'ai' && this.canImport) {
            this.loadGallery();
        }
    },

    setCategory(categoryId) {
        this.activeCategory = categoryId;
    },

    get filteredGallery() {
        if (this.activeCategory === 'all') {
            return this.gallery;
        }
        return this.gallery.filter(item => item.category === this.activeCategory);
    },

    get visibleCategories() {
        const usedCategories = new Set(this.gallery.map(item => item.category || 'other'));
        return this.categories.filter(cat => cat.id === 'all' || usedCategories.has(cat.id));
    },

    currentBuilder() {
        const el = document.getElementById('builder-type');
        const value = el ? String(el.value || '').trim() : '';
        if (value === 'text' || value === 'unlayer' || value === 'grapesjs' || value === 'custom') {
            return value;
        }
        return opts.builder || 'grapesjs';
    },

    loadGallery() {
        if (!this.canImport || !opts.galleryUrl) {
            this.gallery = [];
            return;
        }

        this.loading = true;
        this.error = '';

        const tabParam = 'templates';
        const builderParam = this.currentBuilder();
        const url = (opts.galleryUrl || '') + '?tab=' + encodeURIComponent(tabParam) + '&builder=' + encodeURIComponent(builderParam);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.ok ? r.json() : Promise.reject(r))
            .then((data) => {
                this.gallery = Array.isArray(data.templates) ? data.templates : [];
                this.categories = Array.isArray(data.categories) ? data.categories : [];
            })
            .catch(() => {
                this.error = 'Failed to load templates.';
            })
            .finally(() => {
                this.loading = false;
            });
    },

    openPreviewModal(item) {
        if (!item) return;
        this.previewModalItem = item;
        this.previewModalOpen = true;
        this.previewModalLoading = true;
        this.previewModalData = null;

        const url = (item && typeof item.content_url === 'string' && item.content_url)
            ? item.content_url
            : ((opts.contentUrlBase || '') + '/' + item.id + '/content');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.ok ? r.json() : Promise.reject(r))
            .then((data) => {
                this.previewModalData = data;
            })
            .catch(() => {
                this.error = 'Failed to load template preview.';
            })
            .finally(() => {
                this.previewModalLoading = false;
            });
    },

    closePreviewModal() {
        this.previewModalOpen = false;
        this.previewModalItem = null;
        this.previewModalData = null;
    },

    insertFromPreviewModal() {
        if (!this.previewModalData) return;
        this.preview = this.previewModalData;
        this.closePreviewModal();
        this.doImport();
    },

    insertTemplate(item) {
        if (!item) return;
        this.previewLoading = true;
        this.error = '';

        const url = (item && typeof item.content_url === 'string' && item.content_url)
            ? item.content_url
            : ((opts.contentUrlBase || '') + '/' + item.id + '/content');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.ok ? r.json() : Promise.reject(r))
            .then((data) => {
                this.preview = data;
                this.doImport();
            })
            .catch(() => {
                this.error = 'Failed to load template.';
            })
            .finally(() => {
                this.previewLoading = false;
            });
    },

    select(item) {
        if (!this.canImport || !opts.contentUrlBase) {
            return;
        }

        this.selected = item;
        this.previewLoading = true;
        this.error = '';

        const url = (item && typeof item.content_url === 'string' && item.content_url)
            ? item.content_url
            : ((opts.contentUrlBase || '') + '/' + item.id + '/content');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.ok ? r.json() : Promise.reject(r))
            .then((data) => {
                this.preview = data;
            })
            .catch(() => {
                this.error = 'Failed to load template preview.';
            })
            .finally(() => {
                this.previewLoading = false;
            });
    },

    doImport() {
        if (!this.preview) {
            return;
        }
        this.importing = true;
        this.error = '';

        const builder = this.preview.builder || null;

        const originInput = document.querySelector('input[name="settings[origin]"]');
        if (originInput) {
            originInput.value = this.tab === 'ai' ? 'ai' : '';
        }

        const effectiveBuilder = this.currentBuilder();

        if (effectiveBuilder === 'text' && (builder === 'text' || !builder)) {
            try {
                const mountEl = document.querySelector('[data-outreach-rich-editor][data-template-editor="1"]');
                if (!mountEl) {
                    throw new Error('Text composer is not ready yet.');
                }
                const html = String(this.preview.html_content || '');
                mountEl.dataset.value = html;
                this.importOpen = false;
            } catch (e) {
                const msg = (e && e.message) ? e.message : 'Failed to import this template.';
                this.error = msg;
            } finally {
                this.importing = false;
            }
            return;
        }

        if (opts.builder === 'custom') {
            try {
                if (window.__mailpurseCustomTemplateBuilder && typeof window.__mailpurseCustomTemplateBuilder.importTemplate === 'function') {
                    const imported = window.__mailpurseCustomTemplateBuilder.importTemplate(this.preview);
                    if (!imported) {
                        throw new Error('This template is not compatible with the current builder.');
                    }
                    this.importOpen = false;
                    this.importing = false;
                    return;
                }

                throw new Error('Custom builder is not ready yet.');
            } catch (e) {
                const msg = (e && e.message) ? e.message : 'Failed to import this template.';
                this.error = msg;
                this.importing = false;
                return;
            }
        }

        if (opts.builder === 'unlayer' && builder === 'unlayer' && this.preview.builder_data) {
            const waitForUnlayer = (timeoutMs = 20000) => new Promise((resolve, reject) => {
                const start = Date.now();
                const tick = () => {
                    if (window.__mailpurseUnlayerReady === true) {
                        resolve();
                        return;
                    }
                    if (Date.now() - start >= timeoutMs) {
                        reject(new Error('Unlayer editor is not ready yet.'));
                        return;
                    }
                    setTimeout(tick, 75);
                };
                tick();
            });

            Promise.resolve()
                .then(() => ensureUnlayerScriptLoaded())
                .then(() => {
                    try {
                        setupUnlayerEditors();
                    } catch (e) {
                    }
                })
                .then(() => {
                    const rawDesign = this.preview.builder_data;
                    const unwrapped = (window.Alpine && typeof window.Alpine.raw === 'function')
                        ? window.Alpine.raw(rawDesign)
                        : rawDesign;

                    let design = unwrapped;

                    if (design && typeof design === 'object' && design.builder === 'unlayer' && design.unlayer && typeof design.unlayer === 'object') {
                        design = design.unlayer;
                    }

                    if (design && typeof design === 'object' && design.design && typeof design.design === 'object') {
                        design = design.design;
                    }

                    if (design && typeof design === 'object' && design.unlayer && typeof design.unlayer === 'object' && design.unlayer.body) {
                        design = design.unlayer;
                    }

                    if (typeof design === 'string') {
                        const trimmed = design.trim();
                        if (trimmed) {
                            try {
                                design = JSON.parse(trimmed);
                            } catch (e) {
                                throw new Error('Template design is not valid JSON.');
                            }
                        }
                    }

                    if (!design || typeof design !== 'object') {
                        throw new Error('Template design is invalid.');
                    }

                    if (!design.body && (design.rows || design.values)) {
                        design = { counters: {}, body: design };
                    }

                    if (!design.body || typeof design.body !== 'object') {
                        throw new Error('Template design is missing body.');
                    }

                    try {
                        if (typeof structuredClone === 'function') {
                            design = structuredClone(design);
                        } else {
                            design = JSON.parse(JSON.stringify(design));
                        }
                    } catch (e) {
                        try {
                            design = JSON.parse(JSON.stringify(design));
                        } catch (e2) {
                            const msg = (e2 && e2.message) ? e2.message : ((e && e.message) ? e.message : 'Unknown error');
                            throw new Error('Template design could not be cloned: ' + msg);
                        }
                    }

                    if (window.__mailpurseUnlayerReady === true && window.unlayer && typeof window.unlayer.loadDesign === 'function') {
                        window.unlayer.loadDesign(design);
                        this.importOpen = false;
                        return;
                    }

                    window.__mailpurseUnlayerPendingDesign = design;
                    return waitForUnlayer().then(() => {
                        if (window.unlayer && typeof window.unlayer.loadDesign === 'function') {
                            window.unlayer.loadDesign(design);
                        }
                        this.importOpen = false;
                    });
                })
                .catch((e) => {
                    const msg = (e && e.message) ? e.message : null;
                    this.error = msg ? ('Failed to import this template: ' + msg) : 'Failed to import this template.';
                })
                .finally(() => {
                    this.importing = false;
                });
            return;
        }

        if (opts.builder === 'grapesjs' && builder === 'grapesjs' && window.__mailpurseGrapesEditor) {
            try {
                const editor = window.__mailpurseGrapesEditor;
                const builderData = this.preview.builder_data || null;
                const components = builderData && builderData.components ? builderData.components : null;
                const styles = builderData && builderData.styles ? builderData.styles : null;
                const html = this.preview.html_content || null;

                if (components) {
                    editor.setComponents(components);
                } else if (html) {
                    editor.setComponents(html);
                }
                if (styles) {
                    editor.setStyle(styles);
                }

                this.importOpen = false;
            } catch (e) {
                this.error = 'Failed to import this template.';
            } finally {
                this.importing = false;
            }
            return;
        }

        this.importing = false;
        this.error = 'This template is not compatible with the current builder.';
    },

    importSelected() {
        this.doImport();
    },

    aiGenerate() {
        this.ai.loading = true;
        this.ai.error = '';
        this.error = '';

        fetch(opts.aiUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': opts.csrfToken,
            },
            body: JSON.stringify({
                provider: this.ai.provider,
                model: this.ai.model || null,
                prompt: this.ai.prompt,
                builder: opts.builder || 'grapesjs',
            }),
        })
            .then(async (r) => {
                if (!r.ok) {
                    const payload = await r.json().catch(() => null);
                    const message = payload && payload.message ? payload.message : 'Failed to generate template.';
                    throw new Error(message);
                }
                return r.json();
            })
            .then((data) => {
                this.preview = data;
            })
            .catch((e) => {
                this.ai.error = (e && e.message) ? e.message : 'Failed to generate template.';
            })
            .finally(() => {
                this.ai.loading = false;
            });
    },
}));

Alpine.data('headerSearch', (config = {}) => ({
    query: config.initialQuery || '',
    open: false,
    loading: false,
    items: [],
    activeIndex: -1,
    debounceMs: config.debounceMs || 200,
    minChars: config.minChars || 2,
    suggestUrl: config.suggestUrl || '',
    searchUrl: config.searchUrl || '',
    variant: config.variant || 'admin',
    _debounceTimer: null,
    _abortController: null,

    get hasItems() {
        return Array.isArray(this.items) && this.items.length > 0;
    },

    get searchUrlWithQuery() {
        if (!this.searchUrl) {
            return '#';
        }
        const q = (this.query || '').trim();
        return q ? `${this.searchUrl}?q=${encodeURIComponent(q)}` : this.searchUrl;
    },

    get dropdownBgClass() {
        return this.variant === 'customer' ? 'bg-admin-sidebar border-gray-500' : 'bg-admin-sidebar border-gray-500';
    },

    get itemHoverClass() {
        return this.variant === 'customer' ? 'hover:bg-white/5' : 'hover:bg-white/5';
    },

    reset() {
        this.items = [];
        this.activeIndex = -1;
        this.loading = false;
    },

    close() {
        this.open = false;
        this.activeIndex = -1;
    },

    onFocus() {
        const q = (this.query || '').trim();
        if (q.length >= this.minChars) {
            if (this.hasItems) {
                this.open = true;
            } else {
                this.fetchSuggestions();
            }
        }
    },

    onInput() {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => {
            this.fetchSuggestions();
        }, this.debounceMs);
    },

    async fetchSuggestions() {
        const q = (this.query || '').trim();

        if (!this.suggestUrl || q.length < this.minChars) {
            this.reset();
            this.open = false;
            return;
        }

        if (this._abortController) {
            this._abortController.abort();
        }

        this._abortController = new AbortController();
        this.loading = true;
        this.open = true;

        try {
            const res = await window.axios.get(this.suggestUrl, {
                params: { q },
                signal: this._abortController.signal,
            });
            this.items = res?.data?.items || [];
            this.activeIndex = this.items.length ? 0 : -1;
        } catch (e) {
            if (e?.name !== 'CanceledError' && e?.code !== 'ERR_CANCELED') {
                this.items = [];
                this.activeIndex = -1;
            }
        } finally {
            this.loading = false;
        }
    },

    onKeydown(e) {
        if (!this.open) {
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            this.close();
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!this.hasItems) {
                return;
            }
            this.activeIndex = Math.min(this.items.length - 1, this.activeIndex + 1);
            return;
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!this.hasItems) {
                return;
            }
            this.activeIndex = Math.max(0, this.activeIndex - 1);
            return;
        }

        if (e.key === 'Enter') {
            if (this.activeIndex >= 0 && this.items[this.activeIndex]?.url) {
                e.preventDefault();
                window.location.href = this.items[this.activeIndex].url;
            }
        }
    },

    select(item) {
        if (item?.url) {
            window.location.href = item.url;
        }
    },
}));

Alpine.data('ragChat', (config = {}) => ({
    query: '',
    provider: config.provider || (config.providerModels && Object.keys(config.providerModels)[0]) || 'chatgpt',
    model: config.model || '',
    availableModels: config.providerModels || {},
    loading: false,
    conversationId: config.initialConversationId || null,
    askUrl: config.askUrl || '',
    indexUrl: config.indexUrl || '',
    feedbackUrl: config.feedbackUrl || '',
    csrfToken: config.csrfToken || '',
    noModelsText: config.noModelsText || 'No models',

    init() {
        this.$nextTick(() => {
            const el = this.$refs.messages;
            if (el) {
                el.scrollTop = el.scrollHeight;
                el.addEventListener('click', (event) => {
                    const feedbackBtn = event.target.closest('[data-chat-feedback]');
                    if (feedbackBtn) {
                        this.submitFeedback(feedbackBtn);
                        return;
                    }
                    const copyBtn = event.target.closest('[data-chat-copy]');
                    if (copyBtn) {
                        this.copyMessage(copyBtn.dataset.copyContent || '');
                    }
                });
                this.updateAllFeedbackRows();
            }
        });
        this.updateModel();
    },

    updateModel() {
        const models = this.availableModels[this.provider] || [];
        this.model = models[0] || '';
    },

    getModelOptions() {
        const models = this.availableModels[this.provider] || [];
        if (models.length === 0) {
            return `<option value="">${this.escapeHtml(this.noModelsText)}</option>`;
        }
        return models.map((m) => `<option value="${this.escapeHtml(m)}">${this.escapeHtml(m)}</option>`).join('');
    },

    escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) {
            return '';
        }
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    newConversation() {
        window.location.href = this.indexUrl || window.location.pathname;
    },

    async sendQuery() {
        if (this.loading || !this.query.trim()) return;

        const userQuery = this.query.trim();
        this.query = '';
        this.loading = true;

        this.appendMessage('user', userQuery);

        this.$nextTick(() => {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
        });

        try {
            const formData = new FormData();
            formData.append('query', userQuery);
            formData.append('provider', this.provider);
            formData.append('model', this.model);
            if (this.conversationId) formData.append('conversation_id', this.conversationId);

            const response = await fetch(this.askUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                if (data.conversation_id) this.conversationId = data.conversation_id;
                this.appendMessage('assistant', data.answer);
            } else {
                this.appendMessage('assistant', '⚠️ ' + (data.message || 'Failed to get response.'));
            }
        } catch (err) {
            this.appendMessage('assistant', '⚠️ Network error. Please try again.');
        } finally {
            this.loading = false;
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    },

    appendMessage(role, content) {
        const container = this.$refs.messages;
        if (!container) return;

        const messageIndex = container.children.length;

        const wrapper = document.createElement('div');
        wrapper.className = 'flex flex-col ' + (role === 'user' ? 'items-end' : 'items-start');

        const bubble = document.createElement('div');
        bubble.className = 'max-w-[80%] rounded-2xl px-4 py-3 ' + (role === 'user' ? 'bg-[#1E5FEA] text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-900 dark:text-admin-text-primary');

        if (role === 'user') {
            const p = document.createElement('p');
            p.className = 'text-sm whitespace-pre-wrap';
            p.textContent = content;
            bubble.appendChild(p);
        } else {
            const div = document.createElement('div');
            div.className = 'text-sm prose prose-sm max-w-none';
            div.innerHTML = content;
            bubble.appendChild(div);
        }

        wrapper.appendChild(bubble);

        if (role === 'assistant' && this.conversationId && this.feedbackUrl) {
            wrapper.appendChild(this.createFeedbackRow(this.conversationId, messageIndex, '', content));
        }

        container.appendChild(wrapper);
    },

    createFeedbackRow(conversationId, messageIndex, selected, copyText) {
        const row = document.createElement('div');
        row.className = 'chat-feedback-row mt-2 flex gap-2';
        row.dataset.feedbackSelected = selected || '';
        if (copyText !== undefined) {
            row.dataset.copyContent = copyText;
        }

        const types = [
            {
                type: 'like',
                label: 'Helpful',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>',
            },
            {
                type: 'dislike',
                label: 'Not helpful',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></svg>',
            },
            {
                type: 'copy',
                label: 'Copy',
                attr: 'data-chat-copy',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>',
            },
        ];

        types.forEach(({ type, label, attr, icon }) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chat-feedback-btn p-1 rounded transition-colors focus:outline-none text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300';
            if (attr) {
                btn.setAttribute(attr, '');
            } else {
                btn.dataset.chatFeedback = type;
                btn.dataset.conversationId = conversationId;
                btn.dataset.messageIndex = messageIndex;
            }
            btn.setAttribute('aria-label', label);
            btn.innerHTML = icon;
            row.appendChild(btn);
        });

        this.updateFeedbackRowClasses(row);
        return row;
    },

    updateFeedbackRowClasses(row) {
        const selected = row.dataset.feedbackSelected || '';
        const likeBtn = row.querySelector('[data-chat-feedback="like"]');
        const dislikeBtn = row.querySelector('[data-chat-feedback="dislike"]');

        if (likeBtn) {
            likeBtn.className = 'chat-feedback-btn p-1 rounded transition-colors focus:outline-none ' + (selected === 'like' ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300');
        }
        if (dislikeBtn) {
            dislikeBtn.className = 'chat-feedback-btn p-1 rounded transition-colors focus:outline-none ' + (selected === 'dislike' ? 'text-red-500' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300');
        }
    },

    updateAllFeedbackRows() {
        const container = this.$refs.messages;
        if (!container) return;
        container.querySelectorAll('.chat-feedback-row').forEach((row) => {
            this.updateFeedbackRowClasses(row);
        });
    },

    async submitFeedback(btn) {
        const type = btn.dataset.chatFeedback;
        const row = btn.closest('.chat-feedback-row');
        const conversationId = btn.dataset.conversationId;
        const messageIndex = btn.dataset.messageIndex;
        if (!type || !row || !conversationId || !this.feedbackUrl) {
            return;
        }

        row.dataset.feedbackSelected = type;
        this.updateFeedbackRowClasses(row);

        try {
            const formData = new FormData();
            formData.append('conversation_id', conversationId);
            formData.append('message_index', messageIndex);
            formData.append('feedback', type);

            await fetch(this.feedbackUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });
        } catch (e) {
            // Ignore network errors
        }
    },

    async copyMessage(text) {
        if (!text) return;
        const tmp = document.createElement('div');
        tmp.innerHTML = text;
        text = tmp.textContent || tmp.innerText || '';
        try {
            await navigator.clipboard.writeText(text);
        } catch (e) {
            // Fallback
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
    },
}));

Alpine.data('modelSelector', (config = {}) => ({
    providerModels: config.providerModels || {},
    selectedProvider: config.selectedProvider || '',
    selectedModel: config.selectedModel || '',
    open: false,
    search: '',
    dropup: config.dropup || false,
    placeholder: config.placeholder || 'Select model',
    allowEmpty: config.allowEmpty || false,
    emptyLabel: config.emptyLabel || 'None',

    providerMeta(provider) {
        const map = {
            chatgpt: { color: '#10A37F', label: 'ChatGPT' },
            claude: { color: '#D97757', label: 'Claude' },
            gemini: { color: '#4285F4', label: 'Gemini' },
            deepseek: { color: '#4D6BFA', label: 'DeepSeek' },
            kimi: { color: '#1E1E1E', label: 'Kimi' },
            glm: { color: '#3B68F7', label: 'GLM' },
            grok: { color: '#1DA1F2', label: 'Grok' },
            openrouter: { color: '#F6882D', label: 'OpenRouter' },
        };
        return map[provider] || { color: '#6B7280', label: provider };
    },

    modelLabel(provider, model) {
        const labels = {
            'gpt-5.5': 'GPT-5.5',
            'gpt-5.5-pro': 'GPT-5.5 Pro',
            'gpt-5.4': 'GPT-5.4',
            'gpt-5.4-pro': 'GPT-5.4 Pro',
            'gpt-5.4-mini': 'GPT-5.4 Mini',
            'gpt-5.4-nano': 'GPT-5.4 Nano',
            'gpt-5.2': 'GPT-5.2',
            'gpt-5.2-pro': 'GPT-5.2 Pro',
            'gpt-5.1': 'GPT-5.1',
            'gpt-5': 'GPT-5',
            'gpt-5-pro': 'GPT-5 Pro',
            'gpt-5-mini': 'GPT-5 Mini',
            'gpt-5-nano': 'GPT-5 Nano',
            'gpt-4.1': 'GPT-4.1',
            'gpt-4.1-mini': 'GPT-4.1 Mini',
            'gpt-4.1-nano': 'GPT-4.1 Nano',
            'gpt-4o': 'GPT-4o',
            'gpt-4o-mini': 'GPT-4o Mini',
            'o4-mini': 'o4-mini',
            'o3': 'o3',
            'o3-mini': 'o3-mini',
            'o3-pro': 'o3-pro',
            'gpt-4-32k': 'GPT-4 32k',
            'gpt-3.5-turbo': 'GPT-3.5 Turbo',
        };
        return labels[model] || model;
    },

    modelTag(model) {
        const tags = {
            'gpt-5-nano': 'Free',
            'gpt-4.1-nano': 'Free',
            'gpt-4o-mini': 'Free',
            'gpt-3.5-turbo': 'Free',
            'gpt-5-mini': 'Fast',
            'gpt-5.4-nano': 'Fast',
            'gpt-4.1-mini': 'Fast',
        };
        return tags[model] || '';
    },

    modelMeter(model) {
        const meters = {
            'gpt-5-nano': 100,
            'gpt-4.1-nano': 100,
            'gpt-4o-mini': 100,
            'gpt-3.5-turbo': 100,
            'gpt-5-mini': 95,
            'gpt-5.4-nano': 95,
            'gpt-4.1-mini': 90,
            'gpt-5.1': 90,
            'gpt-5': 90,
            'gpt-4o': 90,
            'gpt-5.4-mini': 85,
            'o4-mini': 85,
            'o3-mini': 85,
            'gpt-4.1': 80,
            'gpt-5.4': 80,
            'o3': 80,
            'gpt-5.2': 75,
            'gpt-5-pro': 75,
            'gpt-5.5': 75,
            'gpt-5.4-pro': 70,
            'gpt-5.2-pro': 65,
            'gpt-5.5-pro': 65,
            'o3-pro': 65,
            'gpt-4-32k': 50,
        };
        return meters[model] || 70;
    },

    meterClass(tag) {
        if (tag === 'Free') return 'bg-green-500';
        if (tag === 'Fast') return 'bg-blue-500';
        if (tag === 'Thinking') return 'bg-amber-500';
        return 'bg-gray-400';
    },

    buildItem(provider, model) {
        const meta = this.providerMeta(provider);
        const tag = this.modelTag(model);
        return {
            provider,
            model,
            label: this.modelLabel(provider, model),
            tag,
            meter: this.modelMeter(model),
            meterClass: this.meterClass(tag),
            color: meta.color,
            providerLabel: meta.label,
            icon: meta.label.charAt(0).toUpperCase(),
        };
    },

    get allItems() {
        const items = [];
        Object.entries(this.providerModels).forEach(([provider, models]) => {
            models.forEach((model) => items.push(this.buildItem(provider, model)));
        });
        return items;
    },

    get recommendedItems() {
        const rec = [];
        Object.entries(this.providerModels).forEach(([provider, models]) => {
            if (models.length > 0) {
                const item = this.buildItem(provider, models[0]);
                item.tag = item.tag || 'Recommended';
                item.meterClass = this.meterClass(item.tag);
                rec.push(item);
            }
        });
        return rec;
    },

    get filteredGroups() {
        const q = this.search.trim().toLowerCase();
        const groups = [];

        const rec = this.recommendedItems.filter((item) => {
            return q === '' || item.label.toLowerCase().includes(q) || item.providerLabel.toLowerCase().includes(q);
        });
        if (rec.length > 0) {
            groups.push({ key: 'recommended', label: 'Recommended', models: rec });
        }

        Object.entries(this.providerModels).forEach(([provider, models]) => {
            const meta = this.providerMeta(provider);
            const filtered = models
                .map((model) => this.buildItem(provider, model))
                .filter((item) => q === '' || item.label.toLowerCase().includes(q) || item.providerLabel.toLowerCase().includes(q));

            if (filtered.length > 0) {
                groups.push({ key: provider, label: meta.label, models: filtered });
            }
        });

        return groups;
    },

    get selectedItem() {
        if (this.allowEmpty && !this.selectedModel) {
            const meta = this.providerMeta(this.selectedProvider || 'chatgpt');
            return {
                provider: this.selectedProvider,
                model: '',
                label: this.emptyLabel,
                tag: '',
                meter: 0,
                meterClass: 'bg-gray-300',
                color: meta.color,
                providerLabel: meta.label,
                icon: meta.label.charAt(0).toUpperCase(),
            };
        }
        const found = this.allItems.find((item) => item.provider === this.selectedProvider && item.model === this.selectedModel);
        if (found) return found;
        const meta = this.providerMeta(this.selectedProvider || 'chatgpt');
        return {
            provider: this.selectedProvider,
            model: this.selectedModel,
            label: this.selectedModel || 'Select model',
            tag: '',
            meter: 70,
            meterClass: 'bg-gray-400',
            color: meta.color,
            providerLabel: meta.label,
            icon: meta.label.charAt(0).toUpperCase(),
        };
    },

    select(provider, model) {
        this.selectedProvider = provider;
        this.selectedModel = model;
        this.open = false;
        this.search = '';
        this.$el.dispatchEvent(new CustomEvent('model-selected', {
            bubbles: true,
            detail: { provider, model },
        }));
    },
}));

Alpine.data('agentChat', (config = {}) => ({
    query: '',
    provider: config.initialProvider || 'chatgpt',
    model: config.initialModel || '',
    availableModels: config.providerModels || {},
    agentId: config.agentId || null,
    conversationId: config.initialConversationId || null,
    messages: config.messages || [],
    conversations: config.conversations || [],
    askUrl: config.askUrl || '',
    askStreamUrl: config.askStreamUrl || '',
    feedbackUrl: config.feedbackUrl || '',
    conversationUrl: config.conversationUrl || '',
    conversationsListUrl: config.conversationsListUrl || '',
    csrfToken: config.csrfToken || '',
    loading: false,
    loadingMessages: false,
    loadingMoreConversations: false,
    hasMoreConversations: config.hasMoreConversations || false,
    agentModalOpen: false,
    searchConversations: '',

    init() {
        if (this.conversationId && this.conversationUrl) {
            this.loadConversation(this.conversationId);
        } else {
            this.scrollToBottom();
        }
    },

    get filteredConversations() {
        const q = this.searchConversations.trim().toLowerCase();
        if (!q) return this.conversations;
        return this.conversations.filter((c) => (c.title || '').toLowerCase().includes(q));
    },

    async loadConversation(id) {
        if (!id || !this.conversationUrl) return;
        this.loadingMessages = true;
        this.messages = [];
        try {
            const url = this.conversationUrl.replace('__ID__', encodeURIComponent(id));
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data && data.messages) {
                this.messages = data.messages;
            }
        } catch (e) {
            // ignore
        } finally {
            this.loadingMessages = false;
            this.scrollToBottom();
        }
    },

    async loadMoreConversations() {
        if (this.loadingMoreConversations || !this.hasMoreConversations || !this.conversationsListUrl) return;
        this.loadingMoreConversations = true;
        try {
            const excludeIds = this.conversations.map((c) => c.id).join(',');
            const url = `${this.conversationsListUrl}?exclude=${encodeURIComponent(excludeIds)}&per_page=20`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data && data.success) {
                this.conversations = this.conversations.concat(data.conversations || []);
                this.hasMoreConversations = !!data.has_more;
            }
        } catch (e) {
            // ignore
        } finally {
            this.loadingMoreConversations = false;
        }
    },

    onConversationsScroll(e) {
        const el = e.target;
        if (el.scrollTop + el.clientHeight >= el.scrollHeight - 60) {
            this.loadMoreConversations();
        }
    },

    scrollToBottom() {
        this.$nextTick(() => {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
        });
    },

    updateUrl() {
        const params = new URLSearchParams(window.location.search);
        params.set('agent', this.agentId);
        if (this.conversationId) {
            params.set('conversation', this.conversationId);
        } else {
            params.delete('conversation');
        }
        const url = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', url);
    },

    async sendQuery() {
        if (this.loading || !this.query.trim()) return;

        const userQuery = this.query.trim();
        this.query = '';
        this.loading = true;

        this.messages.push({
            role: 'user',
            content: userQuery,
            timestamp: new Date().toISOString(),
        });
        this.scrollToBottom();

        const assistantIdx = this.messages.push({
            role: 'assistant',
            content: '',
            feedback: '',
            streaming: true,
            timestamp: new Date().toISOString(),
        }) - 1;

        try {
            const formData = new FormData();
            formData.append('query', userQuery);
            formData.append('agent_id', this.agentId);
            formData.append('provider', this.provider);
            formData.append('model', this.model);
            if (this.conversationId) formData.append('conversation_id', this.conversationId);

            const response = await fetch(this.askStreamUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'text/event-stream',
                },
                body: formData,
            });

            if (!response.ok || !response.body) {
                this.messages[assistantIdx].content = '⚠️ Request failed (HTTP ' + response.status + ').';
                this.messages[assistantIdx].streaming = false;
                return;
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let fullText = '';
            let doneData = null;
            let errorData = null;

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });

                const lines = buffer.split('\n');
                buffer = lines.pop();

                let currentEvent = '';
                for (const line of lines) {
                    const trimmed = line.trim();
                    if (trimmed === '') {
                        currentEvent = '';
                        continue;
                    }
                    if (trimmed.startsWith('event: ')) {
                        currentEvent = trimmed.substring(7);
                        continue;
                    }
                    if (!trimmed.startsWith('data: ')) continue;

                    const dataStr = trimmed.substring(6);
                    let data;
                    try { data = JSON.parse(dataStr); } catch { continue; }

                    if (currentEvent === 'delta' && data.text) {
                        fullText += data.text;
                        this.messages[assistantIdx].content = fullText;
                        this.scrollToBottom();
                    } else if (currentEvent === 'done') {
                        doneData = data;
                    } else if (currentEvent === 'error') {
                        errorData = data;
                    }
                }
            }

            if (errorData) {
                this.messages[assistantIdx].content = '⚠️ ' + (errorData.message || 'Failed to get response.');
            } else if (doneData) {
                if (doneData.answer) {
                    this.messages[assistantIdx].content = doneData.answer;
                }
                this.messages[assistantIdx].sources = doneData.sources || [];

                if (doneData.conversation_id) {
                    const isNew = !this.conversationId;
                    this.conversationId = doneData.conversation_id;
                    const lastPreview = (doneData.answer || userQuery).substring(0, 60);
                    const nowText = 'Just now';
                    if (isNew) {
                        this.conversations.unshift({
                            id: doneData.conversation_id,
                            title: userQuery.substring(0, 60),
                            preview: lastPreview,
                            updated_at: nowText,
                        });
                    } else {
                        const idx = this.conversations.findIndex((c) => c.id === doneData.conversation_id);
                        if (idx !== -1) {
                            const conv = this.conversations[idx];
                            conv.preview = lastPreview;
                            conv.updated_at = nowText;
                            this.conversations.splice(idx, 1);
                            this.conversations.unshift(conv);
                        }
                    }
                    this.updateUrl();
                }
            } else if (!fullText) {
                this.messages[assistantIdx].content = '⚠️ Empty response from AI.';
            }
        } catch (err) {
            this.messages[assistantIdx].content = '⚠️ Network error. Please try again.';
        } finally {
            this.messages[assistantIdx].streaming = false;
            this.loading = false;
            this.scrollToBottom();
        }
    },

    newChat() {
        const params = new URLSearchParams();
        params.set('agent', this.agentId);
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    },

    async submitFeedback(index, type) {
        if (!this.messages[index] || this.messages[index].role === 'user') return;

        this.messages[index].feedback = type;

        if (!this.conversationId || !this.feedbackUrl) return;

        try {
            const formData = new FormData();
            formData.append('conversation_id', this.conversationId);
            formData.append('message_index', index);
            formData.append('feedback', type);

            await fetch(this.feedbackUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });
        } catch (e) {
            // Ignore network errors
        }
    },

    async copyToClipboard(text) {
        if (!text) return;
        const tmp = document.createElement('div');
        tmp.innerHTML = text;
        text = tmp.textContent || tmp.innerText || '';
        try {
            await navigator.clipboard.writeText(text);
        } catch (e) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
    },

    handleKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            this.sendQuery();
        }
    },
}));

Alpine.start();

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) {
        return '';
    }
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getNotificationsUiVariant(feedUrl) {
    if (!feedUrl) {
        return 'admin';
    }
    return feedUrl.includes('/customer/') ? 'customer' : 'admin';
}

function getNotificationsUiTokens(variant) {
    if (variant === 'customer') {
        return {
            emptyTextClass: 'text-gray-500 dark:text-admin-text-secondary',
            itemTitleClass: 'text-sm font-semibold text-gray-900 dark:text-admin-text-primary',
            itemMessageClass: 'mt-1 text-sm text-gray-600 dark:text-admin-text-secondary',
            itemTimeClass: 'mt-1.5 text-xs text-gray-400',
            iconWrapClass: 'w-10 h-10 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center flex-shrink-0',
            unreadDotClass: 'mt-2 w-2 h-2 rounded-full bg-gray-900 dark:bg-white flex-shrink-0',
            hoverClass: 'hover:bg-gray-50 dark:hover:bg-white/5',
        };
    }

    return {
        emptyTextClass: 'text-admin-text-secondary',
        itemTitleClass: 'text-sm font-semibold text-admin-text-primary',
        itemMessageClass: 'mt-1 text-sm text-admin-text-secondary',
        itemTimeClass: 'mt-1.5 text-xs text-admin-text-secondary/80',
        iconWrapClass: 'w-10 h-10 rounded-full border !border-pink-300 bg-pink-100 flex items-center justify-center flex-shrink-0 color-pink-300',
        unreadDotClass: 'mt-2 w-2 h-2 rounded-full bg-white flex-shrink-0',
        hoverClass: 'hover:bg-white/5',
    };
}

const NOTIFICATIONS_BELL_SVG = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>';

let currentNotificationsTab = 'all';

function renderNotificationsList(listEl, notifications, variant, tab = 'all') {
    if (!listEl) {
        return;
    }

    const t = getNotificationsUiTokens(variant);
    const unreadCount = Array.isArray(notifications)
        ? notifications.filter((n) => !n?.is_read).length
        : 0;

    listEl.dataset.notificationsUnreadCount = String(unreadCount);

    if (tab === 'unread') {
        if (unreadCount <= 0) {
            listEl.innerHTML = `
                <div class="px-6 py-10 text-center text-sm ${t.emptyTextClass}" data-notifications-empty-unread>
                    NO unread notificaitons
                </div>
            `;
            return;
        }

        const unreadNotifications = notifications.filter((n) => !n?.is_read);

        listEl.innerHTML = unreadNotifications
            .map((n) => {
                const title = escapeHtml(n.title || 'Notification');
                const message = n.message ? `<p class="${t.itemMessageClass}">${escapeHtml(n.message)}</p>` : '';
                const time = escapeHtml(n.created_at_human || '');
                const markReadUrl = n && typeof n === 'object' && typeof n.mark_read_url === 'string'
                    ? n.mark_read_url
                    : null;

                const url = (n && typeof n === 'object' && n.data && typeof n.data === 'object' && typeof n.data.url === 'string')
                    ? n.data.url
                    : null;

                const contentClass = `min-w-0 flex-1 ${url ? t.hoverClass : ''}`;
                const contentOpen = url
                    ? `<a href="${escapeHtml(url)}" class="${contentClass}" data-notifications-status="unread">`
                    : `<div class="${contentClass}" data-notifications-status="unread">`;
                const contentClose = url ? '</a>' : '</div>';
                const markReadButton = markReadUrl
                    ? `<button type="button" class="ml-3 shrink-0 rounded-md px-2.5 py-1 text-xs font-medium text-primary-600 hover:bg-primary-50 hover:text-primary-700 dark:text-primary-300 dark:hover:bg-white/5" data-notification-mark-read data-notification-mark-read-url="${escapeHtml(markReadUrl)}" aria-label="Mark notification as read">Mark read</button>`
                    : '';

                return `
                    <div class="px-6 py-4 flex items-start gap-3">
                        ${contentOpen}
                            <p class="${t.itemTitleClass}">${title}</p>
                            ${message}
                            <p class="${t.itemTimeClass}">${time}</p>
                        ${contentClose}
                        <span class="mt-2 w-2 h-2 rounded-full ${variant === 'customer' ? 'bg-gray-900 dark:bg-white' : 'bg-white'} flex-shrink-0"></span>
                        ${markReadButton}
                    </div>
                `;
            })
            .join('');
        return;
    }

    if (!Array.isArray(notifications) || notifications.length === 0) {
        listEl.innerHTML = `
            <div class="px-6 py-10 text-center text-sm ${t.emptyTextClass}" data-notifications-empty-all>
                No notifications yet.
            </div>
        `;
        return;
    }

    listEl.innerHTML = notifications
        .map((n) => {
            const title = escapeHtml(n.title || 'Notification');
            const message = n.message ? `<p class="${t.itemMessageClass}">${escapeHtml(n.message)}</p>` : '';
            const time = escapeHtml(n.created_at_human || '');
            const isRead = !!n.is_read;
            const status = isRead ? 'read' : 'unread';
            const dot = isRead ? '' : `<span class="${t.unreadDotClass}"></span>`;
            const markReadUrl = n && typeof n === 'object' && typeof n.mark_read_url === 'string'
                ? n.mark_read_url
                : null;

            const url = (n && typeof n === 'object' && n.data && typeof n.data === 'object' && typeof n.data.url === 'string')
                ? n.data.url
                : null;

            const contentClass = `min-w-0 flex-1 ${url ? t.hoverClass : ''}`;
            const contentOpen = url
                ? `<a href="${escapeHtml(url)}" class="${contentClass}" data-notifications-status="${status}">`
                : `<div class="${contentClass}" data-notifications-status="${status}">`;
            const contentClose = url ? '</a>' : '</div>';
            const markReadButton = !isRead && markReadUrl
                ? `<button type="button" class="ml-3 shrink-0 rounded-md px-2.5 py-1 text-xs font-medium text-primary-600 hover:bg-primary-50 hover:text-primary-700 dark:text-primary-300 dark:hover:bg-white/5" data-notification-mark-read data-notification-mark-read-url="${escapeHtml(markReadUrl)}" aria-label="Mark notification as read">Mark read</button>`
                : '';
            const iconMarkup = `<div class="${t.iconWrapClass}" data-notification-icon>${NOTIFICATIONS_BELL_SVG}</div>`;

            return `
                <div class="px-6 py-4 flex items-start gap-3">
                    ${iconMarkup}
                    ${contentOpen}
                        <p class="${t.itemTitleClass}">${title}</p>
                        ${message}
                        <p class="${t.itemTimeClass}">${time}</p>
                    ${contentClose}
                    ${dot}
                    ${markReadButton}
                </div>
            `;
        })
        .join('');
}

function updateNotificationsTabCount(notifications) {
    const total = Array.isArray(notifications) ? notifications.length : 0;
    document.querySelectorAll('[data-notifications-tab-count="all"]').forEach((el) => {
        el.textContent = String(total);
    });
}

function updateBadge(badgeEl, unreadCount) {
    if (!badgeEl) {
        return;
    }
    const count = Number(unreadCount) || 0;
    if (count <= 0) {
        badgeEl.remove();
        return;
    }
    badgeEl.textContent = count > 9 ? '9+' : String(count);
}

function ensureBadge(rootButtonEl, unreadCount) {
    const count = Number(unreadCount) || 0;
    const existing = rootButtonEl?.querySelector('[data-notifications-badge]') || null;
    if (count <= 0) {
        if (existing) {
            existing.remove();
        }
        return;
    }
    if (existing) {
        return;
    }

    const span = document.createElement('span');
    span.setAttribute('data-notifications-badge', '');
    span.className = 'absolute top-1 right-1 w-2 h-2 rounded-full bg-red-500';
    rootButtonEl.appendChild(span);
}

function updateUnreadLabel(labelEl, unreadCount) {
    if (!labelEl) {
        return;
    }
    const count = Number(unreadCount) || 0;
    labelEl.textContent = count > 0 ? `${count} unread` : 'Up to date';
}

async function refreshNotifications(rootButtonEl, containerEl) {
    const feedUrl = rootButtonEl?.getAttribute('data-notifications-feed-url');
    if (!feedUrl) {
        return;
    }

    // Drawer is teleported to <body>, so always look up list/label from document.
    const listEl = document.querySelector('[data-notifications-list]');
    const labelEl = document.querySelector('[data-notifications-unread-label]');

    try {
        const res = await window.axios.get(feedUrl);
        const unreadCount = res?.data?.unread_count ?? 0;
        const notifications = res?.data?.notifications ?? [];

        ensureBadge(rootButtonEl, unreadCount);
        updateUnreadLabel(labelEl, unreadCount);
        updateNotificationsTabCount(notifications);

        const variant = getNotificationsUiVariant(feedUrl);
        listEl.__mailpurseNotifications = notifications;
        listEl.__mailpurseNotificationsVariant = variant;
        renderNotificationsList(listEl, notifications, variant, currentNotificationsTab);
    } catch (e) {
        // Silent failure: avoid spamming console in production
    }
}

window.addEventListener('mailpurse-notifications-tab-change', (event) => {
    currentNotificationsTab = event?.detail?.tab === 'unread' ? 'unread' : 'all';

    const listEl = document.querySelector('[data-notifications-list]');
    if (!listEl) {
        return;
    }

    const notifications = Array.isArray(listEl.__mailpurseNotifications)
        ? listEl.__mailpurseNotifications
        : [];
    const variant = listEl.__mailpurseNotificationsVariant || 'admin';

    renderNotificationsList(listEl, notifications, variant, currentNotificationsTab);
});

function parseInlineConfirmMessage(code) {
    if (!code || typeof code !== 'string') {
        return '';
    }

    const idx = code.indexOf('confirm(');
    if (idx === -1) {
        return '';
    }

    const slice = code.slice(idx);
    const m = slice.match(/confirm\(\s*(['"])([\s\S]*?)\1\s*\)/);
    if (!m) {
        return '';
    }

    return (m[2] || '').toString();
}

function setupCustomConfirmModal() {
    if (window.__mailpurseCustomConfirmModalInit) {
        return;
    }
    window.__mailpurseCustomConfirmModalInit = true;

    document.addEventListener(
        'submit',
        (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (form.dataset && form.dataset.mpSkipConfirm === '1') {
                return;
            }

            const onsubmit = form.getAttribute('onsubmit') || '';
            if (!onsubmit.includes('confirm(')) {
                return;
            }

            const hasConfirmModal = !!document.querySelector('[data-confirm-modal-root]');
            if (!hasConfirmModal) {
                return;
            }

            const message = parseInlineConfirmMessage(onsubmit) || 'This action cannot be undone.';

            const methodInput = form.querySelector('input[name="_method"]');
            const methodOverride = methodInput ? String(methodInput.value || '').toUpperCase() : '';
            const isDelete = methodOverride === 'DELETE';

            window.dispatchEvent(
                new CustomEvent('open-confirm-modal', {
                    detail: {
                        form,
                        title: 'Are you sure?',
                        message,
                        confirmText: isDelete ? 'Delete' : 'Confirm',
                        cancelText: 'Cancel',
                        variant: isDelete ? 'danger' : 'default',
                    },
                })
            );

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        },
        true
    );
}

function setupNotificationWidget(rootButtonEl, allWidgets) {
    if (!rootButtonEl || !window.axios) {
        return;
    }

    const containerEl = rootButtonEl.parentElement;
    // Mark-all-read button lives in the teleported drawer (body); query the document.
    const markAllReadButton = document.querySelector('[data-notifications-mark-all-read]');
    const markAllReadUrl = rootButtonEl.getAttribute('data-notifications-mark-all-read-url');

    if (markAllReadButton && markAllReadUrl) {
        markAllReadButton.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await window.axios.post(markAllReadUrl);
            } finally {
                (allWidgets || []).forEach((btn) => {
                    refreshNotifications(btn, btn.parentElement);
                });
            }
        });
    }

    if (!window.__mailpurseNotificationMarkReadInit) {
        window.__mailpurseNotificationMarkReadInit = true;
        document.addEventListener('click', async (e) => {
            const button = e.target instanceof Element ? e.target.closest('[data-notification-mark-read]') : null;
            if (!button) {
                return;
            }

            const url = button.getAttribute('data-notification-mark-read-url');
            if (!url) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            try {
                button.setAttribute('disabled', 'disabled');
                await window.axios.post(url);
            } finally {
                button.removeAttribute('disabled');
                (allWidgets || []).forEach((btn) => {
                    refreshNotifications(btn, btn.parentElement);
                });
            }
        });
    }

    // Refresh when the dropdown is opened
    rootButtonEl.addEventListener('click', () => {
        refreshNotifications(rootButtonEl, containerEl);
    });
}

function setupNotificationPolling() {
    const rootButtons = document.querySelectorAll('[data-notifications-root]');
    if (!rootButtons.length) {
        return;
    }

    const widgets = Array.from(rootButtons);
    widgets.forEach((btn) => setupNotificationWidget(btn, widgets));

    // Initial fetch + periodic refresh (single interval for all widgets)
    const refreshAll = () => {
        widgets.forEach((btn) => {
            refreshNotifications(btn, btn.parentElement);
        });
    };

    refreshAll();

    if (window.__mailpurseNotificationsIntervalId) {
        clearInterval(window.__mailpurseNotificationsIntervalId);
    }
    window.__mailpurseNotificationsIntervalId = setInterval(refreshAll, 15000);
}

function ensureUnlayerScriptLoaded() {
    if (window.unlayer && typeof window.unlayer.init === 'function') {
        return Promise.resolve();
    }

    if (window.__mailpurseUnlayerEmbedPromise) {
        return window.__mailpurseUnlayerEmbedPromise;
    }

    window.__mailpurseUnlayerEmbedPromise = new Promise((resolve, reject) => {
        try {
            const existing = document.querySelector('script[src="https://editor.unlayer.com/embed.js"]');
            if (existing) {
                const tick = (timeoutMs = 12000) => {
                    const started = Date.now();
                    const wait = () => {
                        if (window.unlayer && typeof window.unlayer.init === 'function') {
                            resolve();
                            return;
                        }
                        if (Date.now() - started >= timeoutMs) {
                            reject(new Error('Unlayer embed.js did not load in time.'));
                            return;
                        }
                        setTimeout(wait, 75);
                    };
                    wait();
                };
                tick();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://editor.unlayer.com/embed.js';
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load Unlayer embed.js.'));
            document.head.appendChild(script);
        } catch (e) {
            reject(e);
        }
    });

    return window.__mailpurseUnlayerEmbedPromise;
}

// Load custom tools from external file
async function loadUnlayerCustomTools() {
    // Simple test code to verify customJS is working
    return `
        console.error('[MailPurse Unlayer customJS] SIMPLE TEST - Script loaded at', new Date().toISOString());
        document.body.style.background = 'rgba(255,0,0,0.3)';
        
        // Test if we can register anything
        setTimeout(function() {
            console.log('[MailPurse Unlayer customJS] Checking for unlayer...', typeof window.unlayer);
            if (window.unlayer) {
                console.log('[MailPurse Unlayer customJS] Unlayer found!');
                console.log('[MailPurse Unlayer customJS] Methods available:', Object.getOwnPropertyNames(window.unlayer).filter(n => typeof window.unlayer[n] === 'function'));
            }
        }, 1000);
    `;
}

const MAILPURSE_UNLAYER_CUSTOM_TOOLS_JS = `(function(){
  try {
    var attempts = 0;

    // Force visible proof that customJS is running inside the iframe
    document.body.style.background = 'rgba(255,0,0,0.3)';
    console.error('[MailPurse Unlayer customJS] Injected and executing at', new Date().toISOString());

    function escAttr(v){
      return String(v == null ? '' : v).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escHtml(v){
      return String(v == null ? '' : v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function post(type, payload){
      try {
        if (window.top && window.top !== window) {
          window.top.postMessage({ type: type, payload: payload || null }, '*');
        }
        if (window.parent && window.parent !== window && (!window.top || window.top === window || window.parent !== window.top)) {
          window.parent.postMessage({ type: type, payload: payload || null }, '*');
        }
      } catch (e) {}
    }

    function getUnlayer(){
      try {
        if (typeof unlayer !== 'undefined') return unlayer;
      } catch (e) {}
      try {
        if (window && window.unlayer) return window.unlayer;
      } catch (e) {}
      return null;
    }

    function boot(){
      try {
        post('mailpurse:unlayer:customjs:tick', { attempts: attempts });
        var u = getUnlayer();
        if (!u || typeof u.registerTool !== 'function' || typeof u.createViewer !== 'function') {
          attempts++;
          if (attempts < 160) {
            setTimeout(boot, 50);
          } else {
            post('mailpurse:unlayer:customjs:timeout', { attempts: attempts });
          }
          return;
        }

        if (window.__mailpurseUnlayerToolsRegistered) {
          post('mailpurse:unlayer:customjs:already-registered');
          return;
        }

        post('mailpurse:unlayer:customjs:unlayer-ready');

        function registerFormTool(){
          function exportForm(){
            return '<div style="padding:16px;border:1px solid #e5e7eb;border-radius:12px;">{{fields}}{{gdpr}}{{submit}}</div>';
          }

          try {
            u.registerTool({
              name: 'mp_form',
              label: 'Form',
              icon: 'fa-wpforms',
              supportedDisplayModes: ['email','web','popup'],
              options: {},
              values: {},
              renderer: {
                Viewer: u.createViewer({
                  render: function(){
                    return '<div style="padding:16px;border:1px dashed #cbd5e1;border-radius:12px;font-size:13px;">Form Container</div>';
                  }
                }),
                exporters: {
                  web: exportForm,
                  popup: exportForm,
                  email: exportForm
                },
                head: { css: function(){}, js: function(){} }
              }
            });
          } catch (e) {
            post('mailpurse:unlayer:customjs:error', { tool: 'mp_form', message: String(e && e.message ? e.message : e), stack: e && e.stack ? String(e.stack) : null });
          }
        }

        function registerInputTool(){
          function exportInput(values){
            var fk = values && values.fieldKey ? String(values.fieldKey) : 'email';
            var ph = values && values.placeholder ? String(values.placeholder) : '';
            var req = values && (values.required === true || values.required === 'true' || values.required === 1 || values.required === '1');
            return '<div data-mailpurse-field="' + escAttr(fk) + '" data-mailpurse-placeholder="' + escAttr(ph) + '"' + (req ? ' data-mailpurse-required="1"' : '') + '></div>';
          }

          u.registerTool({
            name: 'mp_input',
            label: 'Input',
            icon: 'fa-i-cursor',
            supportedDisplayModes: ['email','web','popup'],
            options: {
              field: {
                title: 'Field',
                position: 1,
                options: {
                  fieldKey: { label: 'Field', defaultValue: 'email', widget: 'dropdown' },
                  placeholder: { label: 'Placeholder', defaultValue: '', widget: 'text' },
                  required: { label: 'Required', defaultValue: false, widget: 'toggle' }
                }
              }
            },
            values: {},
            renderer: {
              Viewer: u.createViewer({
                render: function(values){
                  var fk = values && values.fieldKey ? String(values.fieldKey) : 'email';
                  return '<div style="padding:10px;border:1px dashed #cbd5e1;border-radius:10px;font-size:13px;">Input: ' + escHtml(fk) + '</div>';
                }
              }),
              exporters: {
                web: exportInput,
                email: exportInput,
                popup: exportInput
              },
              head: { css: function(){}, js: function(){} }
            }
          });
        }

        function registerTextareaTool(){
          function exportTextarea(values){
            var fk = values && values.fieldKey ? String(values.fieldKey) : '';
            var ph = values && values.placeholder ? String(values.placeholder) : '';
            var req = values && (values.required === true || values.required === 'true' || values.required === 1 || values.required === '1');
            var rows = values && values.rows ? parseInt(values.rows, 10) : 3;
            if (isNaN(rows) || rows < 1) rows = 3;
            return '<div data-mailpurse-textarea="' + escAttr(fk) + '" data-mailpurse-placeholder="' + escAttr(ph) + '" data-mailpurse-rows="' + escAttr(rows) + '"' + (req ? ' data-mailpurse-required="1"' : '') + '></div>';
          }

          u.registerTool({
            name: 'mp_textarea',
            label: 'Textarea',
            icon: 'fa-align-left',
            supportedDisplayModes: ['email','web','popup'],
            options: {
              field: {
                title: 'Field',
                position: 1,
                options: {
                  fieldKey: { label: 'Field', defaultValue: '', widget: 'dropdown' },
                  placeholder: { label: 'Placeholder', defaultValue: '', widget: 'text' },
                  required: { label: 'Required', defaultValue: false, widget: 'toggle' },
                  rows: { label: 'Rows', defaultValue: 3, widget: 'number' }
                }
              }
            },
            values: {},
            renderer: {
              Viewer: u.createViewer({
                render: function(values){
                  var fk = values && values.fieldKey ? String(values.fieldKey) : '';
                  return '<div style="padding:10px;border:1px dashed #cbd5e1;border-radius:10px;font-size:13px;">Textarea: ' + escHtml(fk) + '</div>';
                }
              }),
              exporters: {
                web: exportTextarea,
                email: exportTextarea,
                popup: exportTextarea
              },
              head: { css: function(){}, js: function(){} }
            }
          });
        }

        function registerSubmitTool(){
          function exportSubmit(values){
            var text = values && values.text ? String(values.text) : 'Subscribe';
            var fullWidth = values && (values.fullWidth === true || values.fullWidth === 'true' || values.fullWidth === 1 || values.fullWidth === '1');
            var bg = values && values.backgroundColor ? String(values.backgroundColor) : '#6366f1';
            var tc = values && values.textColor ? String(values.textColor) : '#ffffff';
            var br = values && values.borderRadius != null ? parseInt(values.borderRadius, 10) : 12;
            if (isNaN(br) || br < 0) br = 12;
            var py = values && values.paddingY != null ? parseInt(values.paddingY, 10) : 12;
            if (isNaN(py) || py < 0) py = 12;
            var px = values && values.paddingX != null ? parseInt(values.paddingX, 10) : 16;
            if (isNaN(px) || px < 0) px = 16;
            var style = 'border:0;cursor:pointer;font-weight:700;';
            style += 'background:' + escAttr(bg) + ';color:' + escAttr(tc) + ';';
            style += 'border-radius:' + escAttr(br) + 'px;';
            style += 'padding:' + escAttr(py) + 'px ' + escAttr(px) + 'px;';
            if (fullWidth) style += 'width:100%;display:block;';
            return '<button type="submit" data-mailpurse-submit="1" style="' + style + '">' + escHtml(text) + '</button>';
          }

          u.registerTool({
            name: 'mp_submit',
            label: 'Submit Button',
            icon: 'fa-paper-plane',
            supportedDisplayModes: ['email','web','popup'],
            options: {
              content: {
                title: 'Content',
                position: 1,
                options: {
                  text: { label: 'Text', defaultValue: 'Subscribe', widget: 'text' }
                }
              },
              style: {
                title: 'Style',
                position: 2,
                options: {
                  fullWidth: { label: 'Full Width', defaultValue: true, widget: 'toggle' },
                  backgroundColor: { label: 'Background', defaultValue: '#6366f1', widget: 'color_picker' },
                  textColor: { label: 'Text Color', defaultValue: '#ffffff', widget: 'color_picker' },
                  borderRadius: { label: 'Border Radius', defaultValue: 12, widget: 'number' },
                  paddingY: { label: 'Padding Y', defaultValue: 12, widget: 'number' },
                  paddingX: { label: 'Padding X', defaultValue: 16, widget: 'number' }
                }
              }
            },
            values: {},
            renderer: {
              Viewer: u.createViewer({
                render: function(values){
                  var t = values && values.text ? String(values.text) : 'Subscribe';
                  return '<div style="padding:10px;border:1px dashed #cbd5e1;border-radius:10px;font-size:13px;">Submit Button: ' + escHtml(t) + '</div>';
                }
              }),
              exporters: {
                web: exportSubmit,
                email: exportSubmit,
                popup: exportSubmit
              },
              head: { css: function(){}, js: function(){} }
            }
          });
        }

        registerInputTool();
        registerTextareaTool();
        registerSubmitTool();
        registerFormTool();

        window.__mailpurseUnlayerToolsRegistered = true;
        post('mailpurse:unlayer:tools-registered');
      } catch (e) {}
    }

    post('mailpurse:unlayer:customjs:booted');
    boot();
  } catch (e) {}
})();`;

function registerMailpurseUnlayerTools() {
    if (window.__mailpurseUnlayerToolsRegistered) {
        return;
    }

    if (!window.unlayer || typeof window.unlayer.registerTool !== 'function' || typeof window.unlayer.createViewer !== 'function') {
        return;
    }

    const escAttr = (v) => String(v == null ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const escHtml = (v) => String(v == null ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const exportInput = (values) => {
        const fk = values && values.fieldKey ? String(values.fieldKey) : 'email';
        const ph = values && values.placeholder ? String(values.placeholder) : '';
        const req = !!(values && (values.required === true || values.required === 'true' || values.required === 1 || values.required === '1'));
        return `<div data-mailpurse-field="${escAttr(fk)}" data-mailpurse-placeholder="${escAttr(ph)}"${req ? ' data-mailpurse-required="1"' : ''}></div>`;
    };

    const exportTextarea = (values) => {
        const fk = values && values.fieldKey ? String(values.fieldKey) : '';
        const ph = values && values.placeholder ? String(values.placeholder) : '';
        const req = !!(values && (values.required === true || values.required === 'true' || values.required === 1 || values.required === '1'));
        let rows = values && values.rows != null ? parseInt(values.rows, 10) : 3;
        if (Number.isNaN(rows) || rows < 1) {
            rows = 3;
        }
        return `<div data-mailpurse-textarea="${escAttr(fk)}" data-mailpurse-placeholder="${escAttr(ph)}" data-mailpurse-rows="${escAttr(rows)}"${req ? ' data-mailpurse-required="1"' : ''}></div>`;
    };

    const exportSubmit = (values) => {
        const text = values && values.text ? String(values.text) : 'Subscribe';
        const fullWidth = !!(values && (values.fullWidth === true || values.fullWidth === 'true' || values.fullWidth === 1 || values.fullWidth === '1'));
        const bg = values && values.backgroundColor ? String(values.backgroundColor) : '#6366f1';
        const tc = values && values.textColor ? String(values.textColor) : '#ffffff';

        let br = values && values.borderRadius != null ? parseInt(values.borderRadius, 10) : 12;
        if (Number.isNaN(br) || br < 0) br = 12;
        let py = values && values.paddingY != null ? parseInt(values.paddingY, 10) : 12;
        if (Number.isNaN(py) || py < 0) py = 12;
        let px = values && values.paddingX != null ? parseInt(values.paddingX, 10) : 16;
        if (Number.isNaN(px) || px < 0) px = 16;

        let style = 'border:0;cursor:pointer;font-weight:700;';
        style += `background:${escAttr(bg)};color:${escAttr(tc)};`;
        style += `border-radius:${escAttr(br)}px;`;
        style += `padding:${escAttr(py)}px ${escAttr(px)}px;`;
        if (fullWidth) style += 'width:100%;display:block;';
        return `<button type="submit" data-mailpurse-submit="1" style="${style}">${escHtml(text)}</button>`;
    };

    try {
        window.unlayer.registerTool({
            name: 'mp_input',
            label: 'Input',
            icon: 'fa-i-cursor',
            supportedDisplayModes: ['email', 'web', 'popup'],
            options: {
                field: {
                    title: 'Field',
                    position: 1,
                    options: {
                        fieldKey: { label: 'Field', defaultValue: 'email', widget: 'dropdown' },
                        placeholder: { label: 'Placeholder', defaultValue: '', widget: 'text' },
                        required: { label: 'Required', defaultValue: false, widget: 'toggle' },
                    },
                },
            },
            values: {},
            renderer: {
                Viewer: window.unlayer.createViewer({
                    render(values) {
                        const fk = values && values.fieldKey ? String(values.fieldKey) : 'email';
                        return `<div style='padding:10px;border:1px dashed #cbd5e1;border-radius:10px;font-size:13px;'>Input: ${escHtml(fk)}</div>`;
                    },
                }),
                exporters: {
                    web: exportInput,
                    email: exportInput,
                    popup: exportInput,
                },
                head: { css() {}, js() {} },
            },
        });

        window.unlayer.registerTool({
            name: 'mp_textarea',
            label: 'Textarea',
            icon: 'fa-align-left',
            supportedDisplayModes: ['email', 'web', 'popup'],
            options: {
                field: {
                    title: 'Field',
                    position: 1,
                    options: {
                        fieldKey: { label: 'Field', defaultValue: '', widget: 'dropdown' },
                        placeholder: { label: 'Placeholder', defaultValue: '', widget: 'text' },
                        required: { label: 'Required', defaultValue: false, widget: 'toggle' },
                        rows: { label: 'Rows', defaultValue: 3, widget: 'number' },
                    },
                },
            },
            values: {},
            renderer: {
                Viewer: window.unlayer.createViewer({
                    render(values) {
                        const fk = values && values.fieldKey ? String(values.fieldKey) : '';
                        return `<div style='padding:10px;border:1px dashed #cbd5e1;border-radius:10px;font-size:13px;'>Textarea: ${escHtml(fk)}</div>`;
                    },
                }),
                exporters: {
                    web: exportTextarea,
                    email: exportTextarea,
                    popup: exportTextarea,
                },
                head: { css() {}, js() {} },
            },
        });

        window.unlayer.registerTool({
            name: 'mp_submit',
            label: 'Submit Button',
            icon: 'fa-paper-plane',
            supportedDisplayModes: ['email', 'web', 'popup'],
            options: {
                content: {
                    title: 'Content',
                    position: 1,
                    options: {
                        text: { label: 'Text', defaultValue: 'Subscribe', widget: 'text' },
                    },
                },
                style: {
                    title: 'Style',
                    position: 2,
                    options: {
                        fullWidth: { label: 'Full Width', defaultValue: true, widget: 'toggle' },
                        backgroundColor: { label: 'Background', defaultValue: '#6366f1', widget: 'color_picker' },
                        textColor: { label: 'Text Color', defaultValue: '#ffffff', widget: 'color_picker' },
                        borderRadius: { label: 'Border Radius', defaultValue: 12, widget: 'number' },
                        paddingY: { label: 'Padding Y', defaultValue: 12, widget: 'number' },
                        paddingX: { label: 'Padding X', defaultValue: 16, widget: 'number' },
                    },
                },
            },
            values: {},
            renderer: {
                Viewer: window.unlayer.createViewer({
                    render(values) {
                        const t = values && values.text ? String(values.text) : 'Subscribe';
                        return `<div style='padding:10px;border:1px dashed #cbd5e1;border-radius:10px;font-size:13px;'>Submit Button: ${escHtml(t)}</div>`;
                    },
                }),
                exporters: {
                    web: exportSubmit,
                    email: exportSubmit,
                    popup: exportSubmit,
                },
                head: { css() {}, js() {} },
            },
        });

        window.__mailpurseUnlayerToolsRegistered = true;
    } catch (e) {
    }
}

function ensureMailpurseUnlayerToolsRegistered(retriesLeft = 80) {
    try {
        if (window.__mailpurseUnlayerToolsRegistered) {
            return;
        }
        if (!window.unlayer || typeof window.unlayer.registerTool !== 'function' || typeof window.unlayer.createViewer !== 'function') {
            if (retriesLeft <= 0) {
                return;
            }
            setTimeout(() => ensureMailpurseUnlayerToolsRegistered(retriesLeft - 1), 75);
            return;
        }
        registerMailpurseUnlayerTools();
    } catch (e) {
        // ignore
    }
}

function waitForMailpurseUnlayerToolsRegistered(timeoutMs = 6000) {
    const started = Date.now();

    return new Promise((resolve) => {
        const tick = () => {
            try {
                ensureMailpurseUnlayerToolsRegistered();
            } catch (e) {
                // ignore
            }

            if (window.__mailpurseUnlayerToolsRegistered) {
                resolve(true);
                return;
            }

            if (Date.now() - started >= timeoutMs) {
                resolve(false);
                return;
            }

            setTimeout(tick, 50);
        };

        tick();
    });
}

function readJsonScriptTag(scriptId) {
    if (!scriptId) {
        return null;
    }
    const el = document.getElementById(scriptId);
    if (!el) {
        return null;
    }
    const raw = (el.textContent || '').trim();
    if (!raw) {
        return null;
    }
    try {
        return JSON.parse(raw);
    } catch (e) {
        return null;
    }
}

function getMailpurseSupportedGoogleFontFamilies() {
    try {
        const raw = window.__mailpurseSupportedGoogleFontFamilies;
        if (!Array.isArray(raw)) {
            return [];
        }
        return raw
            .map((v) => (typeof v === 'string' ? v.trim() : ''))
            .filter((v) => v);
    } catch (e) {
        return [];
    }
}

function buildMailpurseUnlayerFontsConfig() {
    const families = getMailpurseSupportedGoogleFontFamilies();
    if (!families.length) {
        return null;
    }

    const buildGoogleFontUrl = (family) => {
        const encoded = encodeURIComponent(String(family || '').replace(/'/g, "\\'"));
        return `https://fonts.googleapis.com/css2?family=${encoded}:wght@400;500;600;700&display=swap`;
    };

    const makeWeights = () => ([
        { label: 'Regular', value: 400 },
        { label: 'Medium', value: 500 },
        { label: 'Semi Bold', value: 600 },
        { label: 'Bold', value: 700 },
    ]);

    const customFonts = families.map((family) => {
        const safeFamily = String(family).replace(/'/g, "\\'");
        return {
            label: String(family),
            value: `'${safeFamily}', sans-serif`,
            url: buildGoogleFontUrl(family),
            weights: makeWeights(),
        };
    });

    return {
        showDefaultFonts: true,
        customFonts,
    };
}

function setupUnlayerEditors() {
    const editorEls = document.querySelectorAll('[data-unlayer-editor]');
    if (!editorEls.length) {
        return;
    }

    const showUnlayerMountError = (el, message) => {
        if (!el) {
            return;
        }
        const safeMessage = message && String(message).trim()
            ? String(message).trim()
            : 'Failed to load the email builder.';
        const escapedMessage = safeMessage
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        el.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;height:100%;min-height:320px;padding:16px;"><div data-mailpurse-unlayer-mount-error style="max-width:560px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:10px;padding:14px 16px;font:500 14px/1.4 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;">${escapedMessage}</div></div>`;
    };

    ensureUnlayerScriptLoaded()
        .then(() => {
            editorEls.forEach((el) => {
                const id = el.getAttribute('id') || 'editor-container';
                const initKey = '__mailpurseUnlayerInitDone:' + id;

                if (window[initKey]) {
                    return;
                }

                const waitForVisibleAndInit = async () => {
                    const started = Date.now();
                    const tick = async () => {
                        try {
                            if (window[initKey]) {
                                return;
                            }

                            if (!el.isConnected) {
                                return;
                            }

                            // Turbo can fire turbo:load before layout settles; avoid init on 0-height containers.
                            // Note: position:fixed elements always have offsetParent===null — use offsetHeight only.
                            const isFixed = el.style.position === 'fixed' ||
                                (typeof getComputedStyle !== 'undefined' && getComputedStyle(el).position === 'fixed');
                            const isVisible = isFixed ? el.offsetHeight > 0 : !(el.offsetParent === null || el.offsetHeight === 0);
                            if (!isVisible) {
                                setTimeout(tick, 120);
                                return;
                            }

                            try {
                                const staleError = el.querySelector('[data-mailpurse-unlayer-mount-error]');
                                if (staleError) {
                                    el.innerHTML = '';
                                }
                            } catch (_) {
                            }

                            const projectIdRaw = el.getAttribute('data-unlayer-project-id');
                            const projectId = projectIdRaw ? Number(projectIdRaw) : null;
                            const displayMode = el.getAttribute('data-unlayer-display-mode') || 'email';

                            const options = {
                                id,
                                displayMode,
                                fonts: undefined,
                                editor: {
                                    autoSelectOnDrop: true,
                                },
                            };

                            const fontsCfg = buildMailpurseUnlayerFontsConfig();
                            if (fontsCfg) {
                                options.fonts = fontsCfg;
                            } else {
                                delete options.fonts;
                            }

                            if (projectId) {
                                options.projectId = projectId;
                            }

                            window.__mailpurseUnlayerReady = false;
                            let readyHandled = false;

                            const onReady = () => {
                                if (readyHandled) {
                                    return;
                                }
                                readyHandled = true;
                                window.__mailpurseUnlayerReady = true;

                                try {
                                    const loadingEl = el.querySelector('[data-unlayer-loading]');
                                    if (loadingEl) loadingEl.remove();
                                } catch (e) {}

                                const designScriptId = el.getAttribute('data-unlayer-design-script-id');
                                if (designScriptId) {
                                    const designEl = document.getElementById(designScriptId);
                                    const raw = designEl ? (designEl.textContent || '') : '';
                                    if (raw && raw.trim()) {
                                        try {
                                            const design = JSON.parse(raw);
                                            if (design && typeof design === 'object') {
                                                window.unlayer.loadDesign(design);
                                            }
                                        } catch (e) {
                                        }
                                    }
                                }

                                if (window.__mailpurseUnlayerPendingDesign && typeof window.__mailpurseUnlayerPendingDesign === 'object') {
                                    try {
                                        window.unlayer.loadDesign(window.__mailpurseUnlayerPendingDesign);
                                    } catch (e) {
                                    }
                                    window.__mailpurseUnlayerPendingDesign = null;
                                }
                            };

                            ensureMailpurseUnlayerToolsRegistered();

                            try {
                                window.unlayer.addEventListener('editor:ready', onReady);
                            } catch (e) {
                            }

                            window.unlayer.init(options);

                            try {
                                window.unlayer.addEventListener('editor:ready', onReady);
                            } catch (e) {
                            }

                            window[initKey] = true;
                        } catch (e) {
                            const elapsed = Date.now() - started;
                            if (elapsed < 20000) {
                                setTimeout(tick, 200);
                                return;
                            }
                            const message = (e && e.message) ? e.message : 'Unknown initialization error.';
                            showUnlayerMountError(el, `Failed to load builder: ${message}`);
                            try {
                                console.error('[MailPurse Unlayer] init failed', e);
                            } catch (_) {
                            }
                        }
                    };
                    tick();
                };

                waitForVisibleAndInit();
            });
        })
        .catch((e) => {
            const message = (e && e.message) ? e.message : 'Failed to load Unlayer embed.js.';
            editorEls.forEach((el) => {
                showUnlayerMountError(el, message);
            });
            try {
                console.error('[MailPurse Unlayer] embed script load failed', e);
            } catch (_) {
            }
        });
}

try {
    window.__mailpurseSetupUnlayerEditors = setupUnlayerEditors;
} catch (e) {
}

function setupUnlayerMessageBridge() {
    if (window.__mailpurseUnlayerMessageBridgeBound) {
        return;
    }
    window.__mailpurseUnlayerMessageBridgeBound = true;

    if (!Array.isArray(window.__mailpurseUnlayerToolsDebug)) {
        window.__mailpurseUnlayerToolsDebug = [];
    }

    window.addEventListener('message', (event) => {
        try {
            const data = event && event.data ? event.data : null;
            if (!data || typeof data.type !== 'string') {
                return;
            }

            if (data.type.startsWith('mailpurse:unlayer:')) {
                try {
                    window.__mailpurseUnlayerToolsDebug.push({
                        type: data.type,
                        payload: data.payload ?? null,
                        at: Date.now(),
                    });
                } catch (e) {
                    // ignore
                }
            }

            if (data.type === 'mailpurse:unlayer:tools-registered') {
                window.__mailpurseUnlayerToolsRegistered = true;
            }
        } catch (e) {
            // ignore
        }
    });
}

function setupUnlayerSaveBindings() {
    const form = document.getElementById('unlayer-form');
    const saveButton = document.getElementById('btn-save');

    if (!form || !saveButton) {
        return;
    }

    if (saveButton.dataset.unlayerEmbeddedHandled === '1' || saveButton.hasAttribute('data-unlayer-embedded-handled')) {
        return;
    }

    if (saveButton.dataset.unlayerBound === '1') {
        return;
    }
    saveButton.dataset.unlayerBound = '1';

    saveButton.addEventListener('click', () => {
        if (!window.unlayer || typeof window.unlayer.exportHtml !== 'function') {
            encodeHtmlContentForSubmit(form);
            form.submit();
            return;
        }

        if (window.__mailpurseUnlayerReady !== true) {
            encodeHtmlContentForSubmit(form);
            form.submit();
            return;
        }

        let submitted = false;
        const submitOnce = () => {
            if (submitted) {
                return;
            }
            submitted = true;
            encodeHtmlContentForSubmit(form);
            form.submit();
        };

        const exportFallbackTimer = setTimeout(() => {
            submitOnce();
        }, 8000);

        window.unlayer.exportHtml((data) => {
            const htmlInput = document.getElementById('html_content');
            const plainInput = document.getElementById('plain_text_content');
            const dataInput = document.getElementById('grapesjs_data');

            if (htmlInput) {
                htmlInput.value = (data && data.html) ? data.html : '';
            }

            if (dataInput) {
                dataInput.value = JSON.stringify((data && data.design) ? data.design : null);
            }

            const plainText = String((data && data.html) ? data.html : '')
                .replace(/<style[\s\S]*?<\/style>/gi, ' ')
                .replace(/<script[\s\S]*?<\/script>/gi, ' ')
                .replace(/<[^>]+>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            if (plainInput) {
                plainInput.value = plainText;
            }

            clearTimeout(exportFallbackTimer);
            submitOnce();
        });
    });
}

function teardownUnlayerEditorsBeforeCache() {
    const editorEls = document.querySelectorAll('[data-unlayer-editor]');
    editorEls.forEach((el) => {
        const id = el.getAttribute('id') || 'editor-container';
        const initKey = '__mailpurseUnlayerInitDone:' + id;
        try {
            delete window[initKey];
        } catch (e) {
        }
        el.innerHTML = '';
    });

    const saveButton = document.getElementById('btn-save');
    if (saveButton && saveButton.dataset) {
        delete saveButton.dataset.unlayerBound;
    }

    window.__mailpurseUnlayerReady = false;
}

function getToastTheme(type) {
    const t = String(type || '').toLowerCase();
    if (t === 'success') {
        return {
            iconBg: 'bg-white text-emerald-500',
            message: 'text-white',
            close: 'text-white/80 hover:text-white hover:bg-white/10',
            panel: 'bg-emerald-500 text-white',
            icon: '<svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.414l-7.13 7.13a1 1 0 01-1.414 0L4.29 9.963a1 1 0 111.414-1.414l3.163 3.163 6.423-6.422a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>',
        };
    }
    if (t === 'warning') {
        return {
            iconBg: 'bg-white text-amber-500',
            message: 'text-white',
            close: 'text-white/80 hover:text-white hover:bg-white/10',
            panel: 'bg-amber-500 text-white',
            icon: '<svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.721-1.36 3.486 0l6.518 11.59c.75 1.334-.214 3.011-1.742 3.011H3.48c-1.528 0-2.492-1.677-1.742-3.011l6.519-11.59zM11 14a1 1 0 10-2 0 1 1 0 002 0zm-1-2a.75.75 0 01-.75-.75V7a.75.75 0 011.5 0v4.25A.75.75 0 0110 12z" clip-rule="evenodd"/></svg>',
        };
    }
    if (t === 'error' || t === 'danger') {
        return {
            iconBg: 'bg-white text-red-500',
            message: 'text-white',
            close: 'text-white/80 hover:text-white hover:bg-white/10',
            panel: 'bg-red-500 text-white',
            icon: '<svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm2.53-10.53a.75.75 0 00-1.06-1.06L10 8.94 8.53 7.47a.75.75 0 10-1.06 1.06L8.94 10l-1.47 1.47a.75.75 0 101.06 1.06L10 11.06l1.47 1.47a.75.75 0 001.06-1.06L11.06 10l1.47-1.47z" clip-rule="evenodd"/></svg>',
        };
    }
    return {
        iconBg: 'bg-white text-sky-500',
        message: 'text-white',
        close: 'text-white/80 hover:text-white hover:bg-white/10',
        panel: 'bg-sky-500 text-white',
        icon: '<svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zM10 8a1 1 0 100-2 1 1 0 000 2zm-1 2a1 1 0 012 0v4a1 1 0 11-2 0v-4z" clip-rule="evenodd"/></svg>',
    };
}

function createToastElement(toast, onClose) {
    const t = toast && typeof toast === 'object' ? toast : {};
    const theme = getToastTheme(t.type);
    const messageText = typeof t.message === 'string' ? t.message : '';

    const wrapper = document.createElement('div');
    wrapper.className = 'relative w-full rounded-2xl shadow-lg';

    const panel = document.createElement('div');
    panel.className = `backdrop-blur ${theme.panel} rounded-lg px-3 py-2 transition duration-300 ease-out opacity-0 translate-y-4`;

    const row = document.createElement('div');
    row.className = 'flex items-center gap-3';

    const iconWrap = document.createElement('div');
    iconWrap.className = `shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${theme.iconBg}`;
    iconWrap.innerHTML = theme.icon;

    const content = document.createElement('div');
    content.className = 'flex-1 min-w-0';

    if (messageText) {
        const msg = document.createElement('div');
        msg.className = `text-sm sm:text-base leading-6 font-medium whitespace-pre-line ${theme.message}`;
        msg.textContent = messageText;
        content.appendChild(msg);
    }

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = `shrink-0 -mr-1 p-2 rounded-lg transition-colors ${theme.close}`;
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';

    row.appendChild(iconWrap);
    row.appendChild(content);
    row.appendChild(closeBtn);
    panel.appendChild(row);
    wrapper.appendChild(panel);

    const animateIn = () => {
        requestAnimationFrame(() => {
            panel.classList.remove('opacity-0', 'translate-y-4');
            panel.classList.add('opacity-100', 'translate-y-0');
        });
    };

    const close = () => {
        panel.classList.remove('opacity-100', 'translate-y-0');
        panel.classList.add('opacity-0', 'translate-y-4');
        window.setTimeout(() => {
            wrapper.remove();
            if (typeof onClose === 'function') {
                onClose();
            }
        }, 220);
    };

    closeBtn.addEventListener('click', close);
    animateIn();

    return { el: wrapper, close };
}

function showToast(toast, opts = {}) {
    const root = document.querySelector('[data-toast-root]');
    if (!root) {
        return;
    }

    const durationMs = Number(opts.durationMs ?? toast?.durationMs) || 4500;

    const created = createToastElement(toast, null);
    root.appendChild(created.el);

    if (durationMs > 0) {
        window.setTimeout(() => created.close(), durationMs);
    }
}

function setupToasts() {
    const root = document.querySelector('[data-toast-root]');
    if (!root) {
        return;
    }

    if (typeof window.mailpurseToast !== 'function') {
        window.mailpurseToast = (toast, opts = {}) => showToast(toast, opts);
    }

    const toasts = window.__mailpursePageToasts;
    if (Array.isArray(toasts) && toasts.length) {
        toasts.forEach((t) => showToast(t));
    }

    try {
        window.__mailpursePageToasts = [];
    } catch (e) {
    }
}

const initAdminColorPickers = (root = document) => {
    root.querySelectorAll('.admin-color-picker').forEach((input) => {
        if (input.dataset.adminColorPickerInitialized === 'true') return;

        const wrapper = document.createElement('span');
        wrapper.className = 'admin-color-picker-wrap';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const swatch = document.createElement('button');
        swatch.type = 'button';
        swatch.className = 'admin-color-picker-swatch';
        swatch.setAttribute('aria-label', input.getAttribute('aria-label') || 'Choose color');
        swatch.setAttribute('aria-expanded', 'false');
        wrapper.appendChild(swatch);

        swatch.addEventListener('click', () => {
            input.click();
        });
        const sync = () => {
            wrapper.style.setProperty('--admin-color-picker-value', input.value);
        };
        wrapper.style.setProperty('--admin-color-picker-value', input.value);
        input.addEventListener('input', sync);
        input.addEventListener('change', sync);
        swatch.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                input.click();
            }
        });
        input.dataset.adminColorPickerInitialized = 'true';
    });
};

const runAppBoot = () => {
    setupNotificationPolling();
    setupToasts();
    setupCustomConfirmModal();
    setupUnlayerMessageBridge();
    setupUnlayerEditors();
    setupUnlayerSaveBindings();
    initFaqsCarousels();
    initFaqsAccordions();
    initSliders();
    initCarousels();
    initMobileMenus();
    setupSliderAutoReinit();
    initAdminColorPickers();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runAppBoot);
} else {
    runAppBoot();
}

document.addEventListener('turbo:before-cache', () => {
    saveSidebarScrollPosition();

    if (window.Alpine && typeof window.Alpine.destroyTree === 'function') {
        window.Alpine.destroyTree(document.body);
    }

    const toastRoot = document.querySelector('[data-toast-root]');
    if (toastRoot) {
        toastRoot.innerHTML = '';
    }

    teardownUnlayerEditorsBeforeCache();
});

document.addEventListener('turbo:load', () => {
    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        if (!document.documentElement.__x) {
            window.Alpine.initTree(document.documentElement);
        }
        window.Alpine.initTree(document.body);
    }
    runAppBoot();
});

