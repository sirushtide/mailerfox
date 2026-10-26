// Converts pasted/imported HTML into MailPurse builder sections and widgets.
// It analyzes common email/marketing HTML structures (tables, rows, cells,
// flex/grid rows) and maps headings, text, images, buttons, dividers, spacers,
// nav links, social links and copyright into the registered element props.

const UNSAFE_SELECTOR = 'script,style,iframe,object,embed,link,meta,base,noscript,form,input,button,textarea,select,svg,canvas,video,audio';

const BLOCK_TAGS = new Set([
    'DIV', 'SECTION', 'ARTICLE', 'HEADER', 'FOOTER', 'MAIN', 'ASIDE', 'NAV',
    'TABLE', 'TBODY', 'THEAD', 'TFOOT', 'TR', 'TD', 'TH', 'CENTER',
    'UL', 'OL', 'LI', 'P', 'BLOCKQUOTE', 'PRE', 'FIGURE', 'FIGCAPTION', 'DL', 'DT', 'DD',
    'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'IMG', 'HR',
]);

const SOCIAL_HOSTS = {
    'twitter.com': 'twitter',
    'x.com': 'twitter',
    'facebook.com': 'facebook',
    'fb.com': 'facebook',
    'instagram.com': 'instagram',
    'linkedin.com': 'linkedin',
    'youtube.com': 'youtube',
    'youtu.be': 'youtube',
    'tiktok.com': 'tiktok',
    'pinterest.com': 'pinterest',
    'github.com': 'github',
};

const SOCIAL_SVG = {
    twitter: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24h-6.657l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
    facebook: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.52 1.49-3.91 3.78-3.91 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.78-1.63 1.57v1.89h2.78l-.44 2.91h-2.34V22c4.78-.76 8.43-4.92 8.43-9.94z"/></svg>',
    instagram: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zm0 3.68A6.16 6.16 0 1 0 12 18.16 6.16 6.16 0 0 0 12 5.84zm0 10.16A4 4 0 1 1 12 8a4 4 0 0 1 0 8zm6.4-10.4a1.44 1.44 0 1 1-2.88 0 1.44 1.44 0 0 1 2.88 0z"/></svg>',
    linkedin: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5A2.5 2.5 0 1 1 0 3.5a2.5 2.5 0 0 1 4.98 0zM.5 8h4V24h-4V8zm7.5 0h3.83v2.19h.05c.53-1 1.84-2.06 3.79-2.06 4.05 0 4.8 2.67 4.8 6.14V24h-4v-7.1c0-1.7-.03-3.88-2.36-3.88-2.37 0-2.73 1.85-2.73 3.76V24H8V8z"/></svg>',
    youtube: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.12-2.12C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.53A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.12 2.12c1.88.53 9.38.53 9.38.53s7.5 0 9.38-.53a3 3 0 0 0 2.12-2.12A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8zM9.6 15.6V8.4l6.2 3.6z"/></svg>',
};

let idCounter = 0;
function nid(prefix) {
    idCounter += 1;
    return `${prefix}_imp${idCounter}_${Math.random().toString(36).slice(2, 7)}`;
}

function cleanDoc(rawHtml) {
    const doc = new DOMParser().parseFromString(String(rawHtml || ''), 'text/html');
    doc.querySelectorAll(UNSAFE_SELECTOR).forEach((node) => node.remove());
    doc.querySelectorAll('*').forEach((node) => {
        Array.from(node.attributes).forEach((attr) => {
            const name = attr.name.toLowerCase();
            if (name.startsWith('on')) {
                node.removeAttribute(attr.name);
            } else if ((name === 'href' || name === 'src') && /^\s*javascript:/i.test(attr.value)) {
                node.setAttribute(attr.name, '#');
            }
        });
    });
    return doc;
}

function sanitizeInlineHtml(html) {
    const doc = new DOMParser().parseFromString(String(html || ''), 'text/html');
    doc.querySelectorAll(UNSAFE_SELECTOR).forEach((node) => node.remove());
    doc.querySelectorAll('*').forEach((node) => {
        Array.from(node.attributes).forEach((attr) => {
            const name = attr.name.toLowerCase();
            if (name.startsWith('on') || name.startsWith('data-') || name === 'style' || name === 'class' || name === 'id') {
                node.removeAttribute(attr.name);
            } else if ((name === 'href' || name === 'src') && /^\s*javascript:/i.test(attr.value)) {
                node.setAttribute(attr.name, '#');
            }
        });
    });
    return (doc.body ? doc.body.innerHTML : '').trim();
}

function textOf(el) {
    return String(el?.textContent || '').replace(/\s+/g, ' ').trim();
}

function attrsOf(el) {
    const out = {};
    if (!el || !el.attributes) return out;
    Array.from(el.attributes).forEach((attr) => {
        out[attr.name.toLowerCase()] = String(attr.value || '');
    });
    return out;
}

function styleMap(el) {
    const out = {};
    const style = el && el.getAttribute ? String(el.getAttribute('style') || '') : '';
    style.split(';').forEach((part) => {
        const idx = part.indexOf(':');
        if (idx < 0) return;
        const key = part.slice(0, idx).trim().toLowerCase();
        const value = part.slice(idx + 1).trim();
        if (key) out[key] = value;
    });
    return out;
}

function isBlockish(el) {
    if (!el || el.nodeType !== 1) return false;
    return BLOCK_TAGS.has(el.tagName);
}

function isTextish(el) {
    if (!el || el.nodeType !== 1) return false;
    if (/^(H[1-6]|P|UL|OL|BLOCKQUOTE|PRE|IMG|HR|TABLE|SECTION|ARTICLE|HEADER|FOOTER|NAV|DIV)$/.test(el.tagName)) {
        return false;
    }
    return true;
}

function hasMeaningfulContent(el) {
    if (!el) return false;
    if (textOf(el) !== '') return true;
    return !!el.querySelector('img,hr,a');
}

function splitCells(row) {
    const cells = Array.from(row.children).filter((c) => c.tagName === 'TD' || c.tagName === 'TH');
    return cells.length >= 2 ? cells : null;
}

function flexRowChildren(el) {
    const sm = styleMap(el);
    const display = String(sm.display || '').toLowerCase();
    const isCol = /(flex|grid)/.test(display) || /(flex|grid)/.test(String(attrsOf(el).class || '').toLowerCase());
    if (!isCol) return null;
    const kids = Array.from(el.children).filter(isBlockish);
    return kids.length >= 2 ? kids : null;
}

function columnChildren(el) {
    if (el.tagName === 'TR') {
        const cells = splitCells(el);
        if (cells) return cells;
    }
    return flexRowChildren(el);
}

function headingLevel(tag) {
    const m = /^H([1-6])$/.exec(String(tag || '').toUpperCase());
    return m ? Number(m[1]) : 2;
}

function imageWidget(img, linkUrl) {
    const attrs = attrsOf(img);
    const widget = { id: nid('w'), type: 'image', props: {} };
    const src = attrs.src || attrs['data-src'] || '';
    if (src) widget.props.url = src;
    if (attrs.alt) widget.props.alt = attrs.alt;
    const width = parseInt(attrs.width || '', 10);
    if (Number.isFinite(width) && width > 0) widget.props.width = width;
    const sm = styleMap(img);
    const maxWidth = sm['max-width'] || sm.width || '';
    const mw = parseInt(maxWidth, 10);
    if (!widget.props.width && Number.isFinite(mw) && mw > 0) widget.props.width = mw;
    if (linkUrl) widget.props.linkUrl = linkUrl;
    return widget;
}

function looksLikeButton(a) {
    if (!a || a.tagName !== 'A') return false;
    const attrs = attrsOf(a);
    const cls = String(attrs.class || '').toLowerCase();
    const role = String(attrs.role || '').toLowerCase();
    const sm = styleMap(a);
    if (role === 'button') return true;
    if (/(^|[^a-z])(btn|button|cta)([^a-z]|$)/.test(cls)) return true;
    if (sm['background'] || sm['background-color'] || sm['border-radius']) return true;
    return false;
}

function buttonWidget(a) {
    const attrs = attrsOf(a);
    const widget = { id: nid('w'), type: 'button', props: { text: textOf(a) || 'Click here', url: attrs.href || '#', style: 'primary', size: 'md' } };
    if (attrs.target) widget.props.target = attrs.target;
    return widget;
}

function headingWidget(el) {
    return { id: nid('w'), type: 'heading', props: { text: textOf(el), level: headingLevel(el.tagName) } };
}

function richTextWidget(el, htmlOverride) {
    const inner = htmlOverride !== undefined ? htmlOverride : sanitizeInlineHtml(el.innerHTML);
    if (!inner) return null;
    return { id: nid('w'), type: 'rich_text', props: { html: inner } };
}

function dividerWidget() {
    return { id: nid('w'), type: 'divider', props: { backgroundColor: '#e5e7eb', height: '1px', borderTop: true, borderColor: '#e5e7eb' } };
}

function platformFromHref(href) {
    try {
        const host = new URL(href, 'https://example.com').hostname.replace(/^www\./, '').toLowerCase();
        for (const key of Object.keys(SOCIAL_HOSTS)) {
            if (host === key || host.endsWith('.' + key)) return SOCIAL_HOSTS[key];
        }
    } catch (e) {
        // ignore
    }
    return null;
}

function collectLinks(el) {
    return Array.from(el.querySelectorAll('a')).filter((a) => textOf(a) !== '' || a.getAttribute('href'));
}

function socialIconsWidget(links) {
    const icons = links
        .map((a) => {
            const href = a.getAttribute('href') || '';
            const platform = platformFromHref(href);
            if (!platform) return null;
            return { svg: SOCIAL_SVG[platform] || '', url: href, target: '_blank' };
        })
        .filter(Boolean);
    if (!icons.length) return null;
    return { id: nid('w'), type: 'social_icons', props: { size: '20', gap: '16', align: 'center', icons } };
}

function navMenuWidget(el) {
    const links = collectLinks(el);
    const items = links
        .map((a) => ({ label: textOf(a), url: a.getAttribute('href') || '#' }))
        .filter((item) => item.label !== '');
    if (!items.length) return null;
    return { id: nid('w'), type: 'nav_menu', props: { items, align: 'center', spacing: 24 } };
}

function isSocialContainer(el) {
    if (!/(footer|social|follow|connect)/i.test(String(attrsOf(el).class || ''))) return false;
    const links = collectLinks(el);
    if (!links.length) return false;
    const socialCount = links.filter((a) => platformFromHref(a.getAttribute('href') || '')).length;
    return socialCount >= Math.max(2, Math.ceil(links.length * 0.6));
}

function isNavContainer(el) {
    if (el.tagName === 'NAV') return true;
    const links = Array.from(el.children).filter((c) => c.tagName === 'A');
    return el.children.length > 0 && links.length === el.children.length && links.length >= 2 && !isSocialContainer(el);
}

const COPYRIGHT_RE = /©|&copy;|copyright|all rights reserved|unsubscribe|view in browser|privacy policy|terms/i;

function collectWidgets(container, out) {
    const children = Array.from(container.childNodes);
    let textBuffer = '';

    const flushText = () => {
        const html = textBuffer.trim();
        textBuffer = '';
        if (html) {
            out.push({ id: nid('w'), type: 'rich_text', props: { html: `<p>${sanitizeInlineHtml(html)}</p>` } });
        }
    };

    for (const node of children) {
        if (node.nodeType === 3) {
            textBuffer += node.textContent;
            continue;
        }
        if (node.nodeType !== 1) continue;

        const tag = node.tagName;

        if (/^H[1-6]$/.test(tag)) {
            flushText();
            if (textOf(node)) out.push(headingWidget(node));
            continue;
        }

        if (tag === 'IMG') {
            flushText();
            out.push(imageWidget(node));
            continue;
        }

        if (tag === 'A') {
            const imgChild = node.querySelector(':scope > img');
            if (imgChild && textOf(node) === '') {
                flushText();
                out.push(imageWidget(imgChild, node.getAttribute('href') || ''));
            } else if (looksLikeButton(node)) {
                flushText();
                out.push(buttonWidget(node));
            } else {
                textBuffer += node.outerHTML;
            }
            continue;
        }

        if (tag === 'HR') {
            flushText();
            out.push(dividerWidget());
            continue;
        }

        if (tag === 'BR') {
            flushText();
            out.push({ id: nid('w'), type: 'spacer', props: { height: '16px' } });
            continue;
        }

        if (tag === 'TABLE') {
            const col = columnChildren(node.tagName === 'TR' ? node : (node.querySelector('tr') || node));
            if (col) {
                // A nested column table inside a single-column flow — flatten cells.
                flushText();
                col.forEach((cell) => collectWidgets(cell, out));
                continue;
            }
        }

        if (isSocialContainer(node)) {
            flushText();
            const widget = socialIconsWidget(collectLinks(node));
            if (widget) out.push(widget);
            continue;
        }

        if (isNavContainer(node)) {
            flushText();
            const widget = navMenuWidget(node);
            if (widget) out.push(widget);
            continue;
        }

        if (tag === 'P' || tag === 'BLOCKQUOTE' || tag === 'PRE' || tag === 'FIGCAPTION' || tag === 'LI' || tag === 'DT' || tag === 'DD') {
            flushText();
            const imgs = Array.from(node.querySelectorAll('img'));
            if (imgs.length === 1 && textOf(node) === '' && node.children.length === 1) {
                out.push(imageWidget(imgs[0], ''));
                continue;
            }
            const widget = richTextWidget(node);
            if (widget) out.push(widget);
            continue;
        }

        if (tag === 'UL' || tag === 'OL') {
            flushText();
            const widget = richTextWidget(node);
            if (widget) out.push(widget);
            continue;
        }

        // Copyright / footer text.
        if (tag === 'FOOTER' || COPYRIGHT_RE.test(textOf(node)) && !node.querySelector('h1,h2,h3,img,table')) {
            if (COPYRIGHT_RE.test(textOf(node))) {
                flushText();
                out.push({ id: nid('w'), type: 'copyright', props: { text: textOf(node), align: 'center' } });
                continue;
            }
        }

        if (isBlockish(node) || isTextish(node)) {
            const hasWidgetChildren = Array.from(node.children).some(
                (c) => isBlockish(c) || (c.tagName === 'A' && looksLikeButton(c)),
            );
            if (hasWidgetChildren) {
                flushText();
                collectWidgets(node, out);
                continue;
            }
            // Inline/text content — keep as rich text.
            flushText();
            const widget = richTextWidget(node);
            if (widget) out.push(widget);
            continue;
        }

        // Fallback: descend.
        collectWidgets(node, out);
    }

    flushText();
}

function buildSection(node, mode) {
    const colChildren = columnChildren(node);
    let columnWidgets;

    if (colChildren && colChildren.length >= 2) {
        columnWidgets = colChildren.map((cell) => {
            const widgets = [];
            collectWidgets(cell, widgets);
            return widgets;
        });
    } else {
        const widgets = [];
        collectWidgets(node, widgets);
        columnWidgets = [widgets];
    }

    const filtered = columnWidgets.filter((widgets) => widgets.length > 0);
    if (!filtered.length) return null;

    const span = Math.max(1, Math.floor(12 / filtered.length));
    const columns = filtered.map((widgets) => ({
        id: nid('col'),
        spans: { base: 12, md: 12, lg: filtered.length === 1 ? 12 : span },
        widgets,
    }));

    return {
        id: nid('sec'),
        name: 'Imported section',
        style: {
            backgroundType: 'color',
            backgroundColor: '#ffffff',
            paddingY: 32,
            gap: 20,
            gapX: 20,
            gapY: 20,
            gapLinked: true,
            itemsDirection: 'row',
            itemsJustify: 'flex-start',
            itemsAlign: 'stretch',
            maxWidth: mode === 'email' ? '600px' : '6xl',
        },
        columns,
    };
}

const SECTION_LEVEL_TAGS = new Set([
    'DIV', 'SECTION', 'ARTICLE', 'HEADER', 'FOOTER', 'MAIN', 'ASIDE', 'CENTER', 'TABLE',
]);

function sectionCandidates(root) {
    // Email HTML is usually nested in a single layout table; descend single-child
    // wrappers first so we analyze the real content rows.
    let container = root;
    for (let depth = 0; depth < 12; depth += 1) {
        const elementChildren = Array.from(container.children).filter(isBlockish);
        if (elementChildren.length === 1 && /^(DIV|CENTER|TABLE|TBODY|TR|TD|TH|BODY|HTML)$/.test(elementChildren[0].tagName)) {
            container = elementChildren[0];
            continue;
        }
        break;
    }

    const rows = Array.from(container.querySelectorAll(':scope > tbody > tr, :scope > tr'));
    if (rows.length >= 2) {
        const meaningful = rows.filter(hasMeaningfulContent);
        if (meaningful.length >= 2) return meaningful;
    }

    const children = Array.from(container.children).filter(isBlockish).filter(hasMeaningfulContent);
    const sectionLevel = children.filter((c) => SECTION_LEVEL_TAGS.has(c.tagName));
    if (sectionLevel.length) return sectionLevel;

    return hasMeaningfulContent(container) ? [container] : [];
}

export function htmlToSections(rawHtml, { mode = 'page' } = {}) {
    if (typeof DOMParser === 'undefined') return [];
    const doc = cleanDoc(rawHtml);
    const body = doc.body;
    if (!body) return [];

    const candidates = sectionCandidates(body);
    const source = candidates.length ? candidates : [body];

    const sections = [];
    source.forEach((node) => {
        // If this candidate is itself a column row, build one section from it.
        const section = buildSection(node, mode);
        if (section) sections.push(section);
    });

    return sections;
}
