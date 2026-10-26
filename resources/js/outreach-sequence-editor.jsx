import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { FontBackgroundColorPlugin, FontColorPlugin, TextAlignPlugin } from '@platejs/basic-styles/react';
import { BasicBlocksPlugin, BoldPlugin, ItalicPlugin, UnderlinePlugin } from '@platejs/basic-nodes/react';
import { EmojiInputPlugin, EmojiPlugin } from '@platejs/emoji/react';
import { LinkPlugin } from '@platejs/link/react';
import { upsertLink } from '@platejs/link';
import { ListPlugin } from '@platejs/list/react';
import { ListStyleType, toggleList } from '@platejs/list';
import { HtmlPlugin } from 'platejs';
import { createPlatePlugin, Plate, PlateContent, useEditorRef, useMarkToolbarButton, useMarkToolbarButtonState, usePlateEditor } from 'platejs/react';
import { AlignCenter, AlignJustify, AlignLeft, AlignRight, Bold, Italic, Image as ImageIcon, Link, List, ListOrdered, PaintBucket, Palette, Smile, Underline } from 'lucide-react';

function LinkElement({ attributes, children, element }) {
    const target = element?.target;
    return (
        <a
            {...attributes}
            href={element?.url || '#'}
            target={target || undefined}
            rel={target === '_blank' ? 'noopener noreferrer' : undefined}
            style={{ textDecoration: 'underline', color: '#1E5FEA' }}
        >
            {children}
        </a>
    );
}

function ImageElement({ attributes, children, element }) {
    const editor = useEditorRef();
    const imageRef = useRef(null);
    const url = element?.url || '';
    const alt = element?.alt || '';
    const width = element?.width;
    const align = element?.align || 'center';
    const linkUrl = String(element?.linkUrl || '').trim();
    const target = String(element?.target || '').trim();
    const isInline = align === 'inline';
    const isResizingRef = useRef(false);

    const justify = align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center';
    const clampWidth = (value) => Math.max(40, Math.min(1200, Math.round(value)));

    const updateWidthAtPath = (nextWidth) => {
        if (!Number.isFinite(nextWidth) || nextWidth <= 0) {
            return;
        }

        try {
            const path = editor.api.findPath(element);
            editor.tf.setNodes({ width: clampWidth(nextWidth) }, { at: path });
        } catch (error) {
            // Ignore resize failures if the node moves/disconnects mid-drag.
        }
    };

    const startResize = (event) => {
        if (event.button !== 0) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const startX = event.clientX;
        const startWidth = Number.isFinite(width) && width > 0
            ? width
            : Math.max(40, Math.round(imageRef.current?.getBoundingClientRect()?.width || 240));

        isResizingRef.current = true;

        const handleMouseMove = (moveEvent) => {
            if (!isResizingRef.current) {
                return;
            }

            updateWidthAtPath(startWidth + (moveEvent.clientX - startX));
        };

        const handleMouseUp = () => {
            isResizingRef.current = false;
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };

        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
    };

    const image = url ? (
        <img
            ref={imageRef}
            src={url}
            alt={alt}
            style={{
                maxWidth: '100%',
                height: 'auto',
                width: width ? `${width}px` : undefined,
                borderRadius: 4,
                display: 'inline-block',
                verticalAlign: 'middle',
                cursor: linkUrl ? 'pointer' : 'default',
            }}
            draggable={false}
        />
    ) : (
        <div style={{ padding: 12, border: '1px dashed #cbd5e1', color: '#94a3b8', fontSize: 12 }}>
            Image
        </div>
    );

    return (
        <span
            {...attributes}
            contentEditable={false}
            style={{
                position: 'relative',
                display: isInline ? 'inline-flex' : 'flex',
                justifyContent: isInline ? 'initial' : justify,
                alignItems: 'center',
                margin: isInline ? '0 2px' : '8px 0',
                verticalAlign: 'middle',
                paddingRight: '10px',
            }}
            className="group"
        >
            {linkUrl ? (
                <a
                    href={linkUrl}
                    target={target || '_blank'}
                    rel={target === '_blank' || !target ? 'noopener noreferrer' : undefined}
                    style={{ display: 'inline-block' }}
                >
                    {image}
                </a>
            ) : image}
            <button
                type="button"
                aria-label="Resize image"
                title="Resize image"
                onMouseDown={startResize}
                onClick={(event) => event.preventDefault()}
                className="absolute right-0 top-1/2 -translate-y-1/2 rounded-full border border-[#1E5FEA] bg-white p-0.5 text-[#1E5FEA] opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 hover:bg-blue-50 dark:border-blue-500 dark:bg-admin-card dark:hover:bg-blue-900/20"
            >
                <svg className="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M3 9 9 3" />
                    <path d="M6 9H9V6" />
                </svg>
            </button>
            {children}
        </span>
    );
}

const ImagePluginDef = createPlatePlugin({
    key: 'img',
    node: {
        isElement: true,
        isInline: true,
        isVoid: true,
        type: 'img',
    },
}).withComponent(ImageElement);

const TOOLBAR_BUTTON_CLASS = 'inline-flex h-7 w-7 items-center justify-center rounded text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-admin-text-secondary dark:hover:bg-white/10 dark:hover:text-admin-text-primary';
const COLOR_BUTTON_CLASS = 'h-7 w-8 cursor-pointer rounded border border-gray-200 bg-white p-0.5 dark:border-admin-border dark:bg-white/5';
const TOOLTIP_CLASS = 'pointer-events-none absolute left-1/2 top-full z-20 mt-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-[11px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-gray-700';
const EDITOR_CONTENT_CLASS = [
    'min-h-[112px] border-0 bg-transparent p-0 text-sm text-gray-900 outline-none dark:text-admin-text-primary',
    '[&_ol]:!my-1.5 [&_ol]:!pl-[15px] [&_ol]:list-decimal',
    '[&_ul]:!my-1.5 [&_ul]:!pl-[15px] [&_ul]:list-disc',
    '[&_li]:!my-1.5 [&_li]:pl-1 [&_li]:leading-relaxed',
    '[&_li>p]:my-0',
].join(' ');
const EMOJIS = ['😀', '🙂', '😊', '👍', '🔥', '✨', '🚀', '💡', '🎉', '❤️'];

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function normalizeHtml(value) {
    return String(value ?? '').replace(/\r\n/g, '\n').trim();
}

function htmlToPlainText(value) {
    const source = String(value ?? '');

    if (!source.trim()) {
        return '';
    }

    const template = document.createElement('template');
    template.innerHTML = source;

    const blockSelectors = ['p', 'div', 'li', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    blockSelectors.forEach((selector) => {
        template.content.querySelectorAll(selector).forEach((node) => {
            node.insertAdjacentText('beforebegin', '\n');
            node.insertAdjacentText('afterend', '\n');
        });
    });

    template.content.querySelectorAll('br').forEach((node) => {
        node.replaceWith('\n');
    });

    return (template.content.textContent || '')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

function plainTextToValue(value) {
    const text = String(value ?? '').replace(/\r\n/g, '\n');

    if (!text.trim()) {
        return [{ type: 'p', children: [{ text: '' }] }];
    }

    return text.split(/\n{2,}/).map((paragraph) => ({
        type: 'p',
        children: [{ text: paragraph }],
    }));
}

function normalizeTextNodes(nodes) {
    const normalized = [];

    nodes.forEach((node) => {
        if (!node) {
            return;
        }

        if (Object.prototype.hasOwnProperty.call(node, 'text')) {
            const previous = normalized[normalized.length - 1];
            const previousKeys = previous ? Object.keys(previous).sort().join('|') : '';
            const currentKeys = Object.keys(node).sort().join('|');

            if (previous && Object.prototype.hasOwnProperty.call(previous, 'text') && previousKeys === currentKeys) {
                const hasSameMarks = currentKeys.split('|').every((key) => previous[key] === node[key]);

                if (hasSameMarks) {
                    previous.text += node.text;
                    return;
                }
            }
        }

        normalized.push(node);
    });

    return normalized;
}

function extractStyleMarks(element, marks) {
    const nextMarks = { ...marks };
    const tagName = element.tagName.toLowerCase();

    if (tagName === 'strong' || tagName === 'b') {
        nextMarks.bold = true;
    }

    if (tagName === 'em' || tagName === 'i') {
        nextMarks.italic = true;
    }

    if (tagName === 'u') {
        nextMarks.underline = true;
    }

    const color = element.style?.color;
    const backgroundColor = element.style?.backgroundColor;

    if (color) {
        nextMarks.color = color;
    }

    if (backgroundColor) {
        nextMarks.backgroundColor = backgroundColor;
    }

    return nextMarks;
}

function deserializeInlineNodes(nodeList, marks = {}) {
    const children = [];

    Array.from(nodeList || []).forEach((node) => {
        if (node.nodeType === Node.TEXT_NODE) {
            if (node.textContent) {
                children.push({ text: node.textContent, ...marks });
            }

            return;
        }

        if (node.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        const element = node;
        const tagName = element.tagName.toLowerCase();

        if (tagName === 'br') {
            children.push({ text: '\n', ...marks });
            return;
        }

        const nextMarks = extractStyleMarks(element, marks);

        if (tagName === 'a') {
            const linkChildren = normalizeTextNodes(deserializeInlineNodes(element.childNodes, nextMarks));
            children.push({
                type: 'a',
                url: element.getAttribute('href') || '#',
                ...(element.getAttribute('target') ? { target: element.getAttribute('target') } : {}),
                children: linkChildren.length ? linkChildren : [{ text: element.textContent || '', ...nextMarks }],
            });
            return;
        }

        children.push(...deserializeInlineNodes(element.childNodes, nextMarks));
    });

    return children;
}

function deserializeListItem(element, listStyleType, listStart, indent = 1) {
    const children = normalizeTextNodes(deserializeInlineNodes(element.childNodes));

    return {
        type: 'p',
        indent,
        listStyleType,
        ...(typeof listStart === 'number' ? { listStart } : {}),
        children: children.length ? children : [{ text: '' }],
    };
}

function imageNodeFromElement(element) {
    const src = String(element.getAttribute('src') || '').trim();
    if (!src) {
        return null;
    }
    const alt = String(element.getAttribute('alt') || '');
    const widthAttr = parseInt(element.getAttribute('width') || '', 10);
    const styleText = String(element.getAttribute('style') || '');
    const widthMatch = /width:\s*(\d+)/i.exec(styleText);
    const width = Number.isFinite(widthAttr) && widthAttr > 0
        ? widthAttr
        : (widthMatch ? parseInt(widthMatch[1], 10) : undefined);
    const parentStyle = String((element.parentElement && element.parentElement.getAttribute('style')) || '');
    let align = 'center';
    if (/justify-content:\s*flex-start/i.test(parentStyle) || /text-align:\s*left/i.test(parentStyle)) {
        align = 'left';
    } else if (/justify-content:\s*flex-end/i.test(parentStyle) || /text-align:\s*right/i.test(parentStyle)) {
        align = 'right';
    }
    const linkUrl = String(element.closest?.('a[href]')?.getAttribute('href') || '').trim();
    const target = String(element.closest?.('a[href]')?.getAttribute('target') || '').trim();
    return {
        type: 'img',
        url: src,
        alt,
        ...(Number.isFinite(width) && width > 0 ? { width } : {}),
        align,
        ...(linkUrl ? { linkUrl } : {}),
        ...(target ? { target } : {}),
        children: [{ text: '' }],
    };
}

function deserializeBlockNode(node) {
    if (node.nodeType === Node.TEXT_NODE) {
        const text = String(node.textContent || '').trim();
        return text ? [{ type: 'p', children: [{ text }] }] : [];
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return [];
    }

    const element = node;
    const tagName = element.tagName.toLowerCase();

    if (tagName === 'img') {
        const imageNode = imageNodeFromElement(element);
        return imageNode ? [imageNode] : [];
    }

    if (tagName === 'a') {
        const onlyChild = element.children.length === 1 ? element.children[0] : null;
        if (onlyChild && onlyChild.tagName?.toLowerCase() === 'img') {
            const imageNode = imageNodeFromElement(onlyChild);
            return imageNode ? [imageNode] : [];
        }

        if (onlyChild && onlyChild.tagName?.toLowerCase() === 'div' && onlyChild.children.length === 1) {
            const nestedChild = onlyChild.children[0];
            if (nestedChild && nestedChild.tagName?.toLowerCase() === 'img') {
                const imageNode = imageNodeFromElement(nestedChild);
                return imageNode ? [imageNode] : [];
            }
        }
    }

    // <div>/<p> wrappers that exist purely to wrap an image (e.g. our serialized
    // alignment wrapper) should bubble up the image as a block node rather than
    // discarding it inside an empty paragraph.
    if ((tagName === 'div' || tagName === 'p') && element.children.length === 1) {
        const onlyChild = element.children[0];
        if (onlyChild && onlyChild.tagName?.toLowerCase() === 'a' && onlyChild.children.length === 1) {
            const nestedChild = onlyChild.children[0];
            if (nestedChild && nestedChild.tagName?.toLowerCase() === 'img') {
                const imageNode = imageNodeFromElement(nestedChild);
                return imageNode ? [imageNode] : [];
            }
        }
        if (onlyChild && onlyChild.tagName?.toLowerCase() === 'img') {
            const imageNode = imageNodeFromElement(onlyChild);
            return imageNode ? [imageNode] : [];
        }
    }

    if (tagName === 'ol' || tagName === 'ul') {
        const listStyleType = tagName === 'ol' ? 'decimal' : 'disc';
        const startAttr = Number(element.getAttribute('start') || '');
        const indent = Number(element.dataset.indent || element.getAttribute('aria-level') || '1') || 1;
        let itemIndex = 0;

        return Array.from(element.children)
            .filter((child) => child.tagName?.toLowerCase() === 'li')
            .map((child) => {
                const listStart = tagName === 'ol' ? ((Number.isFinite(startAttr) && startAttr > 0 ? startAttr : 1) + itemIndex) : undefined;
                itemIndex += 1;
                return deserializeListItem(child, listStyleType, listStart, indent);
            });
    }

    const align = element.style?.textAlign || '';
    const alignProp = align && ['left', 'center', 'right', 'justify', 'start', 'end'].includes(align) ? { textAlign: align } : {};

    if (tagName === 'blockquote') {
        const children = normalizeTextNodes(deserializeInlineNodes(element.childNodes));
        return [{ type: 'blockquote', ...alignProp, children: children.length ? children : [{ text: '' }] }];
    }

    if (tagName.match(/^h[1-6]$/)) {
        const children = normalizeTextNodes(deserializeInlineNodes(element.childNodes));
        return [{ type: tagName, ...alignProp, children: children.length ? children : [{ text: '' }] }];
    }

    if (tagName === 'p' || tagName === 'div') {
        const children = normalizeTextNodes(deserializeInlineNodes(element.childNodes));
        return [{ type: 'p', ...alignProp, children: children.length ? children : [{ text: '' }] }];
    }

    const children = normalizeTextNodes(deserializeInlineNodes(element.childNodes));
    return children.length ? [{ type: 'p', children }] : [];
}

function htmlToValue(value) {
    const html = String(value ?? '').trim();

    if (!html) {
        return [{ type: 'p', children: [{ text: '' }] }];
    }

    const template = document.createElement('template');
    template.innerHTML = html;

    const blocks = Array.from(template.content.childNodes).flatMap(deserializeBlockNode);

    return blocks.length ? blocks : [{ type: 'p', children: [{ text: '' }] }];
}

function valueFromStoredContent(value) {
    const html = normalizeHtml(value);

    if (!html) {
        return [{ type: 'p', children: [{ text: '' }] }];
    }

    if (/<\s*[a-z][\s\S]*>/i.test(html)) {
        return htmlToValue(html);
    }

    return plainTextToValue(html);
}

function serializeListItemChildren(node) {
    const children = (node.children || []).map(serializeNodeToHtml).join('');
    return children || '<br>';
}

function getListTag(listStyleType) {
    return listStyleType === 'decimal' ? 'ol' : 'ul';
}

function flushListBuffer(buffer) {
    if (!buffer.items.length) {
        return '';
    }

    const tag = getListTag(buffer.listStyleType);
    const start = buffer.listStart && buffer.listStart > 1 ? ` start="${buffer.listStart}"` : '';
    const listStyle = 'margin: 6px 0; padding-left: 15px;';
    const itemStyle = 'margin: 6px 0; line-height: 1.625;';
    return `<${tag}${start} style="${listStyle}"><li style="${itemStyle}">${buffer.items.join(`</li><li style="${itemStyle}">`)}</li></${tag}>`;
}

function serializeNodeToHtml(node) {
    if (!node) {
        return '';
    }

    if (node.type === 'img' && node.url) {
        const src = escapeHtml(node.url);
        const alt = escapeHtml(node.alt || '');
        const width = Number.isFinite(node.width) && node.width > 0 ? Number(node.width) : null;
        const widthAttr = width ? ` width="${width}"` : '';
        const widthStyle = width ? `width:${width}px;` : '';
        const align = node.align === 'left' || node.align === 'right' || node.align === 'inline' ? node.align : 'center';
        const justify = align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center';
        const imgStyle = align === 'inline'
            ? `display:inline-block;vertical-align:middle;max-width:100%;height:auto;${widthStyle}`
            : `max-width:100%;height:auto;${widthStyle}`;
        const linkUrl = String(node.linkUrl || '').trim();
        const target = String(node.target || '').trim();
        const targetAttr = target ? ` target="${escapeHtml(target)}"` : '';
        const relAttr = target === '_blank' || !target ? ' rel="noopener noreferrer"' : '';
        const imageHtml = linkUrl
            ? `<a href="${escapeHtml(linkUrl)}"${targetAttr}${relAttr} style="display:inline-block;"><img src="${src}" alt="${alt}"${widthAttr} style="${imgStyle}" /></a>`
            : `<img src="${src}" alt="${alt}"${widthAttr} style="${imgStyle}" />`;
        if (align === 'inline') {
            return imageHtml;
        }

        return `<div style="display:flex;justify-content:${justify};margin:8px 0;">${imageHtml}</div>`;
    }

    if (Object.prototype.hasOwnProperty.call(node, 'text')) {
        let html = escapeHtml(node.text);

        if (node.bold) {
            html = `<strong>${html}</strong>`;
        }

        if (node.italic) {
            html = `<em>${html}</em>`;
        }

        if (node.underline) {
            html = `<u>${html}</u>`;
        }

        const styles = [];

        if (node.color) {
            styles.push(`color: ${escapeHtml(node.color)}`);
        }

        if (node.backgroundColor) {
            styles.push(`background-color: ${escapeHtml(node.backgroundColor)}`);
        }

        if (styles.length) {
            html = `<span style="${styles.join('; ')}">${html}</span>`;
        }

        return html;
    }

    const children = (node.children || []).map(serializeNodeToHtml).join('');

    if (node.type === 'a') {
        const url = escapeHtml(node.url || node.href || '#');
        const target = node.target ? ` target="${escapeHtml(node.target)}"` : '';
        return `<a href="${url}"${target} style="text-decoration: underline;">${children}</a>`;
    }

    if (node.type === 'list') {
        const tag = node.listStyleType === 'decimal' ? 'ol' : 'ul';
        return `<${tag}><li>${children}</li></${tag}>`;
    }

    const alignStyle = node.textAlign && node.textAlign !== 'start' ? ` style="text-align: ${escapeHtml(node.textAlign)};"` : '';

    if (node.type === 'blockquote') {
        return `<blockquote${alignStyle}>${children}</blockquote>`;
    }

    if (String(node.type || '').match(/^h[1-6]$/)) {
        return `<${node.type}${alignStyle}>${children}</${node.type}>`;
    }

    return `<p${alignStyle}>${children || '<br>'}</p>`;
}

function serializeValueToHtml(value) {
    const nodes = Array.isArray(value) ? value : [];
    const parts = [];
    let listBuffer = null;
    let inlineBuffer = [];

    const flushInlineBuffer = () => {
        if (!inlineBuffer.length) {
            return;
        }

        parts.push(`<p>${inlineBuffer.join('') || '<br>'}</p>`);
        inlineBuffer = [];
    };

    nodes.forEach((node) => {
        if (node?.listStyleType) {
            flushInlineBuffer();
            if (!listBuffer || listBuffer.listStyleType !== node.listStyleType || (node.listStart && node.listStart !== listBuffer.listStart && listBuffer.items.length === 0)) {
                if (listBuffer) {
                    parts.push(flushListBuffer(listBuffer));
                }

                listBuffer = {
                    listStyleType: node.listStyleType,
                    listStart: node.listStart,
                    items: [],
                };
            }

            listBuffer.items.push(serializeListItemChildren(node));
            return;
        }

        if (listBuffer) {
            parts.push(flushListBuffer(listBuffer));
            listBuffer = null;
        }

        if (node?.type === 'a' || Object.prototype.hasOwnProperty.call(node || {}, 'text')) {
            inlineBuffer.push(serializeNodeToHtml(node));
            return;
        }

        flushInlineBuffer();
        parts.push(serializeNodeToHtml(node));
    });

    if (listBuffer) {
        parts.push(flushListBuffer(listBuffer));
    }

    flushInlineBuffer();

    return normalizeHtml(parts.join(''));
}

function MarkToolbarButton({ nodeType, label, title, className = '' }) {
    const state = useMarkToolbarButtonState({ nodeType });
    const { props } = useMarkToolbarButton(state);

    return (
        <button
            type="button"
            className={`group relative ${TOOLBAR_BUTTON_CLASS} ${props.pressed ? 'bg-blue-50 text-[#1E5FEA] dark:bg-blue-900/20' : ''} ${className}`.trim()}
            aria-label={title}
            aria-pressed={props.pressed}
            onClick={props.onClick}
            onMouseDown={props.onMouseDown}
        >
            {label}
            <span className={TOOLTIP_CLASS}>{title}</span>
        </button>
    );
}

function ToolbarButton({ label, title, onClick, pressed = false, className = '', disabled = false }) {
    return (
        <button
            type="button"
            className={`group relative ${TOOLBAR_BUTTON_CLASS} ${pressed ? 'bg-blue-50 text-[#1E5FEA] dark:bg-blue-900/20' : ''} ${disabled ? 'cursor-not-allowed opacity-50' : ''} ${className}`.trim()}
            aria-label={title}
            aria-pressed={pressed}
            disabled={disabled}
            onMouseDown={(event) => event.preventDefault()}
            onClick={disabled ? undefined : onClick}
        >
            {label}
            <span className={TOOLTIP_CLASS}>{title}</span>
        </button>
    );
}

function RichToolbar({ onSync, uploadUrl = '' }) {
    const editor = useEditorRef();
    const fileInputRef = useRef(null);
    const [showLinkPopover, setShowLinkPopover] = useState(false);
    const [showImagePopover, setShowImagePopover] = useState(false);
    const [linkUrl, setLinkUrl] = useState('');
    const [linkText, setLinkText] = useState('');
    const [imageAltText, setImageAltText] = useState('');
    const [imageLinkUrl, setImageLinkUrl] = useState('');
    const [imageTarget, setImageTarget] = useState('_blank');
    const [imageWidth, setImageWidth] = useState('');
    const [pendingImageUrl, setPendingImageUrl] = useState('');
    const [openInNewTab, setOpenInNewTab] = useState(true);
    const [isUploadingImage, setIsUploadingImage] = useState(false);
    const [imageUploadError, setImageUploadError] = useState('');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const appendUrlVariable = (token) => {
        setLinkUrl((current) => `${current || ''}${token}`);
    };

    const resetLinkForm = () => {
        setLinkUrl('');
        setLinkText('');
        setOpenInNewTab(true);
    };

    const resetImageForm = () => {
        setPendingImageUrl('');
        setImageAltText('');
        setImageLinkUrl('');
        setImageTarget('_blank');
        setImageWidth('');
    };

    const openLinkPopover = () => {
        const selectedText = editor.api.string(editor.selection) || '';
        setLinkUrl('');
        setLinkText(selectedText);
        setOpenInNewTab(true);
        setShowLinkPopover(true);
    };

    const closeLinkPopover = () => {
        setShowLinkPopover(false);
        resetLinkForm();
    };

    const closeImagePopover = () => {
        setShowImagePopover(false);
        resetImageForm();
    };

    const applyLink = () => {
        const url = String(linkUrl || '').trim();
        if (!url) {
            return;
        }

        editor.tf.focus();
        const text = String(linkText || '').trim() || editor.api.string(editor.selection) || url;

        upsertLink(editor, {
            url,
            text,
            target: openInNewTab ? '_blank' : undefined,
            skipValidation: true,
        });

        onSync(editor.children);
        closeLinkPopover();
    };

    const applyList = (listStyleType) => {
        editor.tf.focus();
        toggleList(editor, { listStyleType });
        onSync(editor.children);
    };

    const applyAlign = (value) => {
        editor.tf.focus();
        editor.tf.textAlign.setNodes(value);
        onSync(editor.children);
    };

    const applyTextColor = (event) => {
        editor.tf.focus();
        editor.tf.color.addMark(event.target.value);
        onSync(editor.children);
    };

    const applyBackgroundColor = (event) => {
        editor.tf.focus();
        editor.tf.backgroundColor.addMark(event.target.value);
        onSync(editor.children);
    };

    const insertEmoji = (event) => {
        const emoji = event.target.value;
        if (!emoji) {
            return;
        }

        editor.tf.focus();
        editor.tf.insertText(emoji);
        event.target.value = '';
        onSync(editor.children);
    };

    const insertImageNode = (imageUrl, alt = '', width = undefined, linkUrl = '', target = '_blank') => {
        const imageNode = {
            type: 'img',
            url: imageUrl,
            alt,
            ...(Number.isFinite(width) && width > 0 ? { width } : {}),
            ...(String(linkUrl || '').trim() ? { linkUrl: String(linkUrl || '').trim() } : {}),
            ...(String(linkUrl || '').trim() && String(target || '').trim() ? { target: String(target || '').trim() } : {}),
            align: 'inline',
            children: [{ text: '' }],
        };

        editor.tf.focus();

        try {
            editor.tf.insertNodes([imageNode]);
        } catch (error) {
            editor.tf.insertNodes(imageNode);
        }

        onSync(editor.children);
    };

    const openImagePicker = () => {
        setImageUploadError('');

        if (!uploadUrl || isUploadingImage) {
            return;
        }

        fileInputRef.current?.click();
    };

    const uploadImageFile = async (file) => {
        if (!file || !uploadUrl) {
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        setIsUploadingImage(true);
        setImageUploadError('');

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const errorMessage = payload?.message
                    || (payload?.errors ? Object.values(payload.errors).flat().find(Boolean) : '')
                    || 'Image upload failed.';
                throw new Error(errorMessage);
            }

            const url = String(payload?.url || '').trim();
            if (!url) {
                throw new Error('Image upload did not return a URL.');
            }

            setPendingImageUrl(url);
            setImageAltText(file.name.replace(/\.[^.]+$/, ''));
            setImageLinkUrl('');
            setShowImagePopover(true);
        } catch (error) {
            setImageUploadError(error instanceof Error ? error.message : 'Image upload failed.');
        } finally {
            setIsUploadingImage(false);
        }
    };

    const handleImageInputChange = async (event) => {
        const file = event.target.files?.[0] || null;
        event.target.value = '';

        if (!file) {
            return;
        }

        await uploadImageFile(file);
    };

    const applyImageInsertion = () => {
        const url = String(pendingImageUrl || '').trim();
        if (!url) {
            closeImagePopover();
            return;
        }

        const width = Number.parseInt(String(imageWidth || '').trim(), 10);
        insertImageNode(url, imageAltText, Number.isFinite(width) && width > 0 ? width : undefined, imageLinkUrl, imageTarget);
        closeImagePopover();
    };

    const [showBlockMenu, setShowBlockMenu] = useState(false);

    const applyBlockTypeValue = (type) => {
        editor.tf.focus();
        try {
            editor.tf.setNodes(
                { type },
                { match: (n) => editor.api.isBlock(n) }
            );
        } catch (e) {
        }
        onSync(editor.children);
    };

    const blockTypeOptions = [
        { value: 'p', label: 'Paragraph' },
        { value: 'h1', label: 'Heading 1' },
        { value: 'h2', label: 'Heading 2' },
        { value: 'h3', label: 'Heading 3' },
    ];

    const currentBlockType = (() => {
        try {
            const entry = editor.api.block();
            const node = Array.isArray(entry) ? entry[0] : null;
            const type = node && typeof node.type === 'string' ? node.type : 'p';
            return ['h1', 'h2', 'h3'].includes(type) ? type : 'p';
        } catch (e) {
            return 'p';
        }
    })();

    const currentBlockLabel = blockTypeOptions.find((opt) => opt.value === currentBlockType)?.label || 'Paragraph';

    return (
        <div className="relative flex flex-wrap items-center gap-1 border-b border-gray-100 bg-gray-50/50 px-3 py-2 dark:border-admin-border dark:bg-white/[0.03]">
            <div className="relative">
                <button
                    type="button"
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => setShowBlockMenu((value) => !value)}
                    aria-label="Text style"
                    title="Text style"
                    className="inline-flex h-7 items-center gap-1 rounded border border-gray-200 bg-white px-2 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-admin-border dark:bg-white/5 dark:text-admin-text-primary dark:hover:bg-white/10"
                >
                    <span>{currentBlockLabel}</span>
                    <svg className="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                {showBlockMenu ? (
                    <>
                        <div className="fixed inset-0 z-10" onMouseDown={(event) => { event.preventDefault(); setShowBlockMenu(false); }} />
                        <div className="absolute left-0 top-full z-20 mt-1 w-36 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg dark:border-admin-border dark:bg-admin-card">
                            {blockTypeOptions.map((opt) => (
                                <button
                                    key={opt.value}
                                    type="button"
                                    onMouseDown={(event) => event.preventDefault()}
                                    onClick={() => {
                                        setShowBlockMenu(false);
                                        applyBlockTypeValue(opt.value);
                                    }}
                                    className={`block w-full px-3 py-1.5 text-left text-xs ${currentBlockType === opt.value ? 'bg-blue-50 text-[#1E5FEA] dark:bg-blue-900/20' : 'text-gray-700 dark:text-admin-text-primary'} hover:bg-gray-50 dark:hover:bg-white/10`}
                                >
                                    {opt.label}
                                </button>
                            ))}
                        </div>
                    </>
                ) : null}
            </div>
            <span className="mx-1 h-4 w-px bg-gray-200 dark:bg-admin-border"></span>
            <MarkToolbarButton nodeType="bold" label={<Bold className="h-3.5 w-3.5" />} title="Bold" />
            <MarkToolbarButton nodeType="italic" label={<Italic className="h-3.5 w-3.5" />} title="Italic" />
            <MarkToolbarButton nodeType="underline" label={<Underline className="h-3.5 w-3.5" />} title="Underline" />
            <span className="mx-1 h-4 w-px bg-gray-200 dark:bg-admin-border"></span>
            <ToolbarButton label={<List className="h-3.5 w-3.5" />} title="Bullet list" onClick={() => applyList(ListStyleType.Disc)} />
            <ToolbarButton label={<ListOrdered className="h-3.5 w-3.5" />} title="Numbered list" onClick={() => applyList(ListStyleType.Decimal)} />
            <ToolbarButton
                title="Insert link"
                onClick={openLinkPopover}
                label={<Link className="h-3.5 w-3.5" />}
                pressed={showLinkPopover}
            />
            <span className="mx-1 h-4 w-px bg-gray-200 dark:bg-admin-border"></span>
            <ToolbarButton label={<AlignLeft className="h-3.5 w-3.5" />} title="Align left" onClick={() => applyAlign('left')} />
            <ToolbarButton label={<AlignCenter className="h-3.5 w-3.5" />} title="Align center" onClick={() => applyAlign('center')} />
            <ToolbarButton label={<AlignRight className="h-3.5 w-3.5" />} title="Align right" onClick={() => applyAlign('right')} />
            <ToolbarButton label={<AlignJustify className="h-3.5 w-3.5" />} title="Justify" onClick={() => applyAlign('justify')} />
            <span className="mx-1 h-4 w-px bg-gray-200 dark:bg-admin-border"></span>
            <ToolbarButton
                label={isUploadingImage ? (
                    <svg className="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                    </svg>
                ) : (
                    <ImageIcon className="h-3.5 w-3.5" />
                )}
                title={uploadUrl ? 'Insert image' : 'Image upload unavailable'}
                onClick={openImagePicker}
                pressed={false}
                disabled={!uploadUrl || isUploadingImage}
                className={!uploadUrl || isUploadingImage ? 'cursor-not-allowed opacity-50' : ''}
            />
            <input
                ref={fileInputRef}
                type="file"
                accept="image/*,.svg"
                className="hidden"
                onChange={handleImageInputChange}
                disabled={!uploadUrl || isUploadingImage}
            />
            <label className={`group ${TOOLBAR_BUTTON_CLASS} relative cursor-pointer`} aria-label="Text color">
                <Palette className="h-3.5 w-3.5" />
                <input type="color" className={`${COLOR_BUTTON_CLASS} absolute inset-0 opacity-0`} onChange={applyTextColor} />
                <span className={TOOLTIP_CLASS}>Text color</span>
            </label>
            <label className={`group ${TOOLBAR_BUTTON_CLASS} relative cursor-pointer`} aria-label="Background color">
                <PaintBucket className="h-3.5 w-3.5" />
                <input type="color" className={`${COLOR_BUTTON_CLASS} absolute inset-0 opacity-0`} defaultValue="#fff3bf" onChange={applyBackgroundColor} />
                <span className={TOOLTIP_CLASS}>Background color</span>
            </label>
            <label className={`group ${TOOLBAR_BUTTON_CLASS} relative cursor-pointer`} aria-label="Insert emoji">
                <Smile className="h-3.5 w-3.5" />
                <select className="absolute inset-0 cursor-pointer opacity-0" defaultValue="" onChange={insertEmoji}>
                    <option value=""></option>
                    {EMOJIS.map((emoji) => (
                        <option key={emoji} value={emoji}>{emoji}</option>
                    ))}
                </select>
                <span className={TOOLTIP_CLASS}>Insert emoji</span>
            </label>

            {imageUploadError ? (
                <span className="ml-1 text-[11px] text-red-500 dark:text-red-400">{imageUploadError}</span>
            ) : null}

            {showLinkPopover ? (
                <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-admin-border dark:bg-admin-card">
                        <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-admin-border">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-admin-text-primary">Insert Link</h3>
                            <button
                                type="button"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={closeLinkPopover}
                                className="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-admin-text-primary"
                            >
                                <span className="sr-only">Close</span>
                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div className="space-y-4 px-5 py-4">
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-admin-text-secondary">Link text</label>
                                <input
                                    type="text"
                                    value={linkText}
                                    onChange={(event) => setLinkText(event.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] dark:border-admin-border dark:bg-white/5 dark:text-admin-text-primary"
                                    placeholder="Click here"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-admin-text-secondary">URL</label>
                                <input
                                    type="text"
                                    value={linkUrl}
                                    onChange={(event) => setLinkUrl(event.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] dark:border-admin-border dark:bg-white/5 dark:text-admin-text-primary"
                                    placeholder="https://example.com"
                                />
                            </div>
                            <label className="flex items-center gap-2 text-xs text-gray-600 dark:text-admin-text-secondary">
                                <input
                                    type="checkbox"
                                    checked={openInNewTab}
                                    onChange={(event) => setOpenInNewTab(event.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-[#1E5FEA]"
                                />
                                Open in new tab
                            </label>
                            <div>
                                <label className="mb-2 block text-xs font-medium text-gray-600 dark:text-admin-text-secondary">Variables</label>
                                <div className="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onMouseDown={(event) => event.preventDefault()}
                                        onClick={() => appendUrlVariable('{unsubscribe_url}')}
                                        className="inline-flex items-center gap-1 rounded-lg border border-[#1E5FEA]/30 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-[#1E5FEA] hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20"
                                    >
                                        Unsubscribe Link
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4 dark:border-admin-border">
                            <button
                                type="button"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={closeLinkPopover}
                                className="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-admin-border dark:text-admin-text-primary dark:hover:bg-white/10"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={applyLink}
                                className="rounded-lg bg-[#1E5FEA] px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                                disabled={!String(linkUrl || '').trim()}
                            >
                                Insert Link
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}

            {showImagePopover ? (
                <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-admin-border dark:bg-admin-card">
                        <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-admin-border">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-admin-text-primary">Insert Image</h3>
                            <button
                                type="button"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={closeImagePopover}
                                className="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-admin-text-primary"
                            >
                                <span className="sr-only">Close</span>
                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div className="space-y-4 px-5 py-4">
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-admin-text-secondary">Alt text</label>
                                <input
                                    type="text"
                                    value={imageAltText}
                                    onChange={(event) => setImageAltText(event.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] dark:border-admin-border dark:bg-white/5 dark:text-admin-text-primary"
                                    placeholder="Describe the image"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-admin-text-secondary">Link URL</label>
                                <input
                                    type="text"
                                    value={imageLinkUrl}
                                    onChange={(event) => setImageLinkUrl(event.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] dark:border-admin-border dark:bg-white/5 dark:text-admin-text-primary"
                                    placeholder="https://example.com"
                                />
                                <p className="mt-1 text-[11px] text-gray-500 dark:text-admin-text-secondary">Leave empty if the image should not be clickable.</p>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-admin-text-secondary">Width (px)</label>
                                <input
                                    type="number"
                                    min="40"
                                    max="1200"
                                    step="1"
                                    value={imageWidth}
                                    onChange={(event) => setImageWidth(event.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] dark:border-admin-border dark:bg-white/5 dark:text-admin-text-primary"
                                    placeholder="Leave empty for natural size"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-admin-text-secondary">Target</label>
                                <select
                                    value={imageTarget}
                                    onChange={(event) => setImageTarget(event.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] dark:border-admin-border dark:bg-white/5 dark:text-admin-text-primary"
                                >
                                    <option value="_blank">New tab</option>
                                    <option value="_self">Same tab</option>
                                    <option value="_parent">Parent frame</option>
                                    <option value="_top">Top frame</option>
                                </select>
                            </div>
                        </div>
                        <div className="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4 dark:border-admin-border">
                            <button
                                type="button"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={closeImagePopover}
                                className="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-admin-border dark:text-admin-text-primary dark:hover:bg-white/10"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={applyImageInsertion}
                                className="rounded-lg bg-[#1E5FEA] px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                                disabled={!String(pendingImageUrl || '').trim()}
                            >
                                Insert Image
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
}

function InsertTokenBridge({ mountEl, onSync }) {
    const editor = useEditorRef();

    useEffect(() => {
        const handler = (event) => {
            const detail = event.detail || {};
            if (
                String(detail.stepId) !== String(mountEl.dataset.stepId)
                || detail.variant !== mountEl.dataset.variant
                || (detail.field || 'body') !== (mountEl.dataset.field || 'body')
            ) {
                return;
            }

            editor.tf.focus();
            editor.tf.insertText(detail.token || '');
            onSync(editor.children);
        };

        window.addEventListener('outreach-plate-insert-token', handler);

        return () => {
            window.removeEventListener('outreach-plate-insert-token', handler);
        };
    }, [editor, mountEl, onSync]);

    return null;
}

function OutreachPlateEditor({ mountEl }) {
    const observerRef = useRef(null);
    const externalValueRef = useRef(normalizeHtml(mountEl.dataset.value || ''));
    const [externalValue, setExternalValue] = useState(externalValueRef.current);
    const [editorKey, setEditorKey] = useState(0);
    const uploadUrl = String(mountEl.dataset.uploadUrl || '').trim();

    const initialValue = useMemo(() => valueFromStoredContent(externalValue), [externalValue, editorKey]);

    const editor = usePlateEditor({
        plugins: [
            BasicBlocksPlugin,
            BoldPlugin,
            ItalicPlugin,
            UnderlinePlugin,
            LinkPlugin.withComponent(LinkElement),
            ListPlugin,
            TextAlignPlugin.configure({
                inject: {
                    nodeProps: {
                        defaultNodeValue: 'start',
                        nodeKey: 'textAlign',
                        styleKey: 'textAlign',
                        validNodeValues: ['start', 'left', 'center', 'right', 'end', 'justify'],
                    },
                    targetPlugins: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'],
                },
            }),
            FontColorPlugin,
            FontBackgroundColorPlugin,
            EmojiPlugin,
            EmojiInputPlugin,
            ImagePluginDef,
            HtmlPlugin,
        ],
        value: initialValue,
    }, [editorKey]);

    useEffect(() => {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type !== 'attributes' || mutation.attributeName !== 'data-value') {
                    return;
                }

                const nextValue = normalizeHtml(mountEl.dataset.value || '');
                if (nextValue === externalValueRef.current) {
                    return;
                }

                externalValueRef.current = nextValue;
                setExternalValue(nextValue);
                setEditorKey((current) => current + 1);
            });
        });

        observer.observe(mountEl, { attributes: true, attributeFilter: ['data-value'] });
        observerRef.current = observer;

        return () => {
            observer.disconnect();
            observerRef.current = null;
        };
    }, [mountEl]);

    const dispatchChange = (value = editor.children) => {
        const html = serializeValueToHtml(Array.isArray(value) ? value : editor.children);
        externalValueRef.current = html;
        mountEl.dataset.value = html;
        window.dispatchEvent(new CustomEvent('outreach-plate-change', {
            detail: {
                stepId: mountEl.dataset.stepId,
                variant: mountEl.dataset.variant,
                field: mountEl.dataset.field || 'body',
                value: html,
            },
        }));
    };

    const dispatchFocus = () => {
        window.dispatchEvent(new CustomEvent('outreach-plate-focus', {
            detail: {
                stepId: mountEl.dataset.stepId,
                variant: mountEl.dataset.variant,
                field: mountEl.dataset.field || 'body',
            },
        }));
    };

    return (
        <div className="mailpurse-outreach-editor overflow-hidden rounded-lg border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5">
            <style>{`.mailpurse-outreach-editor [contenteditable="true"] ol,.mailpurse-outreach-editor [contenteditable="true"] ul{margin:6px 0 !important;padding-left:15px !important}.mailpurse-outreach-editor [contenteditable="true"] li{margin:6px 0 !important;line-height:1.625}.mailpurse-outreach-editor [contenteditable="true"] li>p{margin:0}.mailpurse-outreach-editor [contenteditable="true"] a{text-decoration:underline}.mailpurse-outreach-editor [contenteditable="true"] h1{font-size:1.875rem;font-weight:700;line-height:1.2;margin:12px 0}.mailpurse-outreach-editor [contenteditable="true"] h2{font-size:1.5rem;font-weight:700;line-height:1.25;margin:10px 0}.mailpurse-outreach-editor [contenteditable="true"] h3{font-size:1.25rem;font-weight:600;line-height:1.3;margin:8px 0}`}</style>
            <Plate editor={editor} onChange={({ value }) => dispatchChange(value)}>
                <RichToolbar onSync={dispatchChange} uploadUrl={uploadUrl} />
                <div className="min-h-[148px] px-4 py-3 text-sm text-gray-900 dark:text-admin-text-primary">
                    <InsertTokenBridge mountEl={mountEl} onSync={dispatchChange} />
                    <PlateContent
                        placeholder={mountEl.dataset.placeholder || 'Write your outreach email...'}
                        className={EDITOR_CONTENT_CLASS}
                        onFocus={dispatchFocus}
                        onBlur={() => dispatchChange()}
                    />
                </div>
            </Plate>
        </div>
    );
}

function isElementVisible(el) {
    if (!el || !el.isConnected) {
        return false;
    }
    // Plate's contenteditable does not render correctly when mounted inside a
    // display:none ancestor (e.g. a collapsed wizard step). Defer until the
    // container actually has layout.
    return el.offsetParent !== null || el.offsetHeight > 0 || el.getClientRects().length > 0;
}

function mountOutreachEditors(root = document) {
    root.querySelectorAll('[data-outreach-rich-editor]').forEach((mountEl) => {
        if (mountEl.__mailpursePlateRoot || mountEl.__mailpursePlatePending) {
            return;
        }

        const doMount = () => {
            if (mountEl.__mailpursePlateRoot) {
                return;
            }
            const rootInstance = createRoot(mountEl);
            mountEl.__mailpursePlateRoot = rootInstance;
            rootInstance.render(<OutreachPlateEditor mountEl={mountEl} />);
        };

        if (isElementVisible(mountEl)) {
            doMount();
            return;
        }

        // Wait for the container to become visible before mounting Plate.
        mountEl.__mailpursePlatePending = true;

        const cleanup = () => {
            mountEl.__mailpursePlatePending = false;
            if (visibilityObserver) {
                try { visibilityObserver.disconnect(); } catch (_) {}
            }
            if (timeoutId) {
                window.clearTimeout(timeoutId);
                timeoutId = null;
            }
        };

        const tryMount = () => {
            if (mountEl.__mailpursePlateRoot) {
                cleanup();
                return;
            }
            if (!mountEl.isConnected) {
                cleanup();
                return;
            }
            if (isElementVisible(mountEl)) {
                cleanup();
                doMount();
            }
        };

        // Watch ancestor class/style mutations so we react instantly when
        // a wizard step is expanded (e.g. .accordion-step gains .is-active or
        // .accordion-body loses .hidden).
        let visibilityObserver = null;
        try {
            visibilityObserver = new MutationObserver(() => {
                tryMount();
            });
            let cursor = mountEl;
            // Observe up to ~10 ancestors — covers wizard/accordion/tabs.
            for (let i = 0; cursor && i < 10; i += 1) {
                visibilityObserver.observe(cursor, { attributes: true, attributeFilter: ['class', 'style', 'hidden'] });
                cursor = cursor.parentElement;
            }
        } catch (_) {
            visibilityObserver = null;
        }

        // Also poll as a safety net in case visibility flips without an
        // attribute mutation (e.g. ancestor stylesheet swap).
        let timeoutId = null;
        const started = Date.now();
        const tick = () => {
            timeoutId = null;
            if (mountEl.__mailpursePlateRoot || !mountEl.__mailpursePlatePending) {
                return;
            }
            tryMount();
            if (mountEl.__mailpursePlateRoot || !mountEl.__mailpursePlatePending) {
                return;
            }
            // Stop polling after 10 minutes; observer will still react to
            // class changes if any.
            if (Date.now() - started > 10 * 60 * 1000) {
                return;
            }
            timeoutId = window.setTimeout(tick, 150);
        };
        tick();
    });
}

function scheduleMountOutreachEditors(root = document) {
    if (!(root instanceof Document) && !(root instanceof HTMLElement)) {
        return;
    }

    const run = () => mountOutreachEditors(root);

    if (typeof window.requestAnimationFrame === 'function') {
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(run);
        });
        return;
    }

    window.setTimeout(run, 0);
}

function bootOutreachEditors() {
    scheduleMountOutreachEditors(document);

    if (window.__mailpurseOutreachEditorsObserver) {
        return;
    }

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.matches('[data-outreach-rich-editor]')) {
                    scheduleMountOutreachEditors(node.parentElement || document);
                    return;
                }

                scheduleMountOutreachEditors(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
    window.__mailpurseOutreachEditorsObserver = observer;
}

document.addEventListener('DOMContentLoaded', bootOutreachEditors);
document.addEventListener('turbo:load', bootOutreachEditors);

if (document.readyState !== 'loading') {
    bootOutreachEditors();
}

window.__mailpurseOutreachEditors = {
    escapeHtml,
    mount: scheduleMountOutreachEditors,
};
