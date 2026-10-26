import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import Lottie from 'lottie-react';
import buildingAnimation from './lottie/building.json';
import { AiBuilderFoundryPanel } from './page-builder/AiBuilderFoundryPanel.jsx';
import { AiBuilderPreloader } from './page-builder/AiBuilderPreloader.jsx';
import { AppDashboardPanel } from './page-builder/AppDashboardPanel.jsx';
import { InlineEditToolbar } from './page-builder/InlineEditToolbar.jsx';
import { THEME_PRESETS, composePromptWithTheme, getThemePreset, getThemeTypography, mergeThemePromptIntoPrompt, stripVisibleThemePrompt } from './page-builder/themePresets.js';
// Fine-tuning presets are now applied server-side before the Vite build.
import Editor from '@monaco-editor/react';

const PROVIDER_META = {
    chatgpt: { color: '#10A37F', label: 'ChatGPT' },
};

const MODEL_LABELS = {
    'gpt-5.6-sol': 'GPT-5.6 Sol',
    'gpt-5.6-tera': 'GPT-5.6 Tera',
    'gpt-5.6-luna': 'GPT-5.6 Luna',
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
    // OpenRouter
    'openai/gpt-5.6-sol': 'GPT-5.6 Sol',
    'openai/gpt-5.6-tera': 'GPT-5.6 Tera',
    'openai/gpt-5.6-luna': 'GPT-5.6 Luna',
    'openai/gpt-5.4-mini': 'GPT-5.4 Mini',
    'openai/gpt-5.4-nano': 'GPT-5.4 Nano',
    'openai/gpt-5.2': 'GPT-5.2',
    'openai/gpt-4o': 'GPT-4o',
    'openai/gpt-4o-mini': 'GPT-4o Mini',
    'openai/gpt-4.1': 'GPT-4.1',
    'openai/gpt-4.1-mini': 'GPT-4.1 Mini',
    'openai/gpt-4.1-nano': 'GPT-4.1 Nano',
    'openai/o3-mini': 'o3-mini',
    'openai/o4-mini': 'o4-mini',
    'openai/gpt-4-turbo': 'GPT-4 Turbo',
    'openai/gpt-3.5-turbo': 'GPT-3.5 Turbo',
    'openai/chatgpt-4o-latest': 'ChatGPT-4o Latest',
    'anthropic/claude-fable-5': 'Claude Fable 5',
    'anthropic/claude-opus-5': 'Claude Opus 5',
    'anthropic/claude-sonnet-5': 'Claude Sonnet 5',
    'anthropic/claude-opus-4.8-fast': 'Claude Opus 4.8 Fast',
    'anthropic/claude-opus-4.8': 'Claude Opus 4.8',
    'anthropic/claude-opus-4.7': 'Claude Opus 4.7',
    'anthropic/claude-opus-4.6': 'Claude Opus 4.6',
    'anthropic/claude-opus-4.5': 'Claude Opus 4.5',
    'anthropic/claude-haiku-4.5': 'Claude Haiku 4.5',
    'google/gemini-3.6-flash': 'Gemini 3.6 Flash',
    'google/gemini-3.5-flash-lite': 'Gemini 3.5 Flash Lite',
    'google/gemini-3.5-flash': 'Gemini 3.5 Flash',
    'google/gemini-3.1-flash-lite': 'Gemini 3.1 Flash Lite',
    'meta-llama/llama-3.3-70b-instruct': 'Llama 3.3 70B',
    'meta-llama/llama-3.1-405b-instruct': 'Llama 3.1 405B',
    'meta-llama/llama-3.1-70b-instruct': 'Llama 3.1 70B',
    'meta-llama/llama-3.1-8b-instruct': 'Llama 3.1 8B',
    'mistralai/mistral-large': 'Mistral Large',
    'mistralai/codestral-2508': 'Codestral',
    'mistralai/mistral-medium-3': 'Mistral Medium 3',
    'mistralai/mistral-small-3.2-24b-instruct': 'Mistral Small 3.2',
    'qwen/qwen3.7-max': 'Qwen3.7 Max',
    'qwen/qwen3.7-plus': 'Qwen3.7 Plus',
    'qwen/qwen3.6-plus': 'Qwen3.6 Plus',
    'deepseek/deepseek-v4-flash': 'DeepSeek V4 Flash',
    'deepseek/deepseek-v4-pro': 'DeepSeek V4 Pro',
    'deepseek/deepseek-v3.2': 'DeepSeek V3.2',
    'moonshotai/kimi-k3': 'Kimi K3',
    'moonshotai/kimi-k2.7-code': 'Kimi K2.7 Code',
    'moonshotai/kimi-k2.6': 'Kimi K2.6',
    'x-ai/grok-4.5': 'Grok 4.5',
    'x-ai/grok-4.3': 'Grok 4.3',
    'z-ai/glm-5.2': 'GLM 5.2',
    'z-ai/glm-5.1': 'GLM 5.1',
    'minimax/minimax-m3': 'MiniMax M3',
    'minimax/minimax-m2.7': 'MiniMax M2.7',
    'xiaomi/mimo-v2.5': 'MiMo-V2.5',
    'xiaomi/mimo-v2.5-pro': 'MiMo-V2.5-Pro',
    'openrouter/auto-beta': 'OpenRouter Auto (Beta)',
    'openrouter/free': 'OpenRouter Free Router',
    'inclusionai/ling-3.0-flash': 'Ling 3.0 Flash',
    'inclusionai/ling-3.0-flash:free': 'Ling 3.0 Flash Free',
    'nvidia/nemotron-3-ultra-550b-a55b:free': 'Nemotron 3 Ultra Free',
    'meta-llama/llama-3.1-8b-instruct:free': 'Llama 3.1 8B Free',
    'mistralai/mistral-7b-instruct:free': 'Mistral 7B Free',
    'google/gemma-2-9b-it:free': 'Gemma 2 9B Free',
    'microsoft/phi-3-mini-128k-instruct:free': 'Phi-3 Mini Free',
};




function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

const DEFAULT_REACT_THEME = {
    primary: '#2563eb',
    secondary: '#06b6d4',
    background: '#f8fafc',
    text: '#0f172a',
};

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeClosingScriptTags(value) {
    return String(value ?? '').replace(/<\/script>/gi, '<\\/script>');
}

function deriveFileName(event) {
    if (!event) return 'src/App.jsx';
    const type = event.type || 'design';
    const title = (event.title || '').toLowerCase();
    const nameMatch = title.match(/([a-z]+(?:[-\s][a-z]+){0,3})\s+(?:section|component|carousel|grid|card|hero|banner|form|modal|list|nav|footer|header|testimonial|pricing|feature|contact|about|team|gallery|blog)/i);
    const baseName = nameMatch
        ? nameMatch[1].replace(/(?:^|-|\s)(\w)/g, (_, c) => c.toUpperCase()).replace(/[-\s]/g, '')
        : '';
    const map = {
        analysis: 'docs/requirements.md',
        planning: 'docs/architecture.md',
        design: 'design/tokens.json',
        components: baseName ? `src/components/${baseName}.jsx` : 'src/components/Layout.jsx',
        content: baseName ? `src/sections/${baseName}Section.jsx` : 'src/sections/ContentSection.jsx',
        forms: baseName ? `src/components/forms/${baseName}Form.jsx` : 'src/components/ContactForm.jsx',
        integrations: 'src/hooks/useApi.js',
        optimization: 'config/optimization.js',
        testing: 'tests/e2e.spec.js',
        deployment: 'build/index.html',
    };
    return map[type] || 'src/App.jsx';
}

function getEventIconSvg(type, title = '') {
    const t = (type || '').toLowerCase();
    const tl = (title || '').toLowerCase();
    const codeIcon = <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>;
    const brainIcon = <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5a3 3 0 1 0-5.997.125 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588A4 4 0 1 0 12 18Z"/><path d="M12 5a3 3 0 1 1 5.997.125 4 4 0 0 1 2.526 5.77 4 4 0 0 1-.556 6.588A4 4 0 1 1 12 18Z"/></svg>;
    const clipboardIcon = <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>;
    const sparkleIcon = <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>;
    const chartIcon = <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>;
    const terminalIcon = <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" x2="20" y1="19" y2="19"/></svg>;
    const layersIcon = <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>;
    const checkIcon = <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>;

    if (tl.includes('artifact') || tl.includes('code') || tl.includes('export') || tl.includes('component')) return codeIcon;
    if (tl.includes('setup') || tl.includes('install') || tl.includes('configure') || tl.includes('form')) return clipboardIcon;
    if (tl.includes('skill') || tl.includes('design') || tl.includes('style') || tl.includes('content')) return sparkleIcon;
    if (tl.includes('log') || tl.includes('diagnos') || tl.includes('test') || tl.includes('check')) return chartIcon;
    if (tl.includes('build') || tl.includes('compile') || tl.includes('optim')) return terminalIcon;
    if (tl.includes('deploy') || tl.includes('integrat') || tl.includes('merge') || tl.includes('layer')) return layersIcon;
    if (t === 'analysis' || t === 'planning') return brainIcon;
    if (t === 'design' || t === 'components' || t === 'content') return sparkleIcon;
    if (t === 'forms' || t === 'integrations') return clipboardIcon;
    if (t === 'optimization' || t === 'testing') return chartIcon;
    if (t === 'deployment') return layersIcon;
    return brainIcon;
}

function detectReactComponentName(code) {
    const source = String(code || '');
    const match = source.match(/(?:function|const)\s+([A-Z][A-Za-z0-9_]*)\s*(?:\(|=)/);
    return match ? match[1] : '';
}

function extractReactThemeFromHtml(html) {
    const source = String(html || '');
    const readValue = (key, fallback) => {
        const match = source.match(new RegExp(`${key}:\\s*['\"]([^'\"]+)['\"]`, 'i'));
        return match && match[1] ? match[1] : fallback;
    };

    return {
        primary: readValue('primary', DEFAULT_REACT_THEME.primary),
        secondary: readValue('secondary', DEFAULT_REACT_THEME.secondary),
        background: readValue('bg', DEFAULT_REACT_THEME.background),
        text: readValue('text', DEFAULT_REACT_THEME.text),
    };
}

function rewritePreviewAssetUrls(html, previewUrl) {
    const source = String(html || '');
    const base = String(previewUrl || '').replace(/\/index\.html(?:\?.*)?$/, '');
    if (!base) return source;

    return source.replace(
        /(src|href)=[\"'][^\"']*\/assets\//gi,
        `$1=\"${base}/assets/`
    );
}

const WORKSPACE_TITLE_STOPWORDS = new Set([
    'features',
    'pricing',
    'customers',
    'faq',
    'privacy',
    'overview',
    'users',
    'data',
    'analytics',
    'domain',
    'domains',
    'logs',
    'api',
    'settings',
    'login',
    'log in',
    'sign up',
    'signup',
    'get started',
    'learn more',
    'task dashboard',
    'workspace home',
    'back to dashboard',
    'terms',
    'twitter',
    'github',
    'linkedin',
    'facebook',
    'instagram',
    'discord',
    'privacy',
    'cookies',
    'blog',
    'docs',
    'support',
    'help',
    'about us',
    'contact us',
    'follow us',
    'copyright',
    'all rights reserved',
]);

function cleanWorkspaceTitle(value) {
    return String(value || '')
        .replace(/\s+/g, ' ')
        .replace(/^["'`]+|["'`]+$/g, '')
        .trim();
}

function isGenericWorkspaceTitle(value) {
    const normalized = cleanWorkspaceTitle(value);
    const lower = normalized.toLowerCase();

    if (!normalized) return true;
    if (normalized.length > 40) return true;
    if (WORKSPACE_TITLE_STOPWORDS.has(lower)) return true;
    if (['generated page', 'untitled app', 'untitled page', 'app', 'page'].includes(lower)) return true;
    if (lower.includes('muaz') && lower.includes('workspace')) return true;
    if (lower.includes('modern saas landing')) return true;
    if (lower.includes('landing page') && normalized.split(/\s+/).length >= 3) return true;

    return false;
}

function isPlaceholderWorkspaceTitle(value) {
    const normalized = cleanWorkspaceTitle(value).toLowerCase();

    return ['', 'generated page', 'untitled app', 'untitled page', 'app', 'page'].includes(normalized);
}

function extractWorkspaceTitleCandidates(source) {
    const text = String(source || '');
    const candidates = [];
    const pushCandidate = (value, fromTitleTag = false) => {
        const normalized = cleanWorkspaceTitle(value);
        if (!isGenericWorkspaceTitle(normalized)) {
            candidates.push({ value: normalized, fromTitleTag });
        }
    };

    for (const match of text.matchAll(/>([^<>{\n][^<>{]{0,48}?)</g)) {
        pushCandidate(match[1], false);
    }

    for (const match of text.matchAll(/<title>([^<]+)<\/title>/gi)) {
        pushCandidate(match[1], true);
    }

    return candidates;
}

function scoreWorkspaceTitleCandidate(value, path, index = 0, fromTitleTag = false) {
    const words = value.split(/\s+/).filter(Boolean).length;
    let score = 0;

    if (fromTitleTag) score += 10;
    if (/header|navbar|layout|app\.jsx|app\.tsx/i.test(path)) score += 6;
    if (index === 0) score += 3;
    if (words === 1) score += 4;
    else if (words <= 3) score += 2;
    if (value.length >= 3 && value.length <= 18) score += 2;
    if (/^[A-Z0-9]/.test(value)) score += 1;
    if (/[A-Z][a-z]+[A-Z]/.test(value)) score += 2;

    return score;
}

function inferWorkspaceTitle(projectFiles) {
    if (!Array.isArray(projectFiles) || projectFiles.length === 0) {
        return '';
    }

    const priorityMatchers = [/header/i, /navbar/i, /layout/i, /app\.jsx$/i, /app\.tsx$/i, /index\.html$/i];
    const files = [...projectFiles]
        .filter((file) => file && typeof file.path === 'string' && typeof file.content === 'string')
        .sort((a, b) => {
            const aPriority = priorityMatchers.findIndex((pattern) => pattern.test(a.path));
            const bPriority = priorityMatchers.findIndex((pattern) => pattern.test(b.path));
            return (aPriority === -1 ? priorityMatchers.length : aPriority) - (bPriority === -1 ? priorityMatchers.length : bPriority);
        });

    let bestMatch = { value: '', score: -1 };

    files.forEach((file) => {
        const candidates = extractWorkspaceTitleCandidates(file.content);
        candidates.forEach((candidate, index) => {
            const score = scoreWorkspaceTitleCandidate(candidate.value, file.path, index, candidate.fromTitleTag);
            if (score > bestMatch.score) {
                bestMatch = { value: candidate.value, score };
            }
        });
    });

    return bestMatch.score > 0 ? bestMatch.value : '';
}

function buildWorkspaceMonogram(value) {
    const normalized = cleanWorkspaceTitle(value);
    if (!normalized) return 'A';

    const parts = normalized.split(/\s+/).filter(Boolean);
    if (parts.length > 1) {
        return parts.slice(0, 2).map((part) => part.charAt(0)).join('').toUpperCase();
    }

    return normalized.charAt(0).toUpperCase();
}

function FileTree({ files, selectedPath, onSelect }) {
    const [expanded, setExpanded] = useState(() => {
        const initial = new Set();
        files.forEach((f) => {
            if (!f || typeof f.path !== 'string') return;
            const parts = f.path.split('/');
            parts.pop();
            let acc = '';
            parts.forEach((part) => {
                acc = acc ? `${acc}/${part}` : part;
                initial.add(acc);
            });
        });
        return initial;
    });

    const toggleFolder = (path) => {
        setExpanded((prev) => {
            const next = new Set(prev);
            if (next.has(path)) {
                next.delete(path);
            } else {
                next.add(path);
            }
            return next;
        });
    };

    const getFileIcon = (node) => {
        const ext = (node.name || '').split('.').pop().toLowerCase();
        const baseColor = 'text-gray-400';

        if (node.type === 'react' || ext === 'jsx' || ext === 'tsx') {
            return (
                <span className={`inline-flex h-4 w-4 items-center justify-center text-[10px] font-bold ${baseColor}`}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                </span>
            );
        }
        if (node.type === 'html' || ext === 'html') {
            return (
                <span className={`inline-flex h-4 w-4 items-center justify-center text-[10px] font-bold ${baseColor}`}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/></svg>
                </span>
            );
        }
        if (node.type === 'json' || ext === 'json') {
            return (
                <span className={`inline-flex h-4 w-4 items-center justify-center text-[10px] font-bold ${baseColor}`}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
            );
        }
        if (ext === 'js' || ext === 'mjs' || ext === 'ts') {
            return (
                <span className={`inline-flex h-4 w-4 items-center justify-center text-[10px] font-bold ${baseColor}`}>
                    JS
                </span>
            );
        }
        if (ext === 'css') {
            return (
                <span className={`inline-flex h-4 w-4 items-center justify-center text-[10px] font-bold ${baseColor}`}>
                    #
                </span>
            );
        }
        if (ext === 'md') {
            return (
                <span className={`inline-flex h-4 w-4 items-center justify-center text-[10px] font-bold ${baseColor}`}>
                    M
                </span>
            );
        }
        return (
            <span className={`inline-flex h-4 w-4 items-center justify-center ${baseColor}`}>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </span>
        );
    };

    const getFolderIcon = (isOpen) => (
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className={`transition-transform text-gray-400 ${isOpen ? 'rotate-90' : ''}`}><polyline points="9 18 15 12 9 6"/></svg>
    );

    const getFolderClosedIcon = () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-500"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
    );

    const getFolderOpenIcon = () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-500"><path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2"/></svg>
    );

    const buildTree = (fileList) => {
        const root = { name: '', path: '', type: 'folder', children: [] };
        const map = { '': root };

        fileList.forEach((file) => {
            if (!file || typeof file.path !== 'string') return;
            const parts = file.path.split('/');
            const fileName = parts.pop();
            let currentPath = '';
            let parent = root;

            parts.forEach((part) => {
                currentPath = currentPath ? `${currentPath}/${part}` : part;
                if (!map[currentPath]) {
                    const folder = { name: part, path: currentPath, type: 'folder', children: [] };
                    map[currentPath] = folder;
                    parent.children.push(folder);
                }
                parent = map[currentPath];
            });

            parent.children.push({
                name: fileName,
                path: file.path,
                type: file.type || 'file',
                content: file.content,
                label: file.label || fileName,
            });
        });

        return root.children;
    };

    const tree = buildTree(files);

    const INDENT = 18;
    const LINE_OFFSET = 7;

    const renderNode = (node, depth = 0) => {
        const isSelected = node.path === selectedPath;

        if (node.type === 'folder') {
            const isOpen = expanded.has(node.path);
            return (
                <div key={node.path} className="relative">
                    {depth > 0 && (
                        <span
                            className="absolute border-b border-gray-200"
                            style={{ left: `${12 + (depth - 1) * INDENT + LINE_OFFSET}px`, width: `${INDENT}px`, top: '14px' }}
                        />
                    )}
                    <button
                        type="button"
                        onClick={() => toggleFolder(node.path)}
                        className="relative z-10 w-full flex items-center gap-1.5 py-1.5 pr-2 text-left text-[12px] text-gray-700 hover:bg-gray-100 transition-colors"
                        style={{ paddingLeft: `${12 + depth * INDENT}px` }}
                    >
                        <span className="inline-flex h-4 w-4 items-center justify-center">{getFolderIcon(isOpen)}</span>
                        <span className="inline-flex h-4 w-4 items-center justify-center">{isOpen ? getFolderOpenIcon() : getFolderClosedIcon()}</span>
                        <span className="font-medium">{node.name}</span>
                    </button>
                    {isOpen && (
                        <div className="relative">
                            <span
                                className="absolute border-l border-gray-200"
                                style={{ left: `${12 + depth * INDENT + LINE_OFFSET}px`, top: 0, bottom: 0 }}
                            />
                            {node.children.map((child) => renderNode(child, depth + 1))}
                        </div>
                    )}
                </div>
            );
        }

        return (
            <div key={node.path} className="relative">
                {depth > 0 && (
                    <span
                        className="absolute border-b border-gray-200"
                        style={{ left: `${12 + (depth - 1) * INDENT + LINE_OFFSET}px`, width: `${INDENT}px`, top: '14px' }}
                    />
                )}
                <button
                    type="button"
                    onClick={() => onSelect(node.path)}
                    className={`relative z-10 w-full flex items-center gap-2 py-1.5 pr-2 text-left transition-colors ${isSelected ? 'bg-white border-r-2 border-r-[#2563eb]' : 'hover:bg-white/60'}`}
                    style={{ paddingLeft: `${12 + depth * INDENT}px` }}
                >
                    <span className="inline-flex h-4 w-4 items-center justify-center">{getFileIcon(node)}</span>
                    <span className={`text-[12px] truncate ${isSelected ? 'font-semibold text-[#2563eb]' : 'text-gray-900'}`}>
                        {node.label}
                    </span>
                </button>
            </div>
        );
    };

    return (
        <div className="py-3 select-none">
            <div className="px-4 mb-2 text-[12px] uppercase tracking-[0.2em] text-gray-400">Project files</div>
            <div>
                {tree.map((node) => renderNode(node))}
            </div>
        </div>
    );
}

function buildReactErrorDocument(message, theme = DEFAULT_REACT_THEME, title = 'React Preview') {
    const safeTitle = escapeHtml(title);
    const safeMessage = escapeHtml(message || 'Unable to render generated React code.');

    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>${safeTitle}</title>
    <style>
        :root {
            color-scheme: light;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: ${theme.background || DEFAULT_REACT_THEME.background};
            color: ${theme.text || DEFAULT_REACT_THEME.text};
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .mailpurse-react-error {
            width: min(640px, 100%);
            border-radius: 20px;
            border: 1px solid rgba(239, 68, 68, 0.18);
            background: rgba(255,255,255,0.92);
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12);
            padding: 24px;
        }
        .mailpurse-react-error strong {
            display: block;
            font-size: 14px;
            margin-bottom: 8px;
            color: #b91c1c;
        }
        .mailpurse-react-error code {
            display: inline-block;
            margin-top: 8px;
            border-radius: 999px;
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="mailpurse-react-error">
        <strong>Preview unavailable</strong>
        <div>${safeMessage}</div>
        <code>Define a top-level React component like const LandingPage = () =&gt; (...)</code>
    </div>
</body>
</html>`;
}

function buildReactPreviewDocument(code, theme = DEFAULT_REACT_THEME, title = 'React Preview') {
    const componentName = detectReactComponentName(code);
    if (!componentName) {
        return buildReactErrorDocument('We could not find a React component name in the current code.', theme, title);
    }

    const escapedCode = escapeClosingScriptTags(code);
    const componentNameJson = JSON.stringify(componentName);
    const safeTitle = escapeHtml(title);
    const primary = theme.primary || DEFAULT_REACT_THEME.primary;
    const secondary = theme.secondary || DEFAULT_REACT_THEME.secondary;
    const background = theme.background || DEFAULT_REACT_THEME.background;
    const text = theme.text || DEFAULT_REACT_THEME.text;

    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>${safeTitle}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: '${primary}',
                            secondary: '${secondary}',
                            bg: '${background}',
                            text: '${text}',
                        }
                    }
                }
            }
        };
        window.__MAILPURSE_AI_REACT_PREVIEW_ERROR__ = function (message) {
            var root = document.getElementById('mailpurse-ai-react-root');
            if (!root) return;
            var escaped = String(message || 'Unable to render generated component.')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            root.innerHTML = '<div class="mailpurse-ai-react-error"><strong>Preview error:</strong> ' + escaped + '</div>';
        };
        window.addEventListener('error', function (event) {
            if (event && event.message) {
                window.__MAILPURSE_AI_REACT_PREVIEW_ERROR__(event.message);
            }
        });
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <style>
        html, body { margin: 0; min-height: 100%; background: ${background}; }
        .mailpurse-ai-react-root, .mailpurse-ai-react-root * { box-sizing: border-box; }
        .mailpurse-ai-react-root { min-height: 100vh; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .bg-brand-primary { background-color: ${primary} !important; }
        .text-brand-primary { color: ${primary} !important; }
        .bg-brand-secondary { background-color: ${secondary} !important; }
        .text-brand-secondary { color: ${secondary} !important; }
        .bg-brand-bg { background-color: ${background} !important; }
        .text-brand-text { color: ${text} !important; }
        .mailpurse-ai-react-error { margin: 16px; border-radius: 12px; border: 1px solid #fecaca; background: #fef2f2; padding: 16px; color: #b91c1c; font: 14px/1.5 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body>
    <div id="mailpurse-ai-react-root" class="mailpurse-ai-react-root"></div>
    <script type="text/babel" data-presets="react">
const { Fragment, useMemo, useRef, useState } = React;

${escapedCode}

try {
    const Component = typeof window[${componentNameJson}] !== 'undefined'
        ? window[${componentNameJson}]
        : (typeof ${componentName} !== 'undefined' ? ${componentName} : null);

    if (!Component) {
        throw new Error('Generated React component could not be resolved.');
    }

    ReactDOM.createRoot(document.getElementById('mailpurse-ai-react-root')).render(<Component />);
} catch (error) {
    window.__MAILPURSE_AI_REACT_PREVIEW_ERROR__(error && error.message ? error.message : 'Unable to render generated component.');
}
    </script>
</body>
</html>`;
}

const PREVIEW_SELECTION_SCRIPT = `
(function() {
    if (window.__mailpurseSelectionScriptLoaded) return;
    window.__mailpurseSelectionScriptLoaded = true;

    const selectedSet = new Set();
    let highlightBox = null;
    let tagBadge = null;
    let hoveredEl = null;

    function removeOverlay() {
        if (highlightBox) { highlightBox.remove(); highlightBox = null; }
        if (tagBadge) { tagBadge.remove(); tagBadge = null; }
    }

    function createOverlay(rect, tag) {
        removeOverlay();
        const box = document.createElement('div');
        box.className = 'mailpurse-selection-overlay';
        box.style.position = 'fixed';
        box.style.left = rect.left + 'px';
        box.style.top = rect.top + 'px';
        box.style.width = rect.width + 'px';
        box.style.height = rect.height + 'px';
        box.style.border = '1px solid #2563eb';
        box.style.backgroundColor = 'rgba(37, 99, 235, 0.08)';
        box.style.pointerEvents = 'none';
        box.style.zIndex = '999999';
        box.style.boxSizing = 'border-box';
        document.body.appendChild(box);
        highlightBox = box;

        const badge = document.createElement('div');
        badge.className = 'mailpurse-selection-badge';
        badge.textContent = tag;
        badge.style.position = 'fixed';
        badge.style.left = rect.left + 'px';
        badge.style.top = (rect.top - 24) + 'px';
        badge.style.backgroundColor = '#2563eb';
        badge.style.color = '#fff';
        badge.style.padding = '2px 8px';
        badge.style.borderRadius = '999px';
        badge.style.fontSize = '12px';
        badge.style.fontFamily = 'sans-serif';
        badge.style.pointerEvents = 'none';
        badge.style.zIndex = '999999';
        document.body.appendChild(badge);
        tagBadge = badge;
    }

    function updateSelectedVisuals(el) {
        if (selectedSet.has(el)) {
            el.style.outline = '1px solid #2563eb';
            el.style.outlineOffset = '0px';
            el.style.backgroundColor = 'rgba(37, 99, 235, 0.08)';
            el.classList.add('mailpurse-selected');
        } else {
            el.style.outline = '';
            el.style.outlineOffset = '';
            el.style.backgroundColor = '';
            el.classList.remove('mailpurse-selected');
        }
    }

    function addSelected(el) {
        selectedSet.add(el);
        updateSelectedVisuals(el);
    }

    function removeSelected(el) {
        selectedSet.delete(el);
        updateSelectedVisuals(el);
    }

    function clearSelection() {
        const arr = Array.from(selectedSet);
        selectedSet.clear();
        arr.forEach(updateSelectedVisuals);
        removeOverlay();
    }

    function getSelector(el) {
        const path = [];
        while (el && el !== document.body && el !== document.documentElement) {
            const tag = el.tagName.toLowerCase();
            const siblings = Array.from(el.parentNode?.children || []).filter((c) => c.tagName === el.tagName);
            const index = siblings.indexOf(el) + 1;
            path.unshift(tag + ':nth-of-type(' + index + ')');
            el = el.parentElement;
        }
        return path.join(' > ');
    }

    function getInfo(el) {
        return {
            tag: el.tagName.toLowerCase(),
            text: el.textContent.trim().slice(0, 200),
            path: getSelector(el),
            classes: Array.from(el.classList).join(' '),
        };
    }

    function findElementByPath(path) {
        try {
            return document.querySelector(path) || null;
        } catch (e) {
            return null;
        }
    }

    function applySelection(paths) {
        clearSelection();
        if (!Array.isArray(paths)) return;
        paths.forEach((path) => {
            const el = findElementByPath(path);
            if (el) addSelected(el);
        });
    }

    document.addEventListener('mouseover', function(e) {
        if (!window.__mailpurseSelectionMode) return;
        const target = e.target;
        if (target === document.body || target === document.documentElement || target.classList.contains('mailpurse-selection-overlay') || target.classList.contains('mailpurse-selection-badge')) return;
        hoveredEl = target;
        const rect = target.getBoundingClientRect();
        createOverlay(rect, target.tagName.toLowerCase());
    });

    document.addEventListener('mouseout', function(e) {
        if (!window.__mailpurseSelectionMode) return;
        hoveredEl = null;
        removeOverlay();
    });

    document.addEventListener('click', function(e) {
        if (!window.__mailpurseSelectionMode) return;
        const target = e.target;
        if (target.classList.contains('mailpurse-selection-overlay') || target.classList.contains('mailpurse-selection-badge')) return;
        if (target === document.body || target === document.documentElement) return;
        e.preventDefault();
        e.stopPropagation();
        const multi = e.ctrlKey || e.metaKey;
        if (!multi) clearSelection();
        if (selectedSet.has(target)) {
            removeSelected(target);
        } else {
            addSelected(target);
        }
        const selected = Array.from(selectedSet).map(getInfo);
        window.parent.postMessage({ type: 'MAILPURSE_ELEMENT_SELECT', selected }, '*');
    }, true);

    window.addEventListener('message', function(e) {
        if (e.data?.type === 'MAILPURSE_CLEAR_SELECTION') clearSelection();
        if (e.data?.type === 'MAILPURSE_APPLY_SELECTION') applySelection(e.data.paths);
    });

    window.addEventListener('scroll', function() {
        if (!window.__mailpurseSelectionMode || !hoveredEl) return;
        const rect = hoveredEl.getBoundingClientRect();
        createOverlay(rect, hoveredEl.tagName.toLowerCase());
    }, true);

    window.__mailpurseRemoveSelectionOverlay = removeOverlay;
    window.__mailpurseClearSelection = clearSelection;
    window.__mailpurseApplySelection = applySelection;
})();
`.trim();

const PREVIEW_INLINE_EDIT_SCRIPT = `
(function() {
    if (window.__mailpurseEditScriptLoaded) return;
    window.__mailpurseEditScriptLoaded = true;

    let editTarget = null;
    let hoverBox = null;
    let hoverBadge = null;
    let selectBox = null;
    let selectBadge = null;
    let hoverEl = null;

    function removeHover() {
        if (hoverBox) { hoverBox.remove(); hoverBox = null; }
        if (hoverBadge) { hoverBadge.remove(); hoverBadge = null; }
    }

    function removeSelection() {
        if (selectBox) { selectBox.remove(); selectBox = null; }
        if (selectBadge) { selectBadge.remove(); selectBadge = null; }
    }

    function getSelector(el) {
        const path = [];
        while (el && el !== document.body && el !== document.documentElement) {
            const tag = el.tagName.toLowerCase();
            const siblings = Array.from(el.parentNode?.children || []).filter((c) => c.tagName === el.tagName);
            const index = siblings.indexOf(el) + 1;
            path.unshift(tag + ':nth-of-type(' + index + ')');
            el = el.parentElement;
        }
        return path.join(' > ');
    }

    function getInfo(el) {
        const tag = el.tagName.toLowerCase();
        const isMedia = ['img', 'svg', 'video', 'picture', 'canvas', 'figure'].includes(tag);
        const isText = !isMedia && el.childNodes.length > 0 && Array.from(el.childNodes).some((n) => n.nodeType === 3 && n.textContent.trim());
        return {
            tag,
            text: el.textContent.trim().slice(0, 100),
            path: getSelector(el),
            isMedia,
            isText,
            href: tag === 'a' ? (el.getAttribute('href') || '') : '',
            linkTarget: tag === 'a' ? (el.getAttribute('target') || '_self') : '_self',
            src: tag === 'img' ? (el.getAttribute('src') || '') : '',
            classes: Array.from(el.classList).join(' '),
        };
    }

    function getRect(el) {
        const rect = el.getBoundingClientRect();
        return { left: rect.left, top: rect.top, width: rect.width, height: rect.height };
    }

    function getStaticSnapshot() {
        const root = document.documentElement.cloneNode(true);
        root.querySelectorAll('script, .mailpurse-edit-hover, .mailpurse-edit-badge, .mailpurse-edit-selection, .mailpurse-edit-selection-badge, .mailpurse-selection-overlay, .mailpurse-selection-badge').forEach((node) => node.remove());
        const head = root.querySelector('head');
        if (head) {
            let base = head.querySelector('base');
            if (!base) {
                base = document.createElement('base');
                base.href = window.location.href;
                head.prepend(base);
            }
        }
        const doctype = document.doctype ? '<!DOCTYPE ' + document.doctype.name + '>' : '<!DOCTYPE html>';
        return doctype + root.outerHTML;
    }

    function createHoverOverlay(rect, tag) {
        removeHover();
        const box = document.createElement('div');
        box.className = 'mailpurse-edit-hover';
        box.style.position = 'fixed';
        box.style.left = rect.left + 'px';
        box.style.top = rect.top + 'px';
        box.style.width = rect.width + 'px';
        box.style.height = rect.height + 'px';
        box.style.border = '1px solid #93c5fd';
        box.style.backgroundColor = 'rgba(147, 197, 253, 0.08)';
        box.style.pointerEvents = 'none';
        box.style.zIndex = '999998';
        box.style.boxSizing = 'border-box';
        document.body.appendChild(box);
        hoverBox = box;

        const badge = document.createElement('div');
        badge.className = 'mailpurse-edit-badge';
        badge.textContent = tag;
        badge.style.position = 'fixed';
        badge.style.left = rect.left + 'px';
        badge.style.top = (rect.top - 32) + 'px';
        badge.style.backgroundColor = '#dbeafe';
        badge.style.color = '#2563eb';
        badge.style.padding = '4px 10px';
        badge.style.borderRadius = '6px';
        badge.style.fontSize = '12px';
        badge.style.fontWeight = '600';
        badge.style.fontFamily = 'sans-serif';
        badge.style.pointerEvents = 'none';
        badge.style.zIndex = '999999';
        badge.style.border = '1px solid #bfdbfe';
        badge.style.boxShadow = 'none';
        document.body.appendChild(badge);
        hoverBadge = badge;
    }

    function createSelectionOverlay(rect, tag) {
        removeSelection();
        const box = document.createElement('div');
        box.className = 'mailpurse-edit-selection';
        box.style.position = 'fixed';
        box.style.left = rect.left + 'px';
        box.style.top = rect.top + 'px';
        box.style.width = rect.width + 'px';
        box.style.height = rect.height + 'px';
        box.style.border = '1px solid #2563eb';
        box.style.backgroundColor = 'rgba(37, 99, 235, 0.06)';
        box.style.pointerEvents = 'none';
        box.style.zIndex = '999998';
        box.style.boxSizing = 'border-box';
        document.body.appendChild(box);
        selectBox = box;

        const badge = document.createElement('div');
        badge.className = 'mailpurse-edit-selection-badge';
        badge.textContent = tag;
        badge.style.position = 'fixed';
        badge.style.left = rect.left + 'px';
        badge.style.top = (rect.top - 32) + 'px';
        badge.style.backgroundColor = '#2563eb';
        badge.style.color = '#fff';
        badge.style.padding = '4px 10px';
        badge.style.borderRadius = '6px';
        badge.style.fontSize = '12px';
        badge.style.fontWeight = '600';
        badge.style.fontFamily = 'sans-serif';
        badge.style.pointerEvents = 'none';
        badge.style.zIndex = '999999';
        badge.style.border = '1px solid #2563eb';
        badge.style.boxShadow = 'none';
        document.body.appendChild(badge);
        selectBadge = badge;
    }

    function updateSelection() {
        if (!editTarget || !selectBox) return;
        const rect = editTarget.getBoundingClientRect();
        const tag = editTarget.tagName.toLowerCase();
        selectBox.style.left = rect.left + 'px';
        selectBox.style.top = rect.top + 'px';
        selectBox.style.width = rect.width + 'px';
        selectBox.style.height = rect.height + 'px';
        selectBadge.style.left = rect.left + 'px';
        selectBadge.style.top = (rect.top - 32) + 'px';
        selectBadge.textContent = tag;
    }

    function loadGoogleFont(family) {
        const key = family.replace(/\\s+/g, '-').toLowerCase();
        if (document.getElementById('mailpurse-font-' + key)) return;
        const link = document.createElement('link');
        link.id = 'mailpurse-font-' + key;
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(family) + ':wght@300;400;500;600;700&display=swap';
        document.head.appendChild(link);
    }

    function applyStyle(prop, value) {
        if (!editTarget) return;
        if (prop === 'fontFamily') loadGoogleFont(value);
        editTarget.style[prop] = value;
        updateSelection();
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_RECT', rect: getRect(editTarget) }, '*');
    }

    function applyStyles(styles) {
        if (!editTarget) return;
        Object.entries(styles).forEach(([prop, value]) => {
            if (prop === 'fontFamily') loadGoogleFont(value);
            editTarget.style[prop] = value;
        });
        updateSelection();
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_RECT', rect: getRect(editTarget) }, '*');
    }

    function applyStyleOverrides(overrides) {
        Object.entries(overrides || {}).forEach(([path, styles]) => {
            const el = document.querySelector(path);
            if (!el) return;
            Object.entries(styles).forEach(([prop, value]) => {
                if (prop === 'fontFamily') loadGoogleFont(value);
                el.style[prop] = value;
            });
        });
        if (editTarget) updateSelection();
    }

    function setLink(url, target) {
        if (!editTarget) return;
        const oldPath = getSelector(editTarget);
        const safeTarget = target === '_blank' ? '_blank' : '_self';
        if (editTarget.tagName.toLowerCase() === 'button') {
            const link = document.createElement('a');
            Array.from(editTarget.attributes).forEach((attribute) => {
                if (attribute.name !== 'type') link.setAttribute(attribute.name, attribute.value);
            });
            link.innerHTML = editTarget.innerHTML;
            editTarget.replaceWith(link);
            editTarget = link;
        }
        if (editTarget.tagName.toLowerCase() !== 'a') return;
        if (url) editTarget.setAttribute('href', url); else editTarget.removeAttribute('href');
        editTarget.setAttribute('target', safeTarget);
        if (safeTarget === '_blank') editTarget.setAttribute('rel', 'noopener noreferrer'); else editTarget.removeAttribute('rel');
        updateSelection();
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_LINK_CHANGED', path: oldPath, outerHTML: editTarget.outerHTML, info: getInfo(editTarget), rect: getRect(editTarget), snapshot: getStaticSnapshot() }, '*');
    }

    function setImageSource(src) {
        if (!editTarget || editTarget.tagName.toLowerCase() !== 'img' || !src) return;
        const oldSrc = editTarget.getAttribute('src');
        editTarget.setAttribute('src', src);
        updateSelection();
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_IMAGE_CHANGED', path: getSelector(editTarget), src, oldSrc, rect: getRect(editTarget), snapshot: getStaticSnapshot() }, '*');
    }

    function replaceWithImage(src) {
        if (!editTarget || !src) return;
        const oldPath = getSelector(editTarget);
        var origRect = editTarget.getBoundingClientRect();
        var origHeight = origRect.height > 0 ? Math.round(origRect.height) : 40;
        const image = document.createElement('img');
        image.src = src;
        image.alt = editTarget.getAttribute('aria-label') || editTarget.textContent.trim() || 'Logo';
        image.style.height = origHeight + 'px';
        image.style.width = 'auto';
        image.style.maxHeight = '100%';
        image.style.maxWidth = '100%';
        image.style.objectFit = 'contain';
        image.style.display = 'inline-block';
        image.style.verticalAlign = 'middle';
        const wrapper = editTarget.tagName.toLowerCase() === 'a' ? editTarget : null;
        if (wrapper) {
            wrapper.innerHTML = '';
            wrapper.appendChild(image);
            editTarget = image;
        } else {
            editTarget.replaceWith(image);
            editTarget = image;
        }
        createSelectionOverlay(getRect(editTarget), 'img');
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_REPLACED_WITH_IMAGE', path: oldPath, outerHTML: wrapper ? wrapper.outerHTML : image.outerHTML, info: getInfo(editTarget), rect: getRect(editTarget), snapshot: getStaticSnapshot() }, '*');
    }

    function replaceWithSvg(markup) {
        if (!editTarget || !markup) return;
        const parser = new DOMParser();
        const parsed = parser.parseFromString(markup, 'image/svg+xml');
        const svg = parsed.documentElement;
        if (!svg || svg.tagName.toLowerCase() !== 'svg' || parsed.querySelector('parsererror')) return;
        const oldPath = getSelector(editTarget);
        const origRect = editTarget.getBoundingClientRect();
        const origHeight = origRect.height > 0 ? Math.round(origRect.height) : 40;
        const svgElement = document.importNode(svg, true);
        svgElement.style.height = origHeight + 'px';
        svgElement.style.width = 'auto';
        svgElement.style.maxHeight = '100%';
        svgElement.style.maxWidth = '100%';
        svgElement.style.display = 'inline-block';
        svgElement.style.verticalAlign = 'middle';
        const wrapper = editTarget.tagName.toLowerCase() === 'a' ? editTarget : null;
        if (wrapper) {
            wrapper.innerHTML = '';
            wrapper.appendChild(svgElement);
            editTarget = svgElement;
        } else {
            editTarget.replaceWith(svgElement);
            editTarget = svgElement;
        }
        createSelectionOverlay(getRect(editTarget), 'svg');
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_REPLACED_WITH_SVG', path: oldPath, outerHTML: wrapper ? wrapper.outerHTML : svgElement.outerHTML, info: getInfo(editTarget), rect: getRect(editTarget), snapshot: getStaticSnapshot() }, '*');
    }

    function finishTextEdit(el) {
        if (!el) return;
        el.contentEditable = false;
        el.removeEventListener('blur', onEditBlur);
        el.removeEventListener('keydown', onEditKeydown);
        const info = getInfo(el);
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_TEXT_CHANGED', path: info.path, innerHTML: el.innerHTML, snapshot: getStaticSnapshot() }, '*');
    }

    function onEditBlur(e) {
        finishTextEdit(e.target);
    }

    function onEditKeydown(e) {
        const el = e.target;
        if (e.key === 'Enter') {
            const tag = el.tagName.toLowerCase();
            if (tag !== 'textarea' && !el.dataset.allowEnter) {
                e.preventDefault();
                el.blur();
            }
        }
    }

    function selectElement(el) {
        if (editTarget && editTarget !== el && editTarget.isContentEditable) {
            finishTextEdit(editTarget);
        }
        editTarget = el;
        createSelectionOverlay(getRect(el), el.tagName.toLowerCase());
        const info = getInfo(el);
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_TARGET', info, rect: getRect(el) }, '*');
        if (info.isText) {
            el.contentEditable = true;
            el.focus();
            el.addEventListener('blur', onEditBlur);
            el.addEventListener('keydown', onEditKeydown);
        }
    }

    function deleteElement() {
        if (!editTarget) return;
        editTarget.remove();
        removeHover();
        removeSelection();
        editTarget = null;
        window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_DELETED', snapshot: getStaticSnapshot() }, '*');
    }

    function clearEdit() {
        if (editTarget && editTarget.isContentEditable) {
            finishTextEdit(editTarget);
        }
        editTarget = null;
        removeHover();
        removeSelection();
    }

    document.addEventListener('mouseover', function(e) {
        if (!window.__mailpurseInlineEditMode) return;
        const target = e.target;
        if (target === document.body || target === document.documentElement ||
            target.classList.contains('mailpurse-edit-hover') ||
            target.classList.contains('mailpurse-edit-badge') ||
            target.classList.contains('mailpurse-edit-selection') ||
            target.classList.contains('mailpurse-edit-selection-badge')) return;
        hoverEl = target;
        createHoverOverlay(target.getBoundingClientRect(), target.tagName.toLowerCase());
    });

    document.addEventListener('mouseout', function(e) {
        if (!window.__mailpurseInlineEditMode) return;
        hoverEl = null;
        removeHover();
    });

    document.addEventListener('click', function(e) {
        if (!window.__mailpurseInlineEditMode) return;
        const target = e.target;
        if (target.classList.contains('mailpurse-edit-hover') ||
            target.classList.contains('mailpurse-edit-badge') ||
            target.classList.contains('mailpurse-edit-selection') ||
            target.classList.contains('mailpurse-edit-selection-badge')) return;
        if (target === document.body || target === document.documentElement) return;
        e.preventDefault();
        e.stopPropagation();
        selectElement(target);
    }, true);

    window.addEventListener('message', function(e) {
        if (e.data?.type === 'MAILPURSE_APPLY_STYLE') applyStyle(e.data.prop, e.data.value);
        if (e.data?.type === 'MAILPURSE_APPLY_STYLES') applyStyles(e.data.styles);
        if (e.data?.type === 'MAILPURSE_APPLY_STYLE_OVERRIDES') applyStyleOverrides(e.data.overrides);
        if (e.data?.type === 'MAILPURSE_SET_LINK') setLink(e.data.url, e.data.target);
        if (e.data?.type === 'MAILPURSE_SET_IMAGE_SOURCE') setImageSource(e.data.src);
        if (e.data?.type === 'MAILPURSE_REPLACE_WITH_IMAGE') replaceWithImage(e.data.src);
        if (e.data?.type === 'MAILPURSE_REPLACE_WITH_SVG') replaceWithSvg(e.data.svg);
        if (e.data?.type === 'MAILPURSE_DELETE_ELEMENT') deleteElement();
        if (e.data?.type === 'MAILPURSE_CLEAR_INLINE_EDIT') clearEdit();
    });

    window.addEventListener('scroll', function() {
        if (window.__mailpurseInlineEditMode && editTarget) {
            updateSelection();
            window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_RECT', rect: getRect(editTarget) }, '*');
        }
        if (window.__mailpurseInlineEditMode && hoverEl) {
            createHoverOverlay(hoverEl.getBoundingClientRect(), hoverEl.tagName.toLowerCase());
        }
    }, true);

    window.addEventListener('resize', function() {
        if (window.__mailpurseInlineEditMode && editTarget) {
            updateSelection();
            window.parent.postMessage({ type: 'MAILPURSE_INLINE_EDIT_RECT', rect: getRect(editTarget) }, '*');
        }
    });

    window.__mailpurseSetInlineEditMode = (active) => {
        window.__mailpurseInlineEditMode = active;
        if (!active) clearEdit();
    };
})();
`.trim();


function PageAiBuilder() {
    const mountEl = useRef(null);
    const generateUrl = mountEl.current?.dataset?.generateUrl || '';
    const saveUrl = mountEl.current?.dataset?.saveUrl || '';
    const pagesIndexUrl = mountEl.current?.dataset?.pagesIndexUrl || '';

    const [prompt, setPrompt] = useState(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const initial = params.get('prompt');
            if (initial && initial.trim() !== '') {
                params.delete('prompt');
                const rest = params.toString();
                const cleanUrl = window.location.pathname + (rest ? `?${rest}` : '') + window.location.hash;
                window.history.replaceState({}, '', cleanUrl);
                return initial;
            }
        } catch (e) {}
        return '';
    });
    const [screenshot, setScreenshot] = useState(null);
    const [screenshotPreviewUrl, setScreenshotPreviewUrl] = useState(null);
    const [model, setModel] = useState(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const m = params.get('model');
            if (m && m.trim() !== '' && m !== 'auto') {
                params.delete('model');
                const rest = params.toString();
                const cleanUrl = window.location.pathname + (rest ? `?${rest}` : '') + window.location.hash;
                window.history.replaceState({}, '', cleanUrl);
                return m;
            }
        } catch (e) {}
        return 'auto';
    });
    const [selectedThemeId, setSelectedThemeId] = useState('default');
    const [initialDiscussMode] = useState(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const mode = params.get('mode');
            if (mode === 'discuss') {
                params.delete('mode');
                const rest = params.toString();
                const cleanUrl = window.location.pathname + (rest ? `?${rest}` : '') + window.location.hash;
                window.history.replaceState({}, '', cleanUrl);
                return true;
            }
        } catch (e) {}
        return false;
    });
    const [shouldAutoStart] = useState(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const auto = params.get('auto');
            if (auto === '1' || auto === 'true' || auto === 'yes') {
                params.delete('auto');
                const rest = params.toString();
                const cleanUrl = window.location.pathname + (rest ? `?${rest}` : '') + window.location.hash;
                window.history.replaceState({}, '', cleanUrl);
                return true;
            }
        } catch (e) {}
        return false;
    });
    const [themeMenuOpen, setThemeMenuOpen] = useState(false);
    const themeMenuRef = useRef(null);
    const [providerModels, setProviderModels] = useState(() => {
        try {
            const el = document.getElementById('page-ai-builder-react');
            const raw = el?.dataset?.providerModels;
            if (raw && raw !== 'null') return JSON.parse(raw);
        } catch (e) {}
        return {};
    });
    const [aiBuilderCapabilities, setAiBuilderCapabilities] = useState(() => {
        try {
            const el = document.getElementById('page-ai-builder-react');
            const raw = el?.dataset?.aiBuilderCapabilities;
            if (raw && raw !== 'null') return JSON.parse(raw);
        } catch (e) {}
        return { enabledModels: [], permissions: {} };
    });
    const [aiBuilderCreditStatus, setAiBuilderCreditStatus] = useState(() => {
        try {
            const el = document.getElementById('page-ai-builder-react');
            const raw = el?.dataset?.aiBuilderCreditStatus;
            if (raw && raw !== 'null') return JSON.parse(raw);
        } catch (e) {}
        return {};
    });
    const aiBuilderPermissions = aiBuilderCapabilities?.permissions || {};
    const enabledModels = aiBuilderCapabilities?.enabledModels || [];
    const selectedTheme = useMemo(() => getThemePreset(selectedThemeId), [selectedThemeId]);

    useEffect(() => {
        const handlePointerDown = (event) => {
            const themeRoot = themeMenuRef.current;
            if (themeRoot && !themeRoot.contains(event.target)) {
                setThemeMenuOpen(false);
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                setThemeMenuOpen(false);
            }
        };

        document.addEventListener('pointerdown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('pointerdown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, []);

    const handleThemeSelect = useCallback((themeId) => {
        const resolvedTheme = getThemePreset(themeId);
        const resolvedThemeId = resolvedTheme?.id || 'default';
        setSelectedThemeId(resolvedThemeId);
        setPrompt((current) => mergeThemePromptIntoPrompt(current, resolvedThemeId));
        setThemeMenuOpen(false);
    }, []);

    useEffect(() => {
        if (model !== 'auto' && enabledModels.length > 0 && !enabledModels.includes(model)) {
            setModel('auto');
        }
    }, [model, enabledModels]);

    const [status, setStatus] = useState('idle');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [messages, setMessages] = useState([]);
    const messagesRef = useRef([]);
    const [progressData, setProgressData] = useState(null);
    const [dynamicEvents, setDynamicEvents] = useState(null);
    const dynamicEventsRef = useRef(null);
    const [previewHtml, setPreviewHtml] = useState('');
    const previewHtmlRef = useRef('');
    const inlineEditsActiveRef = useRef(false);
    const [inlineEditVersion, setInlineEditVersion] = useState(0);
    const [reactCode, setReactCode] = useState('');
    const [generatedReactCode, setGeneratedReactCode] = useState('');
    const [previewMode, setPreviewMode] = useState('html');
    const [previewTitle, setPreviewTitle] = useState('');
    const [previewTheme, setPreviewTheme] = useState(DEFAULT_REACT_THEME);
    const [previewUrl, setPreviewUrl] = useState('');
    const [projectFiles, setProjectFiles] = useState([]);
    const [buildLog, setBuildLog] = useState('');
    const [isCompilingSandbox, setIsCompilingSandbox] = useState(false);
    const [showPreview, setShowPreview] = useState(false);
    const [saving, setSaving] = useState(false);
    const [saveStatus, setSaveStatus] = useState('idle');
    const [pageTitle, setPageTitle] = useState('');
    const [pageSlug, setPageSlug] = useState('');
    const [pageStatus, setPageStatus] = useState('draft');
    const [appMetaTitle, setAppMetaTitle] = useState('');
    const [appMetaLogo, setAppMetaLogo] = useState('');
    const [appMetaIcon, setAppMetaIcon] = useState('');
    const [appMetaDescription, setAppMetaDescription] = useState('');
    const [appMetaSocialImage, setAppMetaSocialImage] = useState('');
    const [appMetaSaveStatus, setAppMetaSaveStatus] = useState('idle');
    const [saveSuccess, setSaveSuccess] = useState(null);
    const [initialPageId, setInitialPageId] = useState(null);
    const [activeRightTab, setActiveRightTab] = useState('preview');
    const activeRightTabRef = useRef('preview');
    activeRightTabRef.current = activeRightTab;
    const [currentUserRole, setCurrentUserRole] = useState(null);
    const isViewer = currentUserRole === 'viewer';
    const [selectedFilePath, setSelectedFilePath] = useState('');
    const [openFileTabs, setOpenFileTabs] = useState([]);
    const [activeFileTabPath, setActiveFileTabPath] = useState('');
    const [generationPhase, setGenerationPhase] = useState(null);
    const [publishMenuOpen, setPublishMenuOpen] = useState(false);
    const publishMenuRef = useRef(null);
    const [shareMenuOpen, setShareMenuOpen] = useState(false);
    const shareMenuRef = useRef(null);
    const [shareModalShares, setShareModalShares] = useState([]);
    const [shareModalOwner, setShareModalOwner] = useState(null);
    const [shareModalIsOwner, setShareModalIsOwner] = useState(false);
    const [shareModalInviteLink, setShareModalInviteLink] = useState('');
    const [shareModalInviteEmail, setShareModalInviteEmail] = useState('');
    const [shareModalInviteRole, setShareModalInviteRole] = useState('viewer');
    const [shareModalLoading, setShareModalLoading] = useState(false);
    const [appVisibility, setAppVisibility] = useState('public');
    const [publishPassword, setPublishPassword] = useState('');
    const [publishSlugDraft, setPublishSlugDraft] = useState('');
    const [publishUrlEditing, setPublishUrlEditing] = useState(false);
    const [activePreviewPage, setActivePreviewPage] = useState('/');
    const [previewPageMenuOpen, setPreviewPageMenuOpen] = useState(false);
    const [titleMenuOpen, setTitleMenuOpen] = useState(false);
    const titleMenuRef = useRef(null);
    const previewPageMenuRef = useRef(null);
    const [previewDevice, setPreviewDevice] = useState('desktop');
    const [previewRefreshKey, setPreviewRefreshKey] = useState(0);
    const [previewSelectionText, setPreviewSelectionText] = useState('');
    const [previewEditMode, setPreviewEditMode] = useState(false);
    const [previewEditText, setPreviewEditText] = useState('');
    const [inlineEditMode, setInlineEditMode] = useState(false);
    const [inlineEditTarget, setInlineEditTarget] = useState(null);
    const [styleOverrides, setStyleOverrides] = useState({});
    const [hasInlinePreviewEdits, setHasInlinePreviewEdits] = useState(false);
    const [showInlineSnapshot, setShowInlineSnapshot] = useState(false);
    const [selectionModeActive, setSelectionModeActive] = useState(false);
    const [selectedElements, setSelectedElements] = useState([]);
    const selectedElementsRef = useRef([]);
    useEffect(() => {
        selectedElementsRef.current = selectedElements;
    }, [selectedElements]);
    const [dashboardSection, setDashboardSection] = useState('overview');

    useEffect(() => {
        if (!isViewer && activeRightTab === 'code' && !aiBuilderPermissions.canCodePreview && !aiBuilderPermissions.canCodeEdit) {
            setActiveRightTab('preview');
        }
    }, [isViewer, activeRightTab, aiBuilderPermissions.canCodePreview, aiBuilderPermissions.canCodeEdit]);

    useEffect(() => {
        if (activeRightTab === 'dashboard' && dashboardSection === 'domain' && !aiBuilderPermissions.canCustomDomain) {
            setDashboardSection('overview');
        }
    }, [activeRightTab, dashboardSection, aiBuilderPermissions.canCustomDomain]);

    const [supabaseIntegration, setSupabaseIntegration] = useState(null);
    const [supabaseLoading, setSupabaseLoading] = useState(false);
    const [supabaseError, setSupabaseError] = useState(null);
    const [supabaseUsers, setSupabaseUsers] = useState([]);
    const [supabaseBuckets, setSupabaseBuckets] = useState([]);
    const [supabaseTables, setSupabaseTables] = useState([]);
    const [supabaseTableData, setSupabaseTableData] = useState([]);
    const [supabaseTableDataLoading, setSupabaseTableDataLoading] = useState(false);
    const [supabaseTableDataError, setSupabaseTableDataError] = useState(null);
    const [customDomain, setCustomDomain] = useState('');
    const [customDomainLoading, setCustomDomainLoading] = useState(false);
    const [customDomainError, setCustomDomainError] = useState('');
    const [customDomainSuccess, setCustomDomainSuccess] = useState('');
    const [supabaseForm, setSupabaseForm] = useState({
        mode: 'byos_manual',
        connection_type: 'hosted',
        admin_supabase_config_id: '',
        project_url: '',
        project_id: '',
        publishable_key: '',
        secret_key: '',
        db_password: '',
        region: '',
    });
    const [supabaseConfigs, setSupabaseConfigs] = useState([]);
    const [supabaseCapabilities, setSupabaseCapabilities] = useState({
        access: true,
        allow_admin_managed: true,
        allow_byos: true,
        allow_byos_oauth: true,
        allow_byos_manual: true,
    });

    const effectiveSupabaseCapabilities = useMemo(() => {
        const base = supabaseCapabilities || {};
        return {
            ...base,
            allow_byos: !!aiBuilderPermissions.canByos && base.allow_byos,
            allow_byos_oauth: !!aiBuilderPermissions.canByos && base.allow_byos_oauth,
            allow_byos_manual: !!aiBuilderPermissions.canByos && base.allow_byos_manual,
        };
    }, [supabaseCapabilities, aiBuilderPermissions.canByos]);

    const abortRef = useRef(null);
    const sessionCompileUrlTemplateRef = useRef('');
    const sessionPreviewUrlTemplateRef = useRef('');
    const starterFilesUrlRef = useRef('');
    const starterFilesRef = useRef([]);
    const iterateUrlTemplateRef = useRef('');
    const suggestionsUrlRef = useRef('');
    const creditStatusUrlRef = useRef('');
    const compileTimeoutRef = useRef(null);
    const progressPollIntervalRef = useRef(null);
    const sseAbortRef = useRef(null);
    const serializeMessagesForSaveRef = useRef((msgs) => msgs);
    const formatProgressMessageRef = useRef((message) => message || '');
    const applyGeneratedResultRef = useRef(null);
    const createSessionRef = useRef(null);
    const iterateRef = useRef(null);
    const lastProgressMessageRef = useRef('');
    const lastCompiledSignatureRef = useRef('');
    const thinkingStartRef = useRef(0);
    const thoughtAddedRef = useRef(false);
    const renderedStepsCountRef = useRef(0);
    const renderedBlocksCountRef = useRef(0);
    const pollStartTimeRef = useRef(0);
    const compileSandboxRef = useRef(null);
    const handleSaveRef = useRef(null);
    const sandboxRetryCountRef = useRef(0);
    const MAX_SANDBOX_RETRIES = 3;
    const previewIframeRef = useRef(null);
    const fakeProgressIntervalRef = useRef(null);
    const autoSaveTimeoutRef = useRef(null);
    const saveStatusTimeoutRef = useRef(null);
    const lastAutoSavedRef = useRef({ projectFiles: '', previewHtml: '' });

    // Keep messagesRef in sync so generate() can read the latest messages
    useEffect(() => {
        messagesRef.current = messages;
    }, [messages]);

    // Keep dynamicEventsRef in sync
    useEffect(() => {
        dynamicEventsRef.current = dynamicEvents;
    }, [dynamicEvents]);

    // Session persistence
    const [sessionId, setSessionId] = useState('');
    const sessionStoreUrlRef = useRef('');
    const sessionUpdateUrlRef = useRef('');
    const saveSessionTimeoutRef = useRef(null);
    const saveSessionRef = useRef(null);
    const saveAppMetaRef = useRef(null);
    const hasCreatedSessionRef = useRef(false);
    const generateRef = useRef(null);
    const discussRef = useRef(null);
    const autoStartedRef = useRef(false);

    // Read dataset on first render properly
    useEffect(() => {
        const el = document.getElementById('page-ai-builder-react');
        if (!el) return;
        mountEl.current = el;
    }, []);

    // Close title menu when clicking outside
    useEffect(() => {
        if (!titleMenuOpen) return;
        const handleClick = (e) => {
            if (titleMenuRef.current && !titleMenuRef.current.contains(e.target)) {
                setTitleMenuOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, [titleMenuOpen]);

    // Close preview page menu when clicking outside
    useEffect(() => {
        if (!previewPageMenuOpen) return;
        const handleClick = (e) => {
            if (previewPageMenuRef.current && !previewPageMenuRef.current.contains(e.target)) {
                setPreviewPageMenuOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, [previewPageMenuOpen]);

    // Close publish menu when clicking outside
    useEffect(() => {
        if (!publishMenuOpen) return;
        const handleClick = (e) => {
            if (publishMenuRef.current && !publishMenuRef.current.contains(e.target)) {
                setPublishMenuOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, [publishMenuOpen]);

    // Close share menu when clicking outside
    useEffect(() => {
        if (!shareMenuOpen) return;
        const handleClick = (e) => {
            if (shareMenuRef.current && !shareMenuRef.current.contains(e.target)) {
                setShareMenuOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, [shareMenuOpen]);

    useEffect(() => {
        if (publishMenuOpen) return;
        setPublishUrlEditing(false);
        setPublishSlugDraft(pageSlug || previewTitle?.toLowerCase().replace(/\s+/g, '-') || 'my-app');
    }, [publishMenuOpen, pageSlug, previewTitle]);

    useEffect(() => {
        setCustomDomain(saveSuccess?.custom_domain || '');
    }, [saveSuccess?.custom_domain]);

    const effectiveGenerateUrl = mountEl.current?.dataset?.generateUrl || '';
    const effectiveSaveUrl = mountEl.current?.dataset?.saveUrl || '';
    const effectiveCustomDomainUrl = mountEl.current?.dataset?.customDomainUrl || '';
    const effectiveServerIp = mountEl.current?.dataset?.serverIp || '';
    const effectiveServerHost = mountEl.current?.dataset?.serverHost || '';
    const effectivePagesIndexUrl = mountEl.current?.dataset?.pagesIndexUrl || '';
    const effectiveDashboardUrl = mountEl.current?.dataset?.dashboardUrl || '';
    const effectiveNewProjectUrl = mountEl.current?.dataset?.newProjectUrl || '';
    const effectiveSupabaseUrlTemplate = mountEl.current?.dataset?.appSupabaseUrlTemplate || '';
    const effectiveSupabaseUsersUrlTemplate = mountEl.current?.dataset?.appSupabaseUsersUrlTemplate || '';
    const effectiveSupabaseBucketsUrlTemplate = mountEl.current?.dataset?.appSupabaseBucketsUrlTemplate || '';
    const effectiveSupabaseTablesUrlTemplate = mountEl.current?.dataset?.appSupabaseTablesUrlTemplate || '';
    const effectiveSupabaseTableDataUrlTemplate = mountEl.current?.dataset?.appSupabaseTableDataUrlTemplate || '';
    const effectiveSupabaseConfigsListUrl = mountEl.current?.dataset?.supabaseConfigsListUrl || '';
    const effectiveSupabaseOAuthRedirectUrl = mountEl.current?.dataset?.supabaseOauthRedirectUrl || '';
    const effectiveCustomerSupabaseStatusUrl = mountEl.current?.dataset?.customerSupabaseStatusUrl || '';
    const effectiveExportUrlTemplate = mountEl.current?.dataset?.exportUrlTemplate || '';
    const savedPageId = saveSuccess?.id || initialPageId || null;
    const effectiveSupabaseUrl = effectiveSupabaseUrlTemplate
        ? (effectiveSupabaseUrlTemplate.includes('__PAGE__') && !savedPageId
            ? ''
            : effectiveSupabaseUrlTemplate.replace('__PAGE__', savedPageId || ''))
        : '';
    const effectiveSupabaseUsersUrl = effectiveSupabaseUsersUrlTemplate
        ? (effectiveSupabaseUsersUrlTemplate.includes('__PAGE__') && !savedPageId
            ? ''
            : effectiveSupabaseUsersUrlTemplate.replace('__PAGE__', savedPageId || ''))
        : '';
    const effectiveSupabaseBucketsUrl = effectiveSupabaseBucketsUrlTemplate
        ? (effectiveSupabaseBucketsUrlTemplate.includes('__PAGE__') && !savedPageId
            ? ''
            : effectiveSupabaseBucketsUrlTemplate.replace('__PAGE__', savedPageId || ''))
        : '';
    const effectiveSupabaseTablesUrl = effectiveSupabaseTablesUrlTemplate
        ? (effectiveSupabaseTablesUrlTemplate.includes('__PAGE__') && !savedPageId
            ? ''
            : effectiveSupabaseTablesUrlTemplate.replace('__PAGE__', savedPageId || ''))
        : '';
    const effectiveSupabaseTableDataUrl = effectiveSupabaseTableDataUrlTemplate
        ? (effectiveSupabaseTableDataUrlTemplate.includes('__PAGE__') && !savedPageId
            ? ''
            : effectiveSupabaseTableDataUrlTemplate.replace('__PAGE__', savedPageId || ''))
        : '';
    const previewSlugLabel = pageSlug || previewTitle?.toLowerCase().replace(/\s+/g, '-') || 'my-app';
    const inferredWorkspaceTitle = useMemo(() => inferWorkspaceTitle(projectFiles), [projectFiles]);
    const explicitWorkspaceTitle = cleanWorkspaceTitle(appMetaTitle);
    const explicitProjectTitle = cleanWorkspaceTitle(pageTitle || previewTitle || '');
    const hasGeneratedProject = generatedReactCode && generatedReactCode.trim() !== '';
    const workspaceAppTitle = pageTitle || previewTitle || (!isPlaceholderWorkspaceTitle(explicitProjectTitle) ? explicitProjectTitle : '') || (hasGeneratedProject ? inferredWorkspaceTitle : '') || explicitWorkspaceTitle || 'Untitled App';
    const workspaceAppMonogram = useMemo(() => buildWorkspaceMonogram(workspaceAppTitle), [workspaceAppTitle]);
    const appPreviewPages = useMemo(() => {
        if (previewMode !== 'react' || !Array.isArray(projectFiles) || projectFiles.length === 0) {
            return [];
        }

        const toDisplayName = (fileName) => {
            const base = String(fileName || '').replace(/\.(jsx|tsx)$/i, '').replace(/Page$/i, '');
            return base
                .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
                .replace(/[-_]+/g, ' ')
                .trim() || 'Page';
        };

        const toSlug = (fileName) => {
            const base = String(fileName || '').replace(/\.(jsx|tsx)$/i, '').replace(/Page$/i, '');
            if (/^(home|index|landing|landingpage)$/i.test(base)) {
                return '/';
            }

            const slug = base
                .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
                .replace(/[^a-zA-Z0-9]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '')
                .toLowerCase();

            return `/${slug || 'home'}`;
        };

        const pages = projectFiles
            .filter((file) => typeof file?.path === 'string' && /^src\/pages\/.*\.(jsx|tsx)$/i.test(file.path))
            .map((file) => {
                const fileName = file.path.split('/').pop() || '';
                return {
                    path: file.path,
                    name: toDisplayName(fileName),
                    slug: toSlug(fileName),
                };
            });

        pages.sort((a, b) => {
            if (a.slug === '/') return -1;
            if (b.slug === '/') return 1;
            return a.name.localeCompare(b.name);
        });

        return pages;
    }, [previewMode, projectFiles]);
    const activeAppPreviewPage = appPreviewPages.find((page) => page.slug === activePreviewPage) || appPreviewPages[0] || null;
    const previewRouteLabel = activeAppPreviewPage?.slug || previewSlugLabel;
    const publishHost = typeof window !== 'undefined' ? window.location.host : '';
    const publishHostBase = publishHost.split(':')[0];
    const publishUrlIsPath = publishHostBase === 'localhost' || /^\d+\.\d+\.\d+\.\d+$/.test(publishHostBase);
    const publishOrigin = typeof window !== 'undefined' ? window.location.origin : '';
    const publishPathPrefix = publishUrlIsPath ? `${publishOrigin}/pages/` : null;
    const publishSlugValue = (publishSlugDraft || pageSlug || previewSlugLabel).trim() || previewSlugLabel;
    const publishWebsiteUrl = publishUrlIsPath
        ? `${publishPathPrefix}${publishSlugValue}`
        : (publishHost ? `${publishSlugValue}.${publishHost}` : (saveSuccess?.public_url || publishSlugValue));
    const publishUrlSlug = publishSlugValue;
    const publishUrlSuffix = publishUrlIsPath ? null : (publishHost || '');
    const publishUrl = appVisibility === 'password' && publishPassword
        ? `${publishWebsiteUrl}${publishWebsiteUrl.includes('?') ? '&' : '?'}password=${encodeURIComponent(publishPassword)}`
        : publishWebsiteUrl;
    const previewViewportClass = previewDevice === 'mobile'
        ? 'mx-auto w-[390px] max-w-full h-full'
        : previewDevice === 'tablet'
            ? 'mx-auto w-[820px] max-w-full h-full'
            : 'w-full h-full';

    useEffect(() => {
        if (previewMode !== 'react') {
            return;
        }

        if (!appPreviewPages.length) {
            if (activePreviewPage !== '/') {
                setActivePreviewPage('/');
            }
            return;
        }

        if (!appPreviewPages.some((page) => page.slug === activePreviewPage)) {
            setActivePreviewPage(appPreviewPages[0].slug);
        }
    }, [activePreviewPage, appPreviewPages, previewMode]);

    const getCompileUrlForSession = useCallback((sid) => {
        const template = sessionCompileUrlTemplateRef.current || '';
        return sid ? template.replace('__SESSION__', sid) : '';
    }, []);

    const getPreviewUrlForSession = useCallback((sid) => {
        const template = sessionPreviewUrlTemplateRef.current || '';
        if (!sid) return '';

        const resolved = template.replace('__SESSION__', sid);
        return resolved.endsWith('/index.html') ? resolved : `${resolved.replace(/\/$/, '')}/index.html`;
    }, []);

    const previewIframeUrl = useMemo(() => {
        let base = previewUrl;
        if (!base && sessionId && projectFiles.length > 0) {
            const sessionPreview = getPreviewUrlForSession(sessionId);
            if (sessionPreview) {
                base = `${sessionPreview}${sessionPreview.includes('?') ? '&' : '?'}v=${Date.now()}`;
            }
        }

        if (previewMode !== 'react' || !base) {
            return base;
        }

        const routeSlug = activeAppPreviewPage?.slug || '/';
        return `${String(base).split('#')[0]}#${routeSlug}`;
    }, [activeAppPreviewPage?.slug, previewMode, previewUrl, sessionId, projectFiles.length, getPreviewUrlForSession]);

    const getIterateUrlForSession = useCallback((sid) => {
        const template = iterateUrlTemplateRef.current || '';
        return sid ? template.replace('__SESSION__', sid) : '';
    }, []);

    const refreshCreditStatus = useCallback(async () => {
        const url = creditStatusUrlRef.current;
        if (!url) return;
        try {
            const resp = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const data = await resp.json().catch(() => null);
            if (data && data.success && data.credit_status) {
                setAiBuilderCreditStatus(data.credit_status);
            }
        } catch (e) {
            // silent fail — credit status UI is non-critical
        }
    }, []);

    const mapProgressEventTypeToAction = useCallback((type) => {
        switch (String(type || '').toLowerCase()) {
            case 'analysis':
            case 'planning':
                return 'thinking';
            case 'design':
            case 'content':
            case 'components':
                return 'creating';
            case 'forms':
            case 'integrations':
            case 'optimization':
                return 'editing';
            case 'testing':
            case 'deployment':
                return 'reading';
            default:
                return 'working';
        }
    }, []);

    const getFallbackIterationEvents = useCallback((iterationPrompt) => {
        const normalized = String(iterationPrompt || '').trim();
        return [
            {
                title: 'Reviewing requested changes',
                description: normalized ? `Understanding how to apply: ${normalized}` : 'Understanding the requested update and its impact.',
                type: 'analysis',
            },
            {
                title: 'Editing affected sections',
                description: 'Updating the relevant components while keeping the existing layout coherent.',
                type: 'components',
            },
            {
                title: 'Polishing refreshed preview',
                description: 'Preparing the updated result so the preview matches the requested iteration.',
                type: 'deployment',
            },
        ];
    }, []);

    const getFallbackGenerationEvents = useCallback((generationPrompt) => {
        const normalized = String(generationPrompt || '').toLowerCase();
        const events = [
            { title: 'Analyzing the app requirements', description: 'Breaking the request into screens, user flows, and reusable components.', type: 'analysis' },
            { title: 'Planning the app structure', description: 'Mapping navigation, shared layout, and the data needed by each screen.', type: 'planning' },
        ];

        if (/kanban|board|task|project management/.test(normalized)) {
            events.push(
                { title: 'Creating the Kanban board', description: 'Building columns, task cards, status transitions, and drag-and-drop interactions.', type: 'components' },
                { title: 'Creating task assignments', description: 'Adding team member avatars, assignment controls, priorities, and due dates.', type: 'forms' },
            );
        }
        if (/team|member|assignment|collaborat/.test(normalized)) {
            events.push({ title: 'Creating the team workspace', description: 'Designing member lists, roles, presence states, and collaboration details.', type: 'components' });
        }
        if (/setting|preference|configuration/.test(normalized)) {
            events.push({ title: 'Creating the project settings page', description: 'Adding project details, preferences, permissions, and workspace controls.', type: 'forms' });
        }
        if (/dashboard|analytics|chart|report/.test(normalized)) {
            events.push({ title: 'Creating the project dashboard', description: 'Adding summary metrics, activity signals, and responsive data visualizations.', type: 'components' });
        }

        events.push(
            { title: 'Connecting the app navigation', description: 'Linking each screen so the main workflows can be explored from the preview.', type: 'integrations' },
            { title: 'Polishing the responsive layout', description: 'Checking spacing, hierarchy, empty states, and mobile behavior across the app.', type: 'optimization' },
            { title: 'Preparing the live preview', description: 'Validating the generated project and preparing the final build.', type: 'deployment' },
        );

        return events;
    }, []);

    const getPrimaryReactCodeFromFiles = useCallback((files) => {
        if (!Array.isArray(files)) return '';

        const preferred = files.find((file) => typeof file?.path === 'string' && /^src\/pages\/.*\.(jsx|tsx)$/i.test(file.path));
        if (preferred && typeof preferred.content === 'string') {
            return preferred.content;
        }

        const fallback = files.find((file) => typeof file?.path === 'string' && /\.(jsx|tsx)$/i.test(file.path));
        return fallback && typeof fallback.content === 'string' ? fallback.content : '';
    }, []);

    const getProjectFilesSignature = useCallback((files) => {
        if (!Array.isArray(files)) return '[]';
        return JSON.stringify(files.map((file) => ({
            path: file?.path || '',
            content: file?.content || '',
        })));
    }, []);

    useEffect(() => {
        if (!screenshot) {
            setScreenshotPreviewUrl(null);
            return;
        }
        try {
            const url = URL.createObjectURL(screenshot);
            setScreenshotPreviewUrl(url);
            return () => { try { URL.revokeObjectURL(url); } catch (e) {} };
        } catch (e) {
            setScreenshotPreviewUrl(null);
        }
    }, [screenshot]);

    useEffect(() => {
        if (status !== 'generating') {
            setProgressData(null);
        }
    }, [status]);

    useEffect(() => {
        return () => {
            if (abortRef.current) {
                abortRef.current.abort();
            }
        };
    }, []);

    const generate = useCallback(async () => {
        setError('');
        sandboxRetryCountRef.current = 0;
        const trimmedPrompt = String(prompt || '').trim();
        const normalizedPrompt = stripVisibleThemePrompt(trimmedPrompt);
        const hasGeneratedProject = projectFiles.length > 0 && generatedReactCode !== '';
        const themeOnlyPrompt = selectedTheme?.prompt?.trim()
            ? `Apply the ${selectedTheme.name} theme:\n\n${selectedTheme.prompt.trim()}`
            : '';
        const finalPrompt = hasGeneratedProject
            ? (normalizedPrompt || themeOnlyPrompt)
            : composePromptWithTheme(normalizedPrompt, selectedThemeId);
        if (finalPrompt.trim() === '') {
            setError('Please enter a prompt describing what you want to generate.');
            return;
        }

        // If we already have an AI-generated project, use the targeted iteration flow
        if (hasGeneratedProject) {
            const isThemeOnlyIteration = !normalizedPrompt.trim() && themeOnlyPrompt;
            iterateRef.current?.(finalPrompt, null, isThemeOnlyIteration ? selectedTheme : null);
            return;
        }

        const genUrl = mountEl.current?.dataset?.generateUrl;
        if (!genUrl) {
            setError('Generate URL is not configured.');
            return;
        }

        const tempId = `tmp-${Date.now()}`;
        const optimisticUser = {
            id: tempId,
            role: 'user',
            content: trimmedPrompt !== '' ? trimmedPrompt : `Theme: ${selectedTheme?.name || 'Default'}`,
            model,
            status: 'ok',
            created_at: new Date().toISOString(),
        };
        const initialProgressMsg = {
            id: 'progress-thinking',
            role: 'assistant',
            content: 'Thinking…',
            status: 'progress',
            created_at: new Date().toISOString(),
        };
        setMessages((prev) => [...prev, optimisticUser, initialProgressMsg]);
        lastProgressMessageRef.current = 'Thinking…';
        setPrompt('');
        const sentScreenshot = screenshot;
        setScreenshot(null);
        setScreenshotPreviewUrl(null);
        setProgressData({ step: 0, total: 3, message: 'Thinking…', file: null, status: 'generating' });
        setDynamicEvents(null);
        setLoading(true);
        setStatus('generating');
        thoughtAddedRef.current = false;
        renderedStepsCountRef.current = 0;
        renderedBlocksCountRef.current = 0;
        setGenerationPhase('analyzing');
        setShowPreview(false);
        setPreviewHtml('');
        previewHtmlRef.current = '';
        inlineEditsActiveRef.current = false;
        setHasInlinePreviewEdits(false);
        setShowInlineSnapshot(false);
        setPreviewUrl('');
        setProjectFiles([]);
        setBuildLog('');
        setReactCode('');
        setGeneratedReactCode('');
        setPreviewMode('html');
        setPreviewTheme(DEFAULT_REACT_THEME);
        setActiveRightTab('preview');
        setSaveSuccess(null);
        abortRef.current = new AbortController();

        let activeSessionId = sessionId;

        // Generate optimistic client-side UUID immediately so the URL updates before any async work
        if (!activeSessionId) {
            activeSessionId = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : `job-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
        }

        // Update browser URL without reload so the chat is preserved on refresh
        const showUrlTemplate = mountEl.current?.dataset?.sessionShowUrlTemplate;
        if (showUrlTemplate && window.history && window.history.replaceState) {
            window.history.replaceState({}, '', showUrlTemplate.replace('__SESSION__', activeSessionId));
        }

        // Create session immediately on first user message (backend may reuse the optimistic UUID)
        if (!hasCreatedSessionRef.current) {
            const backendSessionId = await createSessionRef.current?.({
                messages: serializeMessagesForSaveRef.current([...messages, optimisticUser]),
                model,
                session_id: activeSessionId,
            });
            if (backendSessionId) {
                activeSessionId = backendSessionId;
            }
        }

        // Fetch contextual progress events first
        const analyzeUrl = mountEl.current?.dataset?.analyzeUrl;
        let generationEvents = [];
        if (analyzeUrl) {
            try {
                const afd = new FormData();
                afd.append('_token', getCsrfToken());
                afd.append('prompt', finalPrompt);
                afd.append('raw_prompt', normalizedPrompt);
                const aresp = await fetch(analyzeUrl, {
                    method: 'POST',
                    body: afd,
                    credentials: 'same-origin',
                    signal: abortRef.current.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });
                const adata = await aresp.json().catch(() => null);
                if (adata && adata.success && Array.isArray(adata.events) && adata.events.length > 0) {
                    generationEvents = adata.events;
                    setDynamicEvents(adata.events);
                }
            } catch (e) {
                // Silently fall back to default progress events
            }
        }

        if (generationEvents.length === 0) {
            generationEvents = getFallbackGenerationEvents(normalizedPrompt);
            setDynamicEvents(generationEvents);
        }

        // The queue can take several seconds before the worker emits its first
        // server-side block. Show the request-specific build plan immediately so
        // the user sees real app areas instead of a static "Thinking…" message.
        if (fakeProgressIntervalRef.current) {
            clearInterval(fakeProgressIntervalRef.current);
            fakeProgressIntervalRef.current = null;
        }
        let generationEventIndex = 0;
        const showGenerationEvent = (event, index) => {
            const title = String(event?.title || '').trim() || 'Building the app';
            const description = String(event?.description || '').trim() || null;
            const action = mapProgressEventTypeToAction(event?.type);
            lastProgressMessageRef.current = title;
            setProgressData({
                step: index + 1,
                total: generationEvents.length,
                message: title,
                file: null,
                action,
                description,
                status: 'generating',
            });
            setMessages((prev) => [
                ...prev.filter((message) => message.id !== 'progress-thinking'),
                {
                    id: `generated-event-${Date.now()}-${index}`,
                    role: 'assistant',
                    content: title,
                    status: 'progress',
                    action,
                    description,
                    created_at: new Date().toISOString(),
                },
            ]);
        };
        showGenerationEvent(generationEvents[0], generationEventIndex);
        generationEventIndex += 1;
        if (generationEventIndex < generationEvents.length) {
            fakeProgressIntervalRef.current = setInterval(() => {
                if (generationEventIndex >= generationEvents.length) {
                    clearInterval(fakeProgressIntervalRef.current);
                    fakeProgressIntervalRef.current = null;
                    return;
                }
                showGenerationEvent(generationEvents[generationEventIndex], generationEventIndex);
                generationEventIndex += 1;
            }, 1100);
        }

        // After analyze, move to thinking phase until backend reports file progress
        setGenerationPhase('thinking');
        thinkingStartRef.current = Date.now();

        let pollInterval = null;
        let doPoll = null;
        let pollInFlight = false;
        let shouldKeepPolling = false;
        const progressUrl = mountEl.current?.dataset?.progressUrl;
        if (progressPollIntervalRef.current) {
            clearInterval(progressPollIntervalRef.current);
            progressPollIntervalRef.current = null;
        }
        if (progressUrl && activeSessionId) {
            pollStartTimeRef.current = Date.now();
            doPoll = async () => {
                if (pollInFlight) {
                    return;
                }
                if (abortRef.current?.signal?.aborted) {
                    if (progressPollIntervalRef.current) {
                        clearInterval(progressPollIntervalRef.current);
                        progressPollIntervalRef.current = null;
                    }
                    return;
                }
                pollInFlight = true;
                try {
                    const presp = await fetch(progressUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ session_id: activeSessionId }),
                    });
                    const pdata = await presp.json().catch(() => null);
                    if (pdata && pdata.success) {
                        const nextFile = pdata.file || null;
                        const nextMessage = formatProgressMessageRef.current(pdata.message || '', nextFile);
                        const nextDescription = typeof pdata.description === 'string' && pdata.description.trim() !== '' ? pdata.description.trim() : null;
                        const action = pdata.action || 'working';
                        setProgressData({
                            step: pdata.step || 0,
                            total: pdata.total || 0,
                            message: nextMessage,
                            file: nextFile,
                            action,
                            description: nextDescription,
                            status: pdata.status || 'generating',
                        });
                        // Render any newly accumulated typed blocks from the backend
                        const blocks = Array.isArray(pdata.blocks) ? pdata.blocks : [];
                        const newlyRendered = [];

                        if (blocks.length > 0) {
                            for (let i = renderedBlocksCountRef.current; i < blocks.length; i++) {
                                const b = blocks[i];
                                const bType = String(b.type || '').toLowerCase();
                                if (bType === 'tool') {
                                    setGenerationPhase('editing');
                                }
                                const blockType = bType === 'tool' ? 'tool'
                                    : bType === 'thought' ? 'thought'
                                    : bType === 'summary' ? 'summary'
                                    : 'narration';
                                newlyRendered.push({
                                    id: `block-${Date.now()}-${i}`,
                                    role: 'assistant',
                                    type: blockType,
                                    content: typeof b.content === 'string' ? b.content : '',
                                    action: typeof b.action === 'string' && b.action !== '' ? b.action : null,
                                    file: typeof b.file === 'string' && b.file !== '' ? b.file : null,
                                    detail: typeof b.detail === 'string' && b.detail !== '' ? b.detail : null,
                                    duration: Number.isFinite(Number(b.duration)) && Number(b.duration) > 0 ? Number(b.duration) : null,
                                    status: 'progress',
                                    created_at: b.at || new Date().toISOString(),
                                });
                            }
                            if (newlyRendered.length > 0) {
                                renderedBlocksCountRef.current = blocks.length;
                                const lastBlock = blocks[blocks.length - 1];
                                const lastBlockMsg = typeof lastBlock?.content === 'string' && lastBlock.content.trim() !== '' ? lastBlock.content : 'Working…';
                                lastProgressMessageRef.current = lastBlockMsg;
                                setMessages((prev) => [
                                    ...prev.filter((m) => m.id !== 'progress-thinking' && !m.id.startsWith('thought-') && !m.id.startsWith('generated-event-')),
                                    ...newlyRendered,
                                ]);
                            }
                        } else {
                            // Legacy fallback (no blocks yet): render steps as activity lines
                            const steps = Array.isArray(pdata.steps) ? pdata.steps : [];
                            for (let i = renderedStepsCountRef.current; i < steps.length; i++) {
                                const s = steps[i];
                                const stepMsg = formatProgressMessageRef.current(s.message || '', s.file || null);
                                if (stepMsg && stepMsg !== lastProgressMessageRef.current) {
                                    lastProgressMessageRef.current = stepMsg;
                                    newlyRendered.push({
                                        id: `progress-${Date.now()}-${i}`,
                                        role: 'assistant',
                                        content: stepMsg,
                                        status: 'progress',
                                        file: s.file || null,
                                        action: s.action || 'working',
                                        description: typeof s.description === 'string' && s.description.trim() !== '' ? s.description.trim() : null,
                                        created_at: new Date().toISOString(),
                                    });
                                }
                            }
                            if (newlyRendered.length > 0) {
                                renderedStepsCountRef.current = steps.length;
                                setMessages((prev) => [...prev, ...newlyRendered]);
                            }
                            // Also append the current live message if it's different from the last step
                            if (nextMessage && nextMessage !== lastProgressMessageRef.current && pdata.status !== 'done' && pdata.status !== 'error') {
                                lastProgressMessageRef.current = nextMessage;
                                setMessages((prev) => [
                                    ...prev,
                                    {
                                        id: `progress-${Date.now()}`,
                                        role: 'assistant',
                                        content: nextMessage,
                                        status: 'progress',
                                        file: nextFile,
                                        action,
                                        description: nextDescription,
                                        created_at: new Date().toISOString(),
                                    },
                                ]);
                                renderedStepsCountRef.current = steps.length;
                            }
                        }

                        if (pdata.status === 'done' && pdata.result) {
                            if (progressPollIntervalRef.current) {
                                clearInterval(progressPollIntervalRef.current);
                                progressPollIntervalRef.current = null;
                            }
                            if (abortRef.current?.signal?.aborted) {
                                return;
                            }
                            applyGeneratedResultRef.current?.(pdata.result, activeSessionId);
                            if (pdata.result.type !== 'chat' && Array.isArray(pdata.result.project_files) && pdata.result.project_files.length > 0 && !pdata.result.preview_url) {
                                setTimeout(() => {
                                    compileSandboxRef.current?.(pdata.result.project_files, false);
                                }, 200);
                            }
                            return;
                        }
                        if (pdata.status === 'cancelled') {
                            if (progressPollIntervalRef.current) {
                                clearInterval(progressPollIntervalRef.current);
                                progressPollIntervalRef.current = null;
                            }
                            setLoading(false);
                            setStatus('idle');
                            setGenerationPhase(null);
                            setProgressData(null);
                            setMessages((prev) => {
                                const filtered = prev.filter((m) => m.id !== 'progress-thinking' && !(m.status === 'progress' && !m.type));
                                return [...filtered, {
                                    id: `cancelled-${Date.now()}`,
                                    role: 'assistant',
                                    content: 'Generation cancelled.',
                                    status: 'cancelled',
                                    created_at: new Date().toISOString(),
                                }];
                            });
                            return;
                        }
                        if (pdata.status === 'error') {
                            if (progressPollIntervalRef.current) {
                                clearInterval(progressPollIntervalRef.current);
                                progressPollIntervalRef.current = null;
                            }
                            const errorMessage = pdata.message || 'AI generation failed.';
                            setError(errorMessage);
                            setStatus('error');
                            setLoading(false);
                            setGenerationPhase(null);
                            setMessages((prev) => [
                                ...prev.filter((m) => m.id !== 'progress-thinking' && !(m.status === 'progress' && !m.type)),
                                {
                                    id: `err-${Date.now()}`,
                                    role: 'assistant',
                                    content: errorMessage,
                                    status: 'error',
                                    created_at: new Date().toISOString(),
                                },
                            ]);
                            return;
                        }
                    } else if (!presp.ok) {
                        // Surface poll errors so we can diagnose
                        const errText = pdata && pdata.message ? pdata.message : `Poll error ${presp.status}`;
                        setMessages((prev) => [
                            ...prev,
                            {
                                id: `poll-err-${Date.now()}`,
                                role: 'assistant',
                                content: `Progress poll failed: ${errText}`,
                                status: 'error',
                                created_at: new Date().toISOString(),
                            },
                        ]);
                    }
                } catch (e) {
                    setMessages((prev) => [
                        ...prev,
                        {
                            id: `poll-err-${Date.now()}`,
                            role: 'assistant',
                            content: `Progress poll exception: ${String(e?.message || e)}`,
                            status: 'error',
                            created_at: new Date().toISOString(),
                        },
                    ]);
                } finally {
                    pollInFlight = false;
                }
            };
        }

        const streamUrl = mountEl.current?.dataset?.streamUrl;
        let doStream = null;
        if (streamUrl && activeSessionId) {
            doStream = async () => {
                sseAbortRef.current?.abort();
                const controller = new AbortController();
                sseAbortRef.current = controller;

                try {
                    const response = await fetch(streamUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        signal: controller.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'text/event-stream',
                        },
                        body: JSON.stringify({ session_id: activeSessionId }),
                    });
                    if (!response.ok || !response.body) {
                        throw new Error(`SSE stream failed (${response.status})`);
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    let streamFinished = false;

                    const appendBlock = (block) => {
                        const blockType = String(block?.type || '').toLowerCase();
                        if (blockType === 'tool') setGenerationPhase('editing');
                        const type = blockType === 'tool' ? 'tool'
                            : blockType === 'thought' ? 'thought'
                            : blockType === 'summary' ? 'summary'
                            : 'narration';
                        const content = typeof block?.content === 'string' ? block.content : '';
                        if (!content) return;
                        renderedBlocksCountRef.current += 1;
                        lastProgressMessageRef.current = content;
                        setMessages((prev) => {
                            const clean = prev.filter((m) => m.id !== 'progress-thinking');
                            const mergeable = type === 'thought' && block.action === 'thinking';
                            const last = clean[clean.length - 1];
                            if (mergeable && last?.type === type && last?.action === block.action) {
                                return [...clean.slice(0, -1), {
                                    ...last,
                                    content: `${last.content || ''}${content}`,
                                }];
                            }
                            return [...clean, {
                                id: `stream-block-${Date.now()}-${renderedBlocksCountRef.current}`,
                                role: 'assistant',
                                type,
                                content,
                                action: block.action || null,
                                file: block.file || null,
                                detail: block.detail || null,
                                duration: Number(block.duration) > 0 ? Number(block.duration) : null,
                                status: 'progress',
                                created_at: block.at || new Date().toISOString(),
                            }];
                        });
                    };

                    const handleEvent = (eventType, payload) => {
                        if (eventType === 'block') {
                            appendBlock(payload);
                            return;
                        }
                        if (eventType === 'status') {
                            const file = payload.file || null;
                            setProgressData({
                                step: payload.step || 0,
                                total: payload.total || 0,
                                message: formatProgressMessageRef.current(payload.message || '', file),
                                file,
                                action: payload.action || 'working',
                                description: payload.description || null,
                                status: payload.status || 'generating',
                            });
                            if (payload.status === 'editing') setGenerationPhase('editing');
                            return;
                        }
                        if (eventType === 'done') {
                            streamFinished = true;
                            if (payload.status === 'done' && payload.result) {
                                const result = payload.result;
                                applyGeneratedResultRef.current?.(result, activeSessionId);
                                if (
                                    result.type !== 'chat'
                                    && Array.isArray(result.project_files)
                                    && result.project_files.length > 0
                                    && !result.preview_url
                                ) {
                                    setTimeout(() => {
                                        compileSandboxRef.current?.(result.project_files, false, activeSessionId);
                                    }, 200);
                                }
                            } else if (payload.status === 'cancelled') {
                                setLoading(false);
                                setStatus('idle');
                                setGenerationPhase(null);
                                setProgressData(null);
                            } else if (payload.status === 'error') {
                                const message = payload.message || 'AI generation failed.';
                                setError(message);
                                setStatus('error');
                                setLoading(false);
                                setGenerationPhase(null);
                                setMessages((prev) => [...prev, {
                                    id: `stream-error-${Date.now()}`,
                                    role: 'assistant',
                                    content: message,
                                    status: 'error',
                                    created_at: new Date().toISOString(),
                                }]);
                            }
                        }
                    };

                    while (!controller.signal.aborted && !streamFinished) {
                        const { done, value } = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, { stream: true });
                        const events = buffer.split(/\r?\n\r?\n/);
                        buffer = events.pop() || '';
                        for (const eventText of events) {
                            let eventType = 'message';
                            const dataLines = [];
                            for (const line of eventText.split(/\r?\n/)) {
                                if (line.startsWith('event:')) eventType = line.slice(6).trim();
                                if (line.startsWith('data:')) dataLines.push(line.slice(5).trim());
                            }
                            if (dataLines.length === 0) continue;
                            try {
                                handleEvent(eventType, JSON.parse(dataLines.join('\n')));
                            } catch (_) {
                                // Ignore malformed keep-alive or partial events.
                            }
                        }
                    }
                } catch (error) {
                    if (controller.signal.aborted || error?.name === 'AbortError') return;
                    setError(error?.message || 'AI progress stream failed.');
                    setStatus('error');
                    setLoading(false);
                    setGenerationPhase(null);
                } finally {
                    if (sseAbortRef.current === controller) sseAbortRef.current = null;
                }
            };
        }

        try {
            const fd = new FormData();
            fd.append('_token', getCsrfToken());
            fd.append('prompt', finalPrompt);
            fd.append('raw_prompt', normalizedPrompt);
            fd.append('model', String(model));
            fd.append('theme', JSON.stringify(selectedTheme));
            if (activeSessionId) fd.append('session_id', activeSessionId);
            if (sentScreenshot) fd.append('screenshot', sentScreenshot);

            const resp = await fetch(genUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                signal: abortRef.current.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            const data = await resp.json().catch(() => null);

            if (abortRef.current.signal.aborted) {
                return;
            }

            if (!resp.ok || !data || data.success !== true) {
                const msg = data && typeof data.message === 'string' && data.message.trim() !== ''
                    ? data.message
                    : 'AI generation failed.';
                setError(msg);
                setStatus('error');
                const assistantMsg = {
                    id: `err-${Date.now()}`,
                    role: 'assistant',
                    content: msg,
                    model,
                    status: 'error',
                    created_at: new Date().toISOString(),
                };
                setMessages((prev) => [...prev, assistantMsg]);
                return;
            }

            if (data.type === 'chat') {
                setStatus('idle');
                const assistantMsg = {
                    id: `chat-${Date.now()}`,
                    role: 'assistant',
                    content: data.message || '',
                    model: data.model_used || model,
                    status: 'ok',
                    created_at: new Date().toISOString(),
                };
                setMessages((prev) => [...prev, assistantMsg]);
                return;
            }

            if (data.queued) {
                shouldKeepPolling = true;
                if (data.session_id && !sessionId) {
                    setSessionId(data.session_id);
                }
                if (data.session_id) {
                    hasCreatedSessionRef.current = true;
                }
                // Start the stream only after the generation is queued server-side.
                if (doStream) {
                    doStream();
                } else if (doPoll) {
                    doPoll();
                    pollInterval = setInterval(doPoll, 1000);
                    progressPollIntervalRef.current = pollInterval;
                }
                return;
            }

            applyGeneratedResultRef.current?.(data, activeSessionId);
        } catch (e) {
            if (e && e.name === 'AbortError') {
                return;
            }
            const msg = e && e.message ? String(e.message) : 'AI generation failed.';
            setError(msg);
            setStatus('error');
            const assistantMsg = {
                id: `err-${Date.now()}`,
                role: 'assistant',
                content: msg,
                model,
                status: 'error',
                created_at: new Date().toISOString(),
            };
            setMessages((prev) => [...prev, assistantMsg]);
        } finally {
            if (fakeProgressIntervalRef.current) {
                clearInterval(fakeProgressIntervalRef.current);
                fakeProgressIntervalRef.current = null;
            }
            if (!shouldKeepPolling) {
                setLoading(false);
                setGenerationPhase(null);
                if (progressPollIntervalRef.current) {
                    clearInterval(progressPollIntervalRef.current);
                    progressPollIntervalRef.current = null;
                }
            }
            abortRef.current = null;
        }
    }, [prompt, screenshot, model, messages, getPreviewUrlForSession, sessionId, getProjectFilesSignature, pageTitle, pageSlug, pageStatus, projectFiles, generatedReactCode, selectedThemeId, selectedTheme, getFallbackGenerationEvents, mapProgressEventTypeToAction]);

    const iterate = useCallback(async (overridePrompt = null, filesOverride = null, themeOverride = null) => {
        setError('');
        const trimmedPrompt = stripVisibleThemePrompt(String((overridePrompt ?? prompt) || '').trim());
        const buildErrorContext = String(buildLog || '').trim();
        const finalPrompt = [
            buildErrorContext ? `Build error log:\n${buildErrorContext}` : '',
            trimmedPrompt,
        ].filter(Boolean).join('\n\n');
        if (finalPrompt.trim() === '') {
            setError('Please enter a prompt describing the change you want.');
            return;
        }

        const currentFiles = Array.isArray(filesOverride) ? filesOverride : projectFiles;
        if (currentFiles.length === 0) {
            setError('No existing project files to iterate on.');
            return;
        }

        const tempId = `tmp-${Date.now()}`;
        const optimisticUser = {
            id: tempId,
            role: 'user',
            content: trimmedPrompt !== '' ? trimmedPrompt : `Theme: ${selectedTheme?.name || 'Default'}`,
            model,
            status: 'ok',
            created_at: new Date().toISOString(),
        };
        const initialProgressMsg = {
            id: 'progress-thinking',
            role: 'assistant',
            content: 'Thinking…',
            status: 'progress',
            created_at: new Date().toISOString(),
        };
        setMessages((prev) => [...prev, optimisticUser, initialProgressMsg]);
        lastProgressMessageRef.current = 'Thinking…';
        setPrompt('');
        setProgressData({ step: 0, total: 3, message: 'Thinking…', file: null, status: 'generating' });
        setDynamicEvents(null);
        setLoading(true);
        setStatus('generating');
        setGenerationPhase('thinking');
        thinkingStartRef.current = Date.now();
        thoughtAddedRef.current = false;
        renderedStepsCountRef.current = 0;
        renderedBlocksCountRef.current = 0;
        setActiveRightTab('preview');
        setSaveSuccess(null);
        abortRef.current = new AbortController();

        let activeSessionId = sessionId;

        if (!activeSessionId) {
            activeSessionId = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : `job-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
        }

        const showUrlTemplate = mountEl.current?.dataset?.sessionShowUrlTemplate;
        if (showUrlTemplate && window.history && window.history.replaceState) {
            window.history.replaceState({}, '', showUrlTemplate.replace('__SESSION__', activeSessionId));
        }

        if (!hasCreatedSessionRef.current) {
            const backendSessionId = await createSessionRef.current?.({
                messages: serializeMessagesForSaveRef.current([...messages, optimisticUser]),
                model,
                session_id: activeSessionId,
                project_files: currentFiles,
                preview_mode: 'react',
            });
            if (backendSessionId) {
                activeSessionId = backendSessionId;
            }
        }

        const iterateUrl = getIterateUrlForSession(activeSessionId);
        if (!iterateUrl) {
            setError('Iterate URL is not configured.');
            setLoading(false);
            setGenerationPhase(null);
            return;
        }

        let iterationEvents = [];
        let isChatIntent = false;
        const analyzeUrl = mountEl.current?.dataset?.analyzeUrl;
        if (analyzeUrl) {
            try {
                const afd = new FormData();
                afd.append('_token', getCsrfToken());
                afd.append('prompt', finalPrompt);
                afd.append('raw_prompt', trimmedPrompt);
                const aresp = await fetch(analyzeUrl, {
                    method: 'POST',
                    body: afd,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });
                const adata = await aresp.json().catch(() => null);
                if (adata && adata.success && adata.intent === 'chat') {
                    isChatIntent = true;
                } else if (adata && adata.success && Array.isArray(adata.events) && adata.events.length > 0) {
                    iterationEvents = adata.events;
                    setDynamicEvents(adata.events);
                }
            } catch (e) {
                // fall back to local iteration events
            }
        }
        if (iterationEvents.length === 0 && !isChatIntent) {
            iterationEvents = getFallbackIterationEvents(finalPrompt);
            setDynamicEvents(iterationEvents);
        }

        if (fakeProgressIntervalRef.current) {
            clearInterval(fakeProgressIntervalRef.current);
            fakeProgressIntervalRef.current = null;
        }

        const emitIterationProgress = (event, index) => {
            const label = String(event?.title || '').trim() || 'Updating project';
            const description = String(event?.description || '').trim() || null;
            const action = mapProgressEventTypeToAction(event?.type);

            setProgressData({
                step: index + 1,
                total: iterationEvents.length,
                message: label,
                file: null,
                action,
                description,
                status: 'generating',
            });
            setGenerationPhase('editing');
            lastProgressMessageRef.current = label;

            setMessages((prev) => {
                const next = index === 0 ? prev.filter((m) => m.id !== 'progress-thinking') : prev;
                const additions = [];

                if (index === 0 && !thoughtAddedRef.current && thinkingStartRef.current > 0) {
                    thoughtAddedRef.current = true;
                    const elapsed = Math.max(1, Math.round((Date.now() - thinkingStartRef.current) / 1000));
                    additions.push({
                        id: `thought-${Date.now()}-${index}`,
                        role: 'assistant',
                        content: `Thought for ${elapsed}s`,
                        status: 'thought',
                        thought: description || label,
                        created_at: new Date().toISOString(),
                    });
                }

                additions.push({
                    id: `iter-progress-${Date.now()}-${index}`,
                    role: 'assistant',
                    content: label,
                    status: 'progress',
                    file: null,
                    action,
                    description,
                    created_at: new Date().toISOString(),
                });

                return [...next, ...additions];
            });
        };

        if (iterationEvents.length > 0) {
            let eventIndex = 0;
            emitIterationProgress(iterationEvents[eventIndex], eventIndex);
            eventIndex += 1;

            if (eventIndex < iterationEvents.length) {
                fakeProgressIntervalRef.current = setInterval(() => {
                    if (eventIndex >= iterationEvents.length) {
                        clearInterval(fakeProgressIntervalRef.current);
                        fakeProgressIntervalRef.current = null;
                        return;
                    }
                    emitIterationProgress(iterationEvents[eventIndex], eventIndex);
                    eventIndex += 1;
                }, 1100);
            }
        }

        try {
            let requestFiles = currentFiles;
            let segmentPlan = null;
            let data = null;
            let resp = null;
            do {
            resp = await fetch(iterateUrl, {
                method: 'POST',
                credentials: 'same-origin',
                signal: abortRef.current?.signal,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    prompt: finalPrompt,
                    raw_prompt: trimmedPrompt,
                    model,
                    mode: themeOverride ? 'theme' : (selectedElements.length > 0 ? 'selection' : 'iteration'),
                    theme: themeOverride || null,
                    project_files: requestFiles,
                    segment_plan: segmentPlan,
                    messages: serializeMessagesForSaveRef
                        .current([...messages, optimisticUser])
                        .map((m) => ({
                            id: m.id,
                            role: m.role,
                            content: m.content,
                            model: m.model,
                            status: m.status,
                            created_at: m.created_at,
                        })),
                }),
            });
            data = await resp.json().catch(() => null);
            if (!resp.ok || !data || data.success !== true) {
                const msg = data?.message || data?.error || 'Iteration failed.';
                setError(msg);
                setStatus('error');
                setLoading(false);
                setGenerationPhase(null);
                setMessages((prev) => [
                    ...prev.filter((m) => m.id !== 'progress-thinking'),
                    {
                        id: `err-${Date.now()}`,
                        role: 'assistant',
                        content: msg,
                        model,
                        status: 'error',
                        created_at: new Date().toISOString(),
                    },
                ]);
                return;
            }

            requestFiles = Array.isArray(data.project_files) && data.project_files.length > 0
                ? data.project_files
                : requestFiles;
            segmentPlan = Array.isArray(data.remaining_plan) && data.remaining_plan.length > 0
                ? data.remaining_plan
                : null;
            } while (segmentPlan && !abortRef.current?.signal?.aborted);

            if (abortRef.current?.signal?.aborted) {
                return;
            }

            const updatedFiles = requestFiles;
            const updatedReactCode = getPrimaryReactCodeFromFiles(updatedFiles);

            if (fakeProgressIntervalRef.current) {
                clearInterval(fakeProgressIntervalRef.current);
                fakeProgressIntervalRef.current = null;
            }

            // Chat-type response: the AI answered a question instead of editing the project.
            if (data.type === 'chat') {
                setMessages((prev) => [
                    ...prev.filter((m) => m.id !== 'progress-thinking'),
                    {
                        id: `chat-${Date.now()}`,
                        role: 'assistant',
                        type: 'summary',
                        content: data.message || '',
                        model,
                        status: 'ok',
                        created_at: new Date().toISOString(),
                    },
                ]);
                setLoading(false);
                setGenerationPhase(null);
                setStatus('idle');
                if (data.credit_status) {
                    setAiBuilderCreditStatus(data.credit_status);
                } else {
                    refreshCreditStatus();
                }
                return;
            }

            setMessages((prev) => [
                ...prev.filter((m) => m.id !== 'progress-thinking'),
                {
                    id: `iter-${Date.now()}`,
                    role: 'assistant',
                    content: `Updated: ${trimmedPrompt}`,
                    model,
                    status: 'ok',
                    created_at: new Date().toISOString(),
                },
            ]);

            setProjectFiles(updatedFiles);
            setReactCode(updatedReactCode);
            setGeneratedReactCode(updatedReactCode);
            setPreviewMode('react');
            setActiveRightTab('preview');
            setLoading(false);
            setGenerationPhase(null);
            setStatus('idle');

            if (data.credit_status) {
                setAiBuilderCreditStatus(data.credit_status);
            } else {
                refreshCreditStatus();
            }

            setTimeout(() => {
                compileSandboxRef.current?.(updatedFiles, false);
            }, 200);
        } catch (e) {
            if (fakeProgressIntervalRef.current) {
                clearInterval(fakeProgressIntervalRef.current);
                fakeProgressIntervalRef.current = null;
            }
            if (e && e.name === 'AbortError') {
                return;
            }
            const msg = e?.message || 'Iteration failed.';
            setError(msg);
            setStatus('error');
            setLoading(false);
            setGenerationPhase(null);
            setMessages((prev) => [
                ...prev.filter((m) => m.id !== 'progress-thinking'),
                {
                    id: `err-${Date.now()}`,
                    role: 'assistant',
                    content: msg,
                    model,
                    status: 'error',
                    created_at: new Date().toISOString(),
                },
            ]);
        } finally {
            abortRef.current = null;
        }
    }, [prompt, buildLog, projectFiles, model, messages, sessionId, getIterateUrlForSession, getPrimaryReactCodeFromFiles, getFallbackIterationEvents, mapProgressEventTypeToAction, refreshCreditStatus]);
    iterateRef.current = iterate;

    const getPreviewSelectionText = useCallback(() => {
        try {
            const iframe = previewIframeRef.current;
            if (!iframe || !iframe.contentWindow) return '';
            const sel = iframe.contentWindow.getSelection();
            return sel ? sel.toString().trim() : '';
        } catch (e) {
            return '';
        }
    }, []);

    const setPreviewSelectionMode = useCallback((active) => {
        const iframe = previewIframeRef.current;
        if (!iframe || !iframe.contentWindow || !iframe.contentDocument) return;
        try {
            const win = iframe.contentWindow;
            if (!win.__mailpurseSelectionScriptLoaded) {
                const script = iframe.contentDocument.createElement('script');
                script.textContent = PREVIEW_SELECTION_SCRIPT;
                iframe.contentDocument.head.appendChild(script);
            }
            const selectedElementsList = Array.isArray(selectedElementsRef.current) ? selectedElementsRef.current : [];
            win.__mailpurseApplySelection?.(selectedElementsList.map((e) => e.path));
            win.__mailpurseSelectionMode = active;
            if (!active) {
                win.__mailpurseRemoveSelectionOverlay?.();
            }
        } catch (e) {
            // ignore cross-origin or sandbox issues
        }
    }, []);

    const applyPreviewSelection = useCallback(() => {
        const iframe = previewIframeRef.current;
        if (!iframe || !iframe.contentWindow) return;
        try {
            const win = iframe.contentWindow;
            if (!win.__mailpurseSelectionScriptLoaded) return;
            const selectedElementsList = Array.isArray(selectedElementsRef.current) ? selectedElementsRef.current : [];
            const paths = selectedElementsList.map((e) => e.path);
            win.__mailpurseApplySelection?.(paths);
        } catch (e) {
            // ignore cross-origin or sandbox issues
        }
    }, []);

    const setPreviewInlineEditMode = useCallback((active) => {
        const iframe = previewIframeRef.current;
        if (!iframe || !iframe.contentWindow || !iframe.contentDocument) return;
        try {
            const win = iframe.contentWindow;
            const doc = iframe.contentDocument;
            if (!doc.getElementById('mailpurse-inline-edit-style')) {
                const style = doc.createElement('style');
                style.id = 'mailpurse-inline-edit-style';
                style.textContent = `
[contenteditable="true"], [contenteditable] {
    outline: none !important;
    -webkit-tap-highlight-color: transparent !important;
    caret-color: #2563eb;
}
.mailpurse-edit-selection, .mailpurse-hover-overlay, .mailpurse-selection-overlay, .mailpurse-selection-badge, .mailpurse-edit-selection-badge, .mailpurse-hover-badge {
    outline: none !important;
}
`;
                doc.head.appendChild(style);
            }
            if (!win.__mailpurseEditScriptLoaded) {
                const script = doc.createElement('script');
                script.async = false;
                script.textContent = PREVIEW_INLINE_EDIT_SCRIPT;
                doc.head.appendChild(script);
            }
            win.__mailpurseSetInlineEditMode?.(active);
        } catch (e) {
            // ignore cross-origin or sandbox issues
        }
    }, []);

    const markInlinePreviewDirty = useCallback(() => {
        setHasInlinePreviewEdits(true);
        setBuildLog('');
        setInlineEditVersion((v) => v + 1);
    }, []);

    const persistInlineSnapshot = useCallback((snapshot) => {
        if (!snapshot) return false;
        previewHtmlRef.current = snapshot;
        if (!inlineEditsActiveRef.current) {
            inlineEditsActiveRef.current = true;
        }
        markInlinePreviewDirty();
        return true;
    }, [markInlinePreviewDirty]);

    const replacePreviewElement = useCallback((path, replacement) => {
        if (!path || !replacement) return;
        const updateHtml = (html) => {
            if (!html) return html;
            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const el = doc.body.querySelector(path);
                if (!el) return html;
                el.outerHTML = replacement;
                const doctype = doc.doctype ? `<!DOCTYPE ${doc.doctype.name}>` : '';
                return doctype + doc.documentElement.outerHTML;
            } catch (e) {
                return html;
            }
        };
        previewHtmlRef.current = updateHtml(previewHtmlRef.current);
        if (!inlineEditsActiveRef.current) {
            setPreviewHtml((prev) => updateHtml(prev));
        }
        markInlinePreviewDirty();
    }, [markInlinePreviewDirty]);

    const updatePreviewImageSource = useCallback((path, src) => {
        if (!path || !src) return;
        const updateHtml = (html) => {
            if (!html) return html;
            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const el = doc.body.querySelector(path);
                if (!el || el.tagName.toLowerCase() !== 'img') return html;
                el.setAttribute('src', src);
                const doctype = doc.doctype ? `<!DOCTYPE ${doc.doctype.name}>` : '';
                return doctype + doc.documentElement.outerHTML;
            } catch (e) {
                return html;
            }
        };
        previewHtmlRef.current = updateHtml(previewHtmlRef.current);
        if (!inlineEditsActiveRef.current) {
            setPreviewHtml((prev) => updateHtml(prev));
        }
        markInlinePreviewDirty();
    }, [markInlinePreviewDirty]);

    const propagateImageChangeToProjectFiles = useCallback((oldSrc, newSrc) => {
        if (!oldSrc || !newSrc || oldSrc === newSrc) return;
        setProjectFiles((prev) => {
            if (!Array.isArray(prev) || !prev.length) return prev;
            let changed = false;
            const updated = prev.map((file) => {
                if (!file || typeof file.content !== 'string') return file;
                if (!/\.(jsx|tsx|js|ts)$/i.test(file.path || '')) return file;
                if (!file.content.includes(oldSrc)) return file;
                changed = true;
                return { ...file, content: file.content.split(oldSrc).join(newSrc) };
            });
            return changed ? updated : prev;
        });
    }, []);

    const applyPreviewStyleOverrides = useCallback((overrides) => {
        const iframe = previewIframeRef.current;
        if (!iframe || !iframe.contentWindow) return;
        try {
            iframe.contentWindow.postMessage({ type: 'MAILPURSE_APPLY_STYLE_OVERRIDES', overrides }, '*');
        } catch (e) {
            // ignore cross-origin or sandbox issues
        }
    }, []);

    useEffect(() => {
        applyPreviewSelection();
    }, [selectedElements, applyPreviewSelection]);

    const updateElementTextInPreview = useCallback((path, innerHTML) => {
        if (!path) return;
        const updateHtml = (html) => {
            if (!html) return html;
            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const el = doc.body.querySelector(path);
                if (!el) return html;
                el.innerHTML = innerHTML;
                const doctype = doc.doctype ? `<!DOCTYPE ${doc.doctype.name}>` : '';
                return doctype + doc.documentElement.outerHTML;
            } catch (e) {
                return html;
            }
        };
        previewHtmlRef.current = updateHtml(previewHtmlRef.current);
        if (!inlineEditsActiveRef.current) {
            setPreviewHtml((prev) => updateHtml(prev));
        }
        markInlinePreviewDirty();
    }, [markInlinePreviewDirty]);

    const deleteElementFromPreview = useCallback((path) => {
        if (!path) return;
        const updateHtml = (html) => {
            if (!html) return html;
            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const el = doc.body.querySelector(path);
                if (!el) return html;
                el.remove();
                const doctype = doc.doctype ? `<!DOCTYPE ${doc.doctype.name}>` : '';
                return doctype + doc.documentElement.outerHTML;
            } catch (e) {
                return html;
            }
        };
        previewHtmlRef.current = updateHtml(previewHtmlRef.current);
        if (!inlineEditsActiveRef.current) {
            setPreviewHtml((prev) => updateHtml(prev));
        }
        markInlinePreviewDirty();
    }, [markInlinePreviewDirty]);

    useEffect(() => {
        const handleMessage = (event) => {
            if (event.source !== previewIframeRef.current?.contentWindow) return;
            if (event.data?.type === 'MAILPURSE_ELEMENT_SELECT') {
                const selected = Array.isArray(event.data.selected) ? event.data.selected : [];
                setSelectedElements(selected);
                const content = selected.length === 1
                    ? `Selected element: ${selected[0].tag} - "${selected[0].text.slice(0, 200)}"`
                    : `Selected elements (${selected.length}): ${selected.map((e) => `${e.tag} "${e.text.slice(0, 80)}"`).join(', ')}`;
                setMessages((prevMessages) => {
                    const last = prevMessages[prevMessages.length - 1];
                    if (last && last.role === 'user' && last.id?.startsWith('sel-')) {
                        return [...prevMessages.slice(0, -1), { ...last, content, status: 'selection' }];
                    }
                    return [...prevMessages, { id: `sel-${Date.now()}`, role: 'user', content, status: 'selection', created_at: new Date().toISOString() }];
                });
            } else if (event.data?.type === 'MAILPURSE_INLINE_EDIT_TARGET') {
                setInlineEditTarget({ ...event.data.info, rect: event.data.rect });
            } else if (event.data?.type === 'MAILPURSE_INLINE_EDIT_RECT') {
                setInlineEditTarget((prev) => (prev ? { ...prev, rect: event.data.rect } : null));
            } else if (event.data?.type === 'MAILPURSE_INLINE_EDIT_DELETED') {
                persistInlineSnapshot(event.data.snapshot);
                setInlineEditTarget(null);
            } else if (event.data?.type === 'MAILPURSE_INLINE_EDIT_TEXT_CHANGED') {
                if (!persistInlineSnapshot(event.data.snapshot)) updateElementTextInPreview(event.data.path, event.data.innerHTML);
            } else if (event.data?.type === 'MAILPURSE_INLINE_EDIT_LINK_CHANGED') {
                if (!persistInlineSnapshot(event.data.snapshot)) replacePreviewElement(event.data.path, event.data.outerHTML);
                setInlineEditTarget({ ...event.data.info, rect: event.data.rect });
            } else if (event.data?.type === 'MAILPURSE_INLINE_EDIT_IMAGE_CHANGED') {
                if (!persistInlineSnapshot(event.data.snapshot)) updatePreviewImageSource(event.data.path, event.data.src);
                if (event.data.oldSrc) propagateImageChangeToProjectFiles(event.data.oldSrc, event.data.src);
                setInlineEditTarget((prev) => (prev ? { ...prev, src: event.data.src, rect: event.data.rect } : null));
            } else if (event.data?.type === 'MAILPURSE_INLINE_EDIT_REPLACED_WITH_IMAGE' || event.data?.type === 'MAILPURSE_INLINE_EDIT_REPLACED_WITH_SVG') {
                if (!persistInlineSnapshot(event.data.snapshot)) replacePreviewElement(event.data.path, event.data.outerHTML);
                setInlineEditTarget({ ...event.data.info, rect: event.data.rect });
            }
        };
        window.addEventListener('message', handleMessage);
        return () => window.removeEventListener('message', handleMessage);
    }, [persistInlineSnapshot, replacePreviewElement, updateElementTextInPreview, updatePreviewImageSource, propagateImageChangeToProjectFiles]);

    useEffect(() => {
        setPreviewSelectionMode(selectionModeActive);
    }, [selectionModeActive, setPreviewSelectionMode, previewUrl, previewHtml, previewRefreshKey]);

    useEffect(() => {
        setPreviewInlineEditMode(inlineEditMode);
    }, [inlineEditMode, setPreviewInlineEditMode, previewUrl, previewHtml, previewRefreshKey]);

    const handleClearSelectedElements = useCallback(() => {
        setSelectedElements([]);
        setMessages((prev) => {
            const lastSelectionIdx = prev.findLastIndex((m) => m.role === 'user' && m.id?.startsWith('sel-'));
            if (lastSelectionIdx >= 0) {
                return prev.filter((_, i) => i !== lastSelectionIdx);
            }
            return prev;
        });
        const iframe = previewIframeRef.current;
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.__mailpurseClearSelection?.();
            } catch (e) {
                // ignore cross-origin or sandbox issues
            }
        }
    }, []);

    const handlePreviewSelection = useCallback(() => {
        setSelectionModeActive((prev) => {
            if (prev) {
                handleClearSelectedElements();
            }
            return !prev;
        });
        setPreviewEditMode(false);
        setPreviewEditText('');
        setPreviewSelectionText('');
    }, [handleClearSelectedElements]);

    const handlePreviewEditStart = useCallback(() => {
        setInlineEditMode((prev) => {
            if (prev) {
                setInlineEditTarget(null);
                return false;
            }
            setSelectionModeActive(false);
            setPreviewEditMode(false);
            setPreviewEditText('');
            setPreviewSelectionText('');
            return true;
        });
    }, []);

    const handlePreviewEditCancel = useCallback(async () => {
        setInlineEditMode(false);
        setInlineEditTarget(null);
        if (saveSessionTimeoutRef.current) {
            clearTimeout(saveSessionTimeoutRef.current);
            saveSessionTimeoutRef.current = null;
        }
        if (sessionId && saveSessionRef.current) {
            const payload = {
                messages: serializeMessagesForSaveRef.current(messagesRef.current),
                preview_html: previewHtmlRef.current || previewHtml || null,
                project_files: projectFiles.length ? projectFiles : null,
                react_code: reactCode || null,
                preview_mode: previewMode || 'html',
                preview_title: previewTitle || null,
                page_title: pageTitle || null,
                page_slug: pageSlug || null,
                page_status: pageStatus || 'draft',
                model: model || null,
                app_meta: {
                    app_title: appMetaTitle || '',
                    app_logo: appMetaLogo || '',
                    app_icon: appMetaIcon || '',
                    meta_description: appMetaDescription || '',
                    social_image: appMetaSocialImage || '',
                    style_overrides: styleOverrides,
                    inline_preview_dirty: hasInlinePreviewEdits,
                },
            };
            await saveSessionRef.current(sessionId, payload);
        }
        await handleSaveRef.current?.({ silent: true });
        if (hasInlinePreviewEdits) {
            const committed = previewHtmlRef.current || previewHtml || '';
            if (committed) {
                setShowInlineSnapshot(true);
                setPreviewHtml(committed);
            }
        }
    }, [sessionId, previewHtml, previewHtmlRef, projectFiles, reactCode, previewMode, previewTitle, pageTitle, pageSlug, pageStatus, model, appMetaTitle, appMetaLogo, appMetaIcon, appMetaDescription, appMetaSocialImage, styleOverrides, hasInlinePreviewEdits]);

    const handlePreviewEditDelete = useCallback(() => {
        const path = inlineEditTarget?.path;
        const iframe = previewIframeRef.current;
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.postMessage({ type: 'MAILPURSE_DELETE_ELEMENT' }, '*');
            } catch (e) {
                // ignore
            }
        }
        setInlineEditTarget(null);
        if (path) {
            deleteElementFromPreview(path);
            setStyleOverrides((prev) => {
                const next = { ...prev };
                delete next[path];
                return next;
            });
        }
    }, [inlineEditTarget, deleteElementFromPreview]);

    const handleInlineEditStyleChange = useCallback((path, styles) => {
        if (!path) return;
        markInlinePreviewDirty();
        setStyleOverrides((prev) => ({
            ...prev,
            [path]: { ...(prev[path] || {}), ...styles },
        }));
    }, [markInlinePreviewDirty]);

    const handleSave = useCallback(async (options = {}) => {
        const svUrl = mountEl.current?.dataset?.saveUrl;
        if (!svUrl) return false;
        if (!previewHtml && !previewHtmlRef.current) return false;

        const nextStatus = typeof options.status === 'string' && options.status ? options.status : pageStatus;
        const nextVisibility = typeof options.visibility === 'string' && options.visibility ? options.visibility : appVisibility;
        const nextPassword = typeof options.password === 'string' ? options.password : publishPassword;

        setSaving(true);
        setError('');
        let success = false;
        try {
            const resp = await fetch(svUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    page_id: savedPageId || null,
                    title: pageTitle || previewTitle || 'Generated Page',
                    slug: (pageSlug || publishSlugValue || 'my-app').trim(),
                    html: previewHtmlRef.current || previewHtml,
                    html_content: previewHtmlRef.current || previewHtml,
                    project_files: projectFiles.length ? projectFiles : null,
                    react_code: reactCode || null,
                    preview_mode: previewMode,
                    description: appMetaDescription || '',
                    status: nextStatus,
                    publish_visibility: nextVisibility,
                    publish_password: nextVisibility === 'password' ? nextPassword : '',
                    app_title: appMetaTitle || '',
                    app_logo: appMetaLogo || '',
                    app_icon: appMetaIcon || '',
                    social_image: appMetaSocialImage || '',
                    app_meta: {
                        style_overrides: styleOverrides,
                        inline_preview_dirty: hasInlinePreviewEdits,
                    },
                    session_id: sessionId || null,
                }),
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data && data.success) {
                if (data.page?.slug) {
                    setPageSlug(data.page.slug);
                    setPublishSlugDraft(data.page.slug);
                }
                if (data.page?.status) {
                    setPageStatus(data.page.status);
                }
                if (data.page?.publish_visibility) {
                    setAppVisibility(data.page.publish_visibility);
                }
                setSaveSuccess(data.page || null);
                if (!options.silent) {
                    setMessages((prev) => [
                        ...prev,
                        {
                            id: `saved-${Date.now()}`,
                            role: 'assistant',
                            content: `Saved as "${data.page.title}".`,
                            status: 'ok',
                            created_at: new Date().toISOString(),
                        },
                    ]);
                }
                lastAutoSavedRef.current = {
                    projectFiles: getProjectFilesSignature(projectFiles),
                    previewHtml: previewHtmlRef.current || previewHtml || '',
                };
                success = true;
            } else {
                setError(data?.message || 'Failed to save page.');
            }
        } catch (e) {
            setError(e?.message || 'Failed to save page.');
        } finally {
            setSaving(false);
        }
        return success;
    }, [previewHtml, projectFiles, reactCode, previewMode, pageTitle, pageSlug, pageStatus, previewTitle, appVisibility, publishPassword, savedPageId, appMetaTitle, appMetaLogo, appMetaIcon, appMetaDescription, appMetaSocialImage, sessionId, getProjectFilesSignature, publishSlugValue]);

    handleSaveRef.current = handleSave;

    const updateCustomDomain = useCallback(async (domainOverride) => {
        if (!effectiveCustomDomainUrl || !savedPageId) return;
        const url = effectiveCustomDomainUrl.replace('__SESSION__', sessionId || savedPageId || '');
        if (!url) return;
        const domain = domainOverride !== undefined ? domainOverride : customDomain;
        setCustomDomainLoading(true);
        setCustomDomainError('');
        setCustomDomainSuccess('');
        try {
            const resp = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    page_id: savedPageId,
                    custom_domain: domain,
                }),
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                setCustomDomain(data.page?.custom_domain || '');
                setCustomDomainSuccess('Custom domain saved.');
                setSaveSuccess((prev) => prev ? { ...prev, custom_domain: data.page?.custom_domain } : null);
            } else {
                setCustomDomainError(data?.message || 'Failed to save custom domain.');
            }
        } catch (e) {
            setCustomDomainError(e?.message || 'Failed to save custom domain.');
        } finally {
            setCustomDomainLoading(false);
        }
    }, [effectiveCustomDomainUrl, savedPageId, sessionId, customDomain]);

    const stopGeneration = useCallback(() => {
        if (abortRef.current) {
            abortRef.current.abort();
        }
        if (sseAbortRef.current) {
            sseAbortRef.current.abort();
            sseAbortRef.current = null;
        }
        if (progressPollIntervalRef.current) {
            clearInterval(progressPollIntervalRef.current);
            progressPollIntervalRef.current = null;
        }
        if (fakeProgressIntervalRef.current) {
            clearInterval(fakeProgressIntervalRef.current);
            fakeProgressIntervalRef.current = null;
        }
        setLoading(false);
        setStatus('idle');
        setGenerationPhase(null);
        setProgressData(null);

        // Replace the "Thinking…" placeholder with a cancelled message
        setMessages((prev) => {
            const filtered = prev.filter((m) => m.id !== 'progress-thinking' && m.status !== 'progress');
            return [...filtered, {
                id: `cancelled-${Date.now()}`,
                role: 'assistant',
                content: 'Generation cancelled.',
                status: 'cancelled',
                created_at: new Date().toISOString(),
            }];
        });

        const cancelUrl = mountEl.current?.dataset?.cancelUrl;
        const activeSessionId = sessionId;
        if (cancelUrl && activeSessionId) {
            try {
                fetch(cancelUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ session_id: activeSessionId }),
                }).catch(() => {});
            } catch (_) {}
        }
    }, [sessionId]);

    const discuss = useCallback(async () => {
        setError('');
        const trimmedPrompt = String(prompt || '').trim();
        if (trimmedPrompt === '') {
            setError('Please enter a message to discuss.');
            return;
        }

        let activeSessionId = sessionId;
        if (!activeSessionId) {
            activeSessionId = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : `job-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;

            const showUrlTemplate = mountEl.current?.dataset?.sessionShowUrlTemplate;
            if (showUrlTemplate && window.history && window.history.replaceState) {
                window.history.replaceState({}, '', showUrlTemplate.replace('__SESSION__', activeSessionId));
            }

            const backendSessionId = await createSessionRef.current?.({
                messages: serializeMessagesForSaveRef.current(messages),
                model,
                session_id: activeSessionId,
            });
            if (backendSessionId) {
                activeSessionId = backendSessionId;
            }
        }

        const discussUrlTemplate = mountEl.current?.dataset?.discussUrlTemplate;
        if (!discussUrlTemplate) {
            setError('Discuss URL is not configured.');
            return;
        }
        const discussUrl = discussUrlTemplate.replace('__SESSION__', activeSessionId);

        const tempId = `discuss-${Date.now()}`;
        const optimisticUser = {
            id: tempId,
            role: 'user',
            content: trimmedPrompt,
            model,
            status: 'ok',
            created_at: new Date().toISOString(),
        };
        const loadingMsg = {
            id: `discuss-loading-${Date.now()}`,
            role: 'assistant',
            content: 'Thinking…',
            status: 'progress',
            action: 'thinking',
            created_at: new Date().toISOString(),
        };

        setMessages((prev) => [...prev, optimisticUser, loadingMsg]);
        setPrompt('');
        setScreenshot(null);
        setScreenshotPreviewUrl(null);
        setLoading(true);
        setStatus('discussing');

        try {
            const resp = await fetch(discussUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    prompt: trimmedPrompt,
                    model,
                    messages: serializeMessagesForSaveRef.current([...messages, optimisticUser]),
                }),
            });
            const data = await resp.json().catch(() => null);

            if (!resp.ok || !data || !data.success) {
                throw new Error(data?.message || 'Discussion failed.');
            }

            const newMessages = Array.isArray(data.messages) ? data.messages : [];
            setMessages((prev) => {
                const filtered = prev.filter((m) => m.id !== loadingMsg.id && m.id !== optimisticUser.id);
                return [...filtered, ...newMessages];
            });

            if (data.credit_status) {
                setAiBuilderCreditStatus(data.credit_status);
            } else {
                refreshCreditStatus();
            }
        } catch (e) {
            setMessages((prev) => prev.filter((m) => m.id !== loadingMsg.id));
            setError(e.message || 'Discussion failed.');
        } finally {
            setLoading(false);
            setStatus('idle');
        }
    }, [prompt, screenshot, sessionId, model, messages, refreshCreditStatus]);

    const loadShares = useCallback(async () => {
        if (!sessionId) return;
        if (!aiBuilderPermissions.canShareProject) return;
        const sharesUrl = mountEl.current?.dataset?.sharesUrl?.replace('__SESSION__', sessionId);
        if (!sharesUrl) return;

        setShareModalLoading(true);
        try {
            const resp = await fetch(sharesUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                setShareModalShares(data.shares || []);
                setShareModalOwner(data.owner || null);
                setShareModalIsOwner(data.is_owner || false);
                setCurrentUserRole(data.current_user_role || null);
                setShareModalInviteLink(data.invite_link || '');
            }
        } catch (e) {
            console.error('Failed to load shares:', e);
        } finally {
            setShareModalLoading(false);
        }
    }, [sessionId, aiBuilderPermissions.canShareProject]);

    const inviteCollaborator = useCallback(async () => {
        if (!sessionId || !shareModalInviteEmail) return;
        const inviteUrl = mountEl.current?.dataset?.sharesInviteUrl?.replace('__SESSION__', sessionId);
        if (!inviteUrl) return;

        setShareModalLoading(true);
        try {
            const resp = await fetch(inviteUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    email: shareModalInviteEmail,
                    role: shareModalInviteRole,
                }),
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                setShareModalInviteEmail('');
                await loadShares();
            } else {
                alert(data?.message || 'Failed to invite collaborator.');
            }
        } catch (e) {
            alert('Failed to invite collaborator.');
        } finally {
            setShareModalLoading(false);
        }
    }, [sessionId, shareModalInviteEmail, shareModalInviteRole, loadShares]);

    const updateCollaboratorRole = useCallback(async (shareId, newRole) => {
        if (!sessionId) return;
        const updateUrl = mountEl.current?.dataset?.sharesUpdateUrl?.replace('__SESSION__', sessionId).replace('__SHARE_ID__', shareId);
        if (!updateUrl) return;

        try {
            const resp = await fetch(updateUrl, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ role: newRole }),
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                await loadShares();
            }
        } catch (e) {
            console.error('Failed to update role:', e);
        }
    }, [sessionId, loadShares]);

    const removeCollaborator = useCallback(async (shareId) => {
        if (!sessionId) return;
        const removeUrl = mountEl.current?.dataset?.sharesRemoveUrl?.replace('__SESSION__', sessionId).replace('__SHARE_ID__', shareId);
        if (!removeUrl) return;

        if (!confirm('Are you sure you want to remove this collaborator?')) return;

        try {
            const resp = await fetch(removeUrl, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                await loadShares();
            }
        } catch (e) {
            console.error('Failed to remove collaborator:', e);
        }
    }, [sessionId, loadShares]);

    const [shareLinkCopied, setShareLinkCopied] = useState(false);
    const copyInviteLink = useCallback(() => {
        if (!shareModalInviteLink) return;
        navigator.clipboard.writeText(shareModalInviteLink);
        setShareLinkCopied(true);
        setTimeout(() => setShareLinkCopied(false), 2000);
    }, [shareModalInviteLink]);

    useEffect(() => {
        if (sessionId) {
            loadShares();
        }
    }, [sessionId, loadShares]);

    const loadSupabaseUsers = useCallback(async () => {
        if (!effectiveSupabaseUsersUrl) return;
        setSupabaseError(null);
        try {
            const resp = await fetch(effectiveSupabaseUsersUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                setSupabaseUsers(data.data?.users || data.data || []);
                setSupabaseError(null);
            } else {
                setSupabaseUsers([]);
                setSupabaseError(data?.error || 'Could not load Supabase users.');
            }
        } catch (e) {
            setSupabaseUsers([]);
            setSupabaseError(e?.message || 'Could not load Supabase users.');
        }
    }, [effectiveSupabaseUsersUrl]);

    const loadSupabaseBuckets = useCallback(async () => {
        if (!effectiveSupabaseBucketsUrl) return;
        setSupabaseError(null);
        try {
            const resp = await fetch(effectiveSupabaseBucketsUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                setSupabaseBuckets(data.data || []);
                setSupabaseError(null);
            } else {
                setSupabaseBuckets([]);
                setSupabaseError(data?.error || 'Could not load storage buckets.');
            }
        } catch (e) {
            setSupabaseBuckets([]);
            setSupabaseError(e?.message || 'Could not load storage buckets.');
        }
    }, [effectiveSupabaseBucketsUrl]);

    const loadSupabaseTables = useCallback(async () => {
        if (!effectiveSupabaseTablesUrl) return;
        setSupabaseError(null);
        try {
            const resp = await fetch(effectiveSupabaseTablesUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                setSupabaseTables(data.data || []);
                setSupabaseError(null);
            } else {
                setSupabaseTables([]);
                setSupabaseError(data?.error || 'Could not load database tables.');
            }
        } catch (e) {
            setSupabaseTables([]);
            setSupabaseError(e?.message || 'Could not load database tables.');
        }
    }, [effectiveSupabaseTablesUrl]);

    const loadSupabaseTableData = useCallback(async (table) => {
        if (!effectiveSupabaseTableDataUrl || !table) return;
        setSupabaseTableDataLoading(true);
        setSupabaseTableDataError(null);
        try {
            const url = `${effectiveSupabaseTableDataUrl}${effectiveSupabaseTableDataUrl.includes('?') ? '&' : '?'}table=${encodeURIComponent(table)}&limit=100`;
            const resp = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                setSupabaseTableData(data.data || []);
                setSupabaseTableDataError(null);
            } else {
                setSupabaseTableData([]);
                setSupabaseTableDataError(data?.error || 'Could not load table data.');
            }
        } catch (e) {
            setSupabaseTableData([]);
            setSupabaseTableDataError(e?.message || 'Could not load table data.');
        } finally {
            setSupabaseTableDataLoading(false);
        }
    }, [effectiveSupabaseTableDataUrl]);

    const loadSupabaseIntegration = useCallback(async () => {
        try {
            const [integrationResp, configsResp] = await Promise.all([
                effectiveSupabaseUrl
                    ? fetch(effectiveSupabaseUrl, {
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    })
                    : Promise.resolve(null),
                effectiveSupabaseConfigsListUrl
                    ? fetch(effectiveSupabaseConfigsListUrl, {
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    })
                    : Promise.resolve(null),
            ]);
            const data = integrationResp ? await integrationResp.json().catch(() => null) : null;
            const configsData = configsResp ? await configsResp.json().catch(() => null) : null;
            if (configsData?.success) {
                setSupabaseConfigs(configsData.configs || []);
            }
            if (data && integrationResp.ok && data?.success) {
                if (data.capabilities) {
                    setSupabaseCapabilities(data.capabilities);
                }
                if (data.integration) {
                    setSupabaseIntegration(data.integration);
                    setSupabaseForm({
                        mode: data.integration.mode || 'byos_manual',
                        connection_type: data.integration.connection_type || 'hosted',
                        admin_supabase_config_id: data.integration.admin_supabase_config_id || '',
                        project_url: data.integration.project_url || '',
                        project_id: data.integration.project_id || '',
                        publishable_key: '',
                        secret_key: '',
                        db_password: '',
                        region: data.integration.region || '',
                    });

                    await Promise.allSettled([
                        loadSupabaseUsers(),
                        loadSupabaseBuckets(),
                        loadSupabaseTables(),
                    ]);
                } else if (effectiveCustomerSupabaseStatusUrl) {
                    // Fall back to customer-level integration
                    const customerResp = await fetch(effectiveCustomerSupabaseStatusUrl, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    });
                    const customerData = await customerResp.json().catch(() => null);
                    if (customerResp.ok && customerData?.success && customerData.integration) {
                        setSupabaseIntegration(customerData.integration);
                        await Promise.allSettled([
                            loadSupabaseUsers(),
                            loadSupabaseBuckets(),
                            loadSupabaseTables(),
                        ]);
                    }
                }
            } else if (effectiveCustomerSupabaseStatusUrl) {
                // No page-level URL — try customer-level directly
                const customerResp = await fetch(effectiveCustomerSupabaseStatusUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });
                const customerData = await customerResp.json().catch(() => null);
                if (customerResp.ok && customerData?.success && customerData.integration) {
                    setSupabaseIntegration(customerData.integration);
                    await Promise.allSettled([
                        loadSupabaseUsers(),
                        loadSupabaseBuckets(),
                        loadSupabaseTables(),
                    ]);
                }
            }
        } catch (e) {
            // ignore
        }
    }, [effectiveSupabaseUrl, effectiveSupabaseConfigsListUrl, effectiveCustomerSupabaseStatusUrl, loadSupabaseUsers, loadSupabaseBuckets, loadSupabaseTables]);

    const connectSupabase = useCallback(async () => {
        if (!effectiveSupabaseUrl) return;
        setSupabaseLoading(true);
        setSupabaseError(null);
        try {
            const payload = {
                ...supabaseForm,
                admin_supabase_config_id: supabaseForm.admin_supabase_config_id || null,
            };
            const resp = await fetch(effectiveSupabaseUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success) {
                setSupabaseIntegration(data.integration);
                setSupabaseForm((prev) => ({
                    ...prev,
                    publishable_key: '',
                    secret_key: '',
                    db_password: '',
                }));

                await Promise.allSettled([
                    loadSupabaseUsers(),
                    loadSupabaseBuckets(),
                    loadSupabaseTables(),
                ]);
            } else {
                setSupabaseError(data?.error || data?.message || 'Failed to connect Supabase.');
            }
        } catch (e) {
            setSupabaseError(e?.message || 'Failed to connect Supabase.');
        } finally {
            setSupabaseLoading(false);
        }
    }, [effectiveSupabaseUrl, supabaseForm]);

    const connectSupabaseOAuth = useCallback(async () => {
        if (!effectiveSupabaseOAuthRedirectUrl || !savedPageId) return;
        setSupabaseLoading(true);
        setSupabaseError(null);
        try {
            const resp = await fetch(effectiveSupabaseOAuthRedirectUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    target_type: 'page',
                    target_id: savedPageId,
                    session_id: sessionId || null,
                }),
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data?.success && data?.url) {
                window.location.href = data.url;
                return;
            }
            setSupabaseError(data?.error || 'Could not start Supabase OAuth flow.');
        } catch (e) {
            setSupabaseError(e?.message || 'Could not start Supabase OAuth flow.');
        } finally {
            setSupabaseLoading(false);
        }
    }, [effectiveSupabaseOAuthRedirectUrl, savedPageId, sessionId]);

    const disconnectSupabase = useCallback(async () => {
        if (!effectiveSupabaseUrl) return;
        try {
            await fetch(effectiveSupabaseUrl, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            setSupabaseIntegration(null);
            setSupabaseForm({
                mode: 'byos_manual',
                connection_type: 'hosted',
                admin_supabase_config_id: '',
                project_url: '',
                project_id: '',
                publishable_key: '',
                secret_key: '',
                db_password: '',
                region: '',
            });
            setSupabaseConfigs([]);
        } catch (e) {
            // ignore
        }
    }, [effectiveSupabaseUrl]);

    useEffect(() => {
        if (activeRightTab === 'dashboard') {
            loadSupabaseIntegration();
        }
    }, [activeRightTab, loadSupabaseIntegration]);

    const [leftPanelWidth, setLeftPanelWidth] = useState(420);
    const [leftPanelCollapsed, setLeftPanelCollapsed] = useState(false);
    const [isResizingPanels, setIsResizingPanels] = useState(false);
    const dragRef = useRef(false);

    // Read session from URL path and initial data
    useEffect(() => {
        const el = document.getElementById('page-ai-builder-react');
        if (!el) return;

        sessionStoreUrlRef.current = el.dataset.sessionStoreUrl || '';
        sessionUpdateUrlRef.current = el.dataset.sessionUpdateUrl || '';
        sessionCompileUrlTemplateRef.current = el.dataset.sessionCompileUrlTemplate || '';
        sessionPreviewUrlTemplateRef.current = el.dataset.sessionPreviewUrlTemplate || '';
        starterFilesUrlRef.current = el.dataset.starterFilesUrl || '';
        iterateUrlTemplateRef.current = el.dataset.iterateUrlTemplate || '';
        suggestionsUrlRef.current = el.dataset.suggestionsUrl || '';
        creditStatusUrlRef.current = el.dataset.creditStatusUrl || '';

        const initialSessionRaw = el.dataset.initialSession;
        let loadedProjectFiles = null;
        if (initialSessionRaw && initialSessionRaw !== 'null') {
            try {
                const initial = JSON.parse(initialSessionRaw);
                if (initial && typeof initial === 'object') {
                    if (Array.isArray(initial.messages) && initial.messages.length > 0) {
                        // Restore messages without closures
                        setMessages(initial.messages.map((m) => ({
                            ...m,
                            onView: m.html ? () => {
                                setShowPreview(true);
                                setActiveRightTab('preview');
                            } : undefined,
                        })));
                    }
                    if (initial.preview_html) {
                        setPreviewHtml(initial.preview_html);
                        previewHtmlRef.current = initial.preview_html;
                        inlineEditsActiveRef.current = false;
                        setPreviewTheme(extractReactThemeFromHtml(initial.preview_html));
                        setShowPreview(true);
                    }
                    if (Array.isArray(initial.project_files) && initial.project_files.length > 0) {
                        loadedProjectFiles = initial.project_files;
                        setProjectFiles(initial.project_files);
                        lastCompiledSignatureRef.current = getProjectFilesSignature(initial.project_files);
                    }
                    lastAutoSavedRef.current = {
                        projectFiles: getProjectFilesSignature(loadedProjectFiles || initial.project_files || []),
                        previewHtml: initial.preview_html || '',
                    };
                    if (initial.react_code) {
                        setReactCode(initial.react_code);
                        setGeneratedReactCode(initial.react_code);
                    }
                    if (initial.preview_mode) {
                        setPreviewMode(initial.preview_mode);
                    }
                    if (initial.preview_title) {
                        setPreviewTitle(initial.preview_title);
                        setPageTitle(initial.preview_title);
                    }
                    if (initial.page_title) {
                        setPageTitle(initial.page_title);
                    }
                    if (initial.page_slug) {
                        setPageSlug(initial.page_slug);
                    }
                    if (initial.page_status) {
                        setPageStatus(initial.page_status);
                    }
                    if (initial.model) {
                        setModel(initial.model);
                    }
                    if (initial.app_meta && typeof initial.app_meta === 'object') {
                        setAppMetaTitle(initial.app_meta.app_title || '');
                        setAppMetaLogo(initial.app_meta.app_logo || '');
                        setAppMetaIcon(initial.app_meta.app_icon || '');
                        setAppMetaDescription(initial.app_meta.meta_description || '');
                        setAppMetaSocialImage(initial.app_meta.social_image || '');
                        setStyleOverrides(initial.app_meta.style_overrides && typeof initial.app_meta.style_overrides === 'object' ? initial.app_meta.style_overrides : {});
                        setHasInlinePreviewEdits(Boolean(initial.app_meta.inline_preview_dirty));
                        setShowInlineSnapshot(Boolean(initial.app_meta.inline_preview_dirty) && Boolean(initial.preview_html));
                    }
                    const initialInferredWorkspaceTitle = inferWorkspaceTitle(initial.project_files || []);
                    if (initialInferredWorkspaceTitle && isGenericWorkspaceTitle(initial.app_meta?.app_title || '')) {
                        setAppMetaTitle(initialInferredWorkspaceTitle);
                    }
                    if (initial.session_id) {
                        setSessionId(initial.session_id);
                        setPreviewUrl(getPreviewUrlForSession(initial.session_id));
                        hasCreatedSessionRef.current = true;
                    }
                    if (initial.page_id) {
                        setInitialPageId(initial.page_id);
                    }
                }
            } catch (e) {
                // ignore parse errors
            }
        }

        if (starterFilesUrlRef.current) {
            fetch(starterFilesUrlRef.current, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
                .then((res) => res.json().catch(() => null))
                .then((data) => {
                    if (!data || !data.success || !Array.isArray(data.project_files) || data.project_files.length === 0) {
                        return;
                    }
                    starterFilesRef.current = data.project_files;
                    const starterMap = {};
                    for (const file of data.project_files) {
                        if (file && file.path) {
                            starterMap[file.path] = file;
                        }
                    }
                    const currentMap = {};
                    const currentFiles = loadedProjectFiles && loadedProjectFiles.length > 0 ? loadedProjectFiles : [];
                    for (const file of currentFiles) {
                        if (file && file.path) {
                            currentMap[file.path] = file;
                        }
                    }
                    const merged = { ...starterMap, ...currentMap };
                    const mergedFiles = Object.values(merged);
                    setProjectFiles(mergedFiles);
                    lastCompiledSignatureRef.current = getProjectFilesSignature(mergedFiles);
                    lastAutoSavedRef.current = {
                        projectFiles: getProjectFilesSignature(mergedFiles),
                        previewHtml: initial.preview_html || '',
                    };
                })
                .catch(() => {
                    // Silent fallback: Code tab will show whatever was loaded from the session
                });
        }
    }, [getPreviewUrlForSession, getProjectFilesSignature]);

    // Auto-start generation/discussion when arriving via the AI Prompt Input element (?auto=1)
    useEffect(() => {
        if (!shouldAutoStart || autoStartedRef.current) return;
        if (!String(prompt || '').trim()) return;
        autoStartedRef.current = true;
        const timer = setTimeout(() => {
            if (initialDiscussMode) {
                discussRef.current?.();
            } else {
                generateRef.current?.();
            }
        }, 0);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const serializeMessagesForSave = useCallback((msgs) => {
        return msgs.map((m) => ({
            id: m.id,
            role: m.role,
            content: m.content,
            html: m.html || null,
            model: m.model || null,
            status: m.status || 'ok',
            type: m.type || null,
            file: m.file || null,
            action: m.action || null,
            description: m.description || null,
            detail: m.detail || null,
            thought: m.thought || null,
            created_at: m.created_at || new Date().toISOString(),
        }));
    }, []);
    serializeMessagesForSaveRef.current = serializeMessagesForSave;

    const formatProgressMessage = useCallback((message) => {
        const raw = String(message || '').trim();
        return raw || 'Working...';
    }, []);
    formatProgressMessageRef.current = formatProgressMessage;

    const applyGeneratedResult = useCallback((data, sid = '') => {
        if (sseAbortRef.current) {
            sseAbortRef.current.abort();
            sseAbortRef.current = null;
        }
        if (progressPollIntervalRef.current) {
            clearInterval(progressPollIntervalRef.current);
            progressPollIntervalRef.current = null;
        }

        // Refresh credit status after generation completes (credits are deducted in the background job)
        refreshCreditStatus();

        const resolvedSessionId = sid || data.sandbox_session_id || data.session_id || '';

        if (resolvedSessionId && !sessionId) {
            setSessionId(resolvedSessionId);
        }
        if (resolvedSessionId) {
            hasCreatedSessionRef.current = true;
        }

        setLoading(false);
        setGenerationPhase(null);
        setStatus('idle');

        // Chat-type response (AI didn't generate a page, just replied conversationally)
        if (data.type === 'chat') {
            const chatMsg = {
                id: `chat-${Date.now()}`,
                role: 'assistant',
                content: data.message || '',
                model: data.model_used || model,
                status: 'ok',
                created_at: new Date().toISOString(),
            };
            setMessages((prev) => [
                ...prev.filter((m) => m.status !== 'progress' && m.status !== 'thought'),
                chatMsg,
            ]);
            return;
        }

        const nextReactCode = data.react_code || '';
        const nextPreviewMode = data.preview_mode || (nextReactCode ? 'react' : 'html');
        const rawPreviewHtml = data.preview_html || data.html || '';
        const browserPreviewUrl = resolvedSessionId ? getPreviewUrlForSession(resolvedSessionId) : '';
        const browserPreviewHtml = rewritePreviewAssetUrls(rawPreviewHtml, browserPreviewUrl);
        const nextPreviewHtml = nextPreviewMode === 'react' && !data.preview_url
            ? ''
            : browserPreviewHtml;
        const nextTheme = extractReactThemeFromHtml(nextPreviewHtml || rawPreviewHtml);
        const nextProjectFiles = Array.isArray(data.project_files) ? data.project_files : [];
        const nextInferredWorkspaceTitle = inferWorkspaceTitle(nextProjectFiles);
        const nextAppMetaTitle = cleanWorkspaceTitle(data.app_meta?.app_title);
        const nextPreviewUrl = nextPreviewMode === 'react' && resolvedSessionId
            ? getPreviewUrlForSession(resolvedSessionId)
            : (data.preview_url || '');

        setHasInlinePreviewEdits(false);
        setShowInlineSnapshot(false);
        setStyleOverrides({});
        setPreviewHtml(nextPreviewHtml);
        previewHtmlRef.current = nextPreviewHtml || '';
        inlineEditsActiveRef.current = false;
        setPreviewUrl(nextPreviewUrl);
        setProjectFiles(nextProjectFiles);
        setBuildLog('');
        setReactCode(nextReactCode);
        setGeneratedReactCode(nextReactCode);
        setPreviewMode(nextPreviewMode);
        setPreviewTheme(nextTheme);
        setPreviewTitle(data.title || 'Generated Page');
        setPageTitle(data.title || 'Generated Page');
        if (!isGenericWorkspaceTitle(nextAppMetaTitle)) {
            setAppMetaTitle(nextAppMetaTitle);
        } else if (nextInferredWorkspaceTitle) {
            setAppMetaTitle(nextInferredWorkspaceTitle);
        }
        if (data.app_meta?.app_logo) {
            setAppMetaLogo(data.app_meta.app_logo);
        }
        if (data.app_meta?.app_icon) {
            setAppMetaIcon(data.app_meta.app_icon);
        }
        if (typeof data.app_meta?.meta_description === 'string') {
            setAppMetaDescription(data.app_meta.meta_description);
        }
        if (data.app_meta?.social_image) {
            setAppMetaSocialImage(data.app_meta.social_image);
        }
        setPageSlug('');
        setActivePreviewPage('/');
        setShowPreview(true);
        setActiveRightTab('preview');
        lastCompiledSignatureRef.current = getProjectFilesSignature(nextProjectFiles);

        const assistantMsg = {
            id: `ok-${Date.now()}`,
            role: 'assistant',
            content: `Generated "${data.title || 'Page'}". Preview it on the right and save when ready.`,
            html: nextPreviewHtml,
            model: data.model_used || model,
            status: 'ok',
            created_at: new Date().toISOString(),
            onView: () => {
                setShowPreview(true);
                setActiveRightTab('preview');
            },
        };

        setMessages((prev) => {
            const last = prev[prev.length - 1];
            if (last && last.role === 'assistant' && last.status === 'ok' && last.html === nextPreviewHtml) {
                return prev;
            }
            return [...prev, assistantMsg];
        });
    }, [getPreviewUrlForSession, getProjectFilesSignature, model, sessionId, refreshCreditStatus]);
    applyGeneratedResultRef.current = applyGeneratedResult;

    const saveSession = useCallback(async (sid, payload) => {
        if (!sid) return;
        const updateUrl = sessionUpdateUrlRef.current;
        if (!updateUrl) return;
        try {
            await fetch(updateUrl, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });
        } catch (e) {
            // Silently fail autosave
        }
    }, []);
    saveSessionRef.current = saveSession;

    const saveAppMeta = useCallback(async () => {
        if (!sessionId) return false;
        const updateUrl = sessionUpdateUrlRef.current;
        if (!updateUrl) return false;
        setAppMetaSaveStatus('saving');
        try {
            const resp = await fetch(updateUrl, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    app_meta: {
                        app_title: appMetaTitle || '',
                        app_logo: appMetaLogo || '',
                        app_icon: appMetaIcon || '',
                        meta_description: appMetaDescription || '',
                        social_image: appMetaSocialImage || '',
                        style_overrides: styleOverrides,
                        inline_preview_dirty: hasInlinePreviewEdits,
                    },
                }),
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data && data.success) {
                setAppMetaSaveStatus('saved');
                setTimeout(() => setAppMetaSaveStatus((current) => (current === 'saved' ? 'idle' : current)), 2000);
                return true;
            }
            setAppMetaSaveStatus('error');
            return false;
        } catch (e) {
            setAppMetaSaveStatus('error');
            return false;
        }
    }, [sessionId, appMetaTitle, appMetaLogo, appMetaIcon, appMetaDescription, appMetaSocialImage, styleOverrides, hasInlinePreviewEdits]);
    saveAppMetaRef.current = saveAppMeta;

    useEffect(() => {
        const nextReactCode = getPrimaryReactCodeFromFiles(projectFiles);
        setReactCode((prev) => (prev === nextReactCode ? prev : nextReactCode));
    }, [projectFiles, getPrimaryReactCodeFromFiles]);

    // Auto-save page edits after the user pauses typing
    useEffect(() => {
        if (!savedPageId) return;
        if (loading || status === 'generating') return;
        if (!previewHtml && !previewHtmlRef.current && !projectFiles.length) return;

        const currentProjectSig = getProjectFilesSignature(projectFiles);
        const currentPreviewHtml = previewHtmlRef.current || previewHtml || '';
        const lastSig = lastAutoSavedRef.current;

        if (currentProjectSig === lastSig.projectFiles && currentPreviewHtml === lastSig.previewHtml) {
            return;
        }

        if (saveStatusTimeoutRef.current) {
            clearTimeout(saveStatusTimeoutRef.current);
            saveStatusTimeoutRef.current = null;
        }
        setSaveStatus('idle');
        if (autoSaveTimeoutRef.current) {
            clearTimeout(autoSaveTimeoutRef.current);
        }

        autoSaveTimeoutRef.current = setTimeout(() => {
            (async () => {
                setSaveStatus('saving');
                const ok = await handleSave({ status: pageStatus, silent: true });
                if (ok) {
                    lastAutoSavedRef.current = {
                        projectFiles: getProjectFilesSignature(projectFiles),
                        previewHtml: previewHtmlRef.current || previewHtml || '',
                    };
                    setSaveStatus('saved');
                    saveStatusTimeoutRef.current = setTimeout(() => {
                        setSaveStatus((current) => (current === 'saved' ? 'idle' : current));
                        saveStatusTimeoutRef.current = null;
                    }, 2000);
                } else {
                    setSaveStatus('error');
                }
            })();
        }, 1200);

        return () => {
            if (autoSaveTimeoutRef.current) {
                clearTimeout(autoSaveTimeoutRef.current);
                autoSaveTimeoutRef.current = null;
            }
            if (saveStatusTimeoutRef.current) {
                clearTimeout(saveStatusTimeoutRef.current);
                saveStatusTimeoutRef.current = null;
            }
        };
    }, [savedPageId, loading, status, previewHtml, inlineEditVersion, projectFiles, pageStatus, handleSave, getProjectFilesSignature]);

    const debouncedSaveSession = useCallback((sid, payload) => {
        if (saveSessionTimeoutRef.current) {
            clearTimeout(saveSessionTimeoutRef.current);
        }
        saveSessionTimeoutRef.current = setTimeout(() => {
            saveSession(sid, payload);
        }, 1500);
    }, [saveSession]);

    const createSession = useCallback(async (payload) => {
        if (hasCreatedSessionRef.current) return null;
        const storeUrl = sessionStoreUrlRef.current;
        if (!storeUrl) return null;
        try {
            const resp = await fetch(storeUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await resp.json().catch(() => null);
            if (resp.ok && data && data.success && data.session_id) {
                hasCreatedSessionRef.current = true;
                setSessionId(data.session_id);
                sessionUpdateUrlRef.current = data.update_url || '';
                // Update browser URL without reload
                if (data.url && window.history && window.history.replaceState) {
                    window.history.replaceState({}, '', data.url);
                }
                return data.session_id;
            }
        } catch (e) {
            // ignore
        }
        return null;
    }, []);
    createSessionRef.current = createSession;
    generateRef.current = generate;
    discussRef.current = discuss;

    // Autosave session whenever state changes
    useEffect(() => {
        if (!sessionId) return;
        const payload = {
            messages: serializeMessagesForSave(messages),
            preview_html: previewHtmlRef.current || previewHtml || null,
            project_files: projectFiles.length ? projectFiles : null,
            react_code: reactCode || null,
            preview_mode: previewMode || 'html',
            preview_title: previewTitle || null,
            page_title: pageTitle || null,
            page_slug: pageSlug || null,
            page_status: pageStatus || 'draft',
            model: model || null,
            app_meta: {
                app_title: appMetaTitle || '',
                app_logo: appMetaLogo || '',
                app_icon: appMetaIcon || '',
                meta_description: appMetaDescription || '',
                social_image: appMetaSocialImage || '',
                style_overrides: styleOverrides,
                inline_preview_dirty: hasInlinePreviewEdits,
            },
        };
        debouncedSaveSession(sessionId, payload);
    }, [sessionId, messages, previewHtml, inlineEditVersion, projectFiles, reactCode, previewMode, previewTitle, pageTitle, pageSlug, pageStatus, model, appMetaTitle, appMetaLogo, appMetaIcon, appMetaDescription, appMetaSocialImage, styleOverrides, hasInlinePreviewEdits]);

    useEffect(() => {
        if (!sessionId) return undefined;

        const flushLatestSessionState = () => {
            if (saveSessionTimeoutRef.current) {
                clearTimeout(saveSessionTimeoutRef.current);
                saveSessionTimeoutRef.current = null;
            }
            if (!saveSessionRef.current) return;
            saveSessionRef.current(sessionId, {
                messages: serializeMessagesForSaveRef.current(messagesRef.current),
                preview_html: previewHtmlRef.current || previewHtml || null,
                project_files: projectFiles.length ? projectFiles : null,
                react_code: reactCode || null,
                preview_mode: previewMode || 'html',
                preview_title: previewTitle || null,
                page_title: pageTitle || null,
                page_slug: pageSlug || null,
                page_status: pageStatus || 'draft',
                model: model || null,
                app_meta: {
                    app_title: appMetaTitle || '',
                    app_logo: appMetaLogo || '',
                    app_icon: appMetaIcon || '',
                    meta_description: appMetaDescription || '',
                    social_image: appMetaSocialImage || '',
                    style_overrides: styleOverrides,
                    inline_preview_dirty: hasInlinePreviewEdits,
                },
            });
        };

        window.addEventListener('pagehide', flushLatestSessionState);
        window.addEventListener('beforeunload', flushLatestSessionState);

        return () => {
            window.removeEventListener('pagehide', flushLatestSessionState);
            window.removeEventListener('beforeunload', flushLatestSessionState);
        };
    }, [sessionId, previewHtml, projectFiles, reactCode, previewMode, previewTitle, pageTitle, pageSlug, pageStatus, model, appMetaTitle, appMetaLogo, appMetaIcon, appMetaDescription, appMetaSocialImage, styleOverrides, hasInlinePreviewEdits]);

    // Auto-save app branding changes after the user pauses editing
    useEffect(() => {
        if (!sessionId) return undefined;
        if (!saveAppMetaRef.current) return undefined;

        const timeout = setTimeout(() => {
            saveAppMetaRef.current();
        }, 1000);

        return () => clearTimeout(timeout);
    }, [sessionId, appMetaTitle, appMetaLogo, appMetaIcon, appMetaDescription, appMetaSocialImage]);

    const hasReactWorkspace = String(reactCode || '').trim() !== '' || projectFiles.some((file) => (
        typeof file?.path === 'string' && /\.(jsx|tsx)$/i.test(file.path)
    ));
    const hasPreviewContent = Boolean(previewUrl || previewHtml);
    const detectedComponentName = detectReactComponentName(reactCode);
    const hasLiveCodeChanges = hasReactWorkspace && generatedReactCode !== '' && reactCode !== generatedReactCode;
    const canSavePage = Boolean(previewHtml) && (!hasReactWorkspace || Boolean(detectedComponentName));
    const virtualFiles = useMemo(() => {
        return [
            ...projectFiles
                .filter((file) => file && typeof file.path === 'string')
                .map((file) => ({
                    ...file,
                    label: file.path.split('/').pop() || file.path,
                    type: /\.(jsx|tsx)$/i.test(file.path) ? 'react' : /\.html?$/i.test(file.path) ? 'html' : /\.json$/i.test(file.path) ? 'json' : 'file',
                })),
            {
                path: 'preview/runtime.html',
                label: 'runtime.html',
                type: 'html',
                content: previewHtml || '<!-- Live preview document will appear here after generation. -->',
            },
            {
                path: 'mailpurse/session.json',
                label: 'session.json',
                type: 'json',
                content: JSON.stringify({
                    session_id: sessionId || null,
                    model,
                    preview_mode: previewMode,
                    title: pageTitle || previewTitle || null,
                    slug: pageSlug || null,
                    status: pageStatus,
                    preview_url: previewUrl || null,
                    has_react_code: Boolean(reactCode),
                    has_live_edits: hasLiveCodeChanges,
                    is_compiling: isCompilingSandbox,
                    build_log: buildLog || null,
                }, null, 2),
            },
        ];
    }, [projectFiles, previewHtml, sessionId, model, previewMode, pageTitle, previewTitle, pageSlug, pageStatus, previewUrl, reactCode, hasLiveCodeChanges, isCompilingSandbox, buildLog]);
    const selectedFile = virtualFiles.find((file) => file.path === selectedFilePath) || virtualFiles[0] || null;
    const editableFile = projectFiles.find((file) => file.path === activeFileTabPath) || projectFiles.find((file) => /\.(jsx|tsx|js|ts|css|html)$/i.test(file.path)) || null;
    const editableFileRef = useRef(null);
    editableFileRef.current = editableFile;
    const primaryPageFile = projectFiles.find((file) => typeof file?.path === 'string' && /^src\/pages\/.*\.(jsx|tsx)$/i.test(file.path)) || null;

    const updateProjectFile = useCallback((filePath, nextContent) => {
        setProjectFiles((prev) => prev.map((file) => (
            file.path === filePath
                ? { ...file, content: nextContent }
                : file
        )));
    }, []);

    const openFileTab = useCallback((filePath) => {
        if (!filePath) return;
        setOpenFileTabs((prev) => {
            if (prev.includes(filePath)) return prev;
            return [...prev, filePath];
        });
        setActiveFileTabPath(filePath);
        setSelectedFilePath(filePath);
    }, []);

    const handlePreviewEditCode = useCallback(() => {
        setActiveRightTab('code');
        const path = activePreviewPage || '/';
        const filePath = projectFiles.find((f) => f.path === path || f.path === `${path}.jsx` || f.path === 'index.jsx' || f.path === 'App.jsx')?.path || (projectFiles[0]?.path || '');
        if (filePath) openFileTab(filePath);
    }, [activePreviewPage, projectFiles, openFileTab]);

    const closeFileTab = useCallback((filePath) => {
        setOpenFileTabs((prev) => {
            const next = prev.filter((p) => p !== filePath);
            return next;
        });
        setActiveFileTabPath((currentActive) => {
            if (currentActive !== filePath) return currentActive;
            const idx = openFileTabs.indexOf(filePath);
            const fallback = openFileTabs[idx - 1] || openFileTabs[idx + 1] || '';
            setSelectedFilePath(fallback);
            return fallback;
        });
    }, [openFileTabs]);

    const compileSandbox = useCallback(async (filesOverride = null, autoRetry = false, sessionOverride = '') => {
        const sid = sessionOverride || sessionId || (await createSession({
            messages: serializeMessagesForSave(messages),
            preview_html: previewHtmlRef.current || previewHtml || null,
            project_files: projectFiles.length ? projectFiles : null,
            react_code: reactCode || null,
            preview_mode: previewMode || 'html',
            preview_title: previewTitle || null,
            page_title: pageTitle || null,
            page_slug: pageSlug || null,
            page_status: pageStatus || 'draft',
            model: model || null,
        }));

        if (!sid) {
            return;
        }

        const compileUrl = getCompileUrlForSession(sid);
        if (!compileUrl) {
            return;
        }

        const filesToCompile = Array.isArray(filesOverride) ? filesOverride : projectFiles;
        if (!filesToCompile.length) {
            return;
        }

        setIsCompilingSandbox(true);
        setError('');

        try {
            const resp = await fetch(compileUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    project_files: filesToCompile,
                    react_code: getPrimaryReactCodeFromFiles(filesToCompile),
                    preview_title: previewTitle || pageTitle || 'Generated Page',
                    allow_fix: Boolean(autoRetry),
                }),
            });

            const data = await resp.json().catch(() => null);
            if (!resp.ok || !data || data.success !== true) {
                const msg = data?.message || data?.error || 'Vite sandbox compilation failed.';
                const log = data?.build_log || data?.error || '';
                setBuildLog(log || msg);
                setHasInlinePreviewEdits(false);
                setPreviewUrl('');
                setActiveRightTab('preview');
                return;
            }

            if (Array.isArray(data.project_files)) {
                setProjectFiles(data.project_files);
                lastCompiledSignatureRef.current = getProjectFilesSignature(data.project_files);
            }
            if (typeof data.preview_html === 'string') {
                const browserPreviewUrl = getPreviewUrlForSession(sid);
                const browserPreviewHtml = rewritePreviewAssetUrls(data.preview_html, browserPreviewUrl);
                setPreviewHtml(browserPreviewHtml);
                previewHtmlRef.current = browserPreviewHtml;
                inlineEditsActiveRef.current = false;
                setPreviewTheme(extractReactThemeFromHtml(browserPreviewHtml));
            }
            setHasInlinePreviewEdits(false);
            setShowInlineSnapshot(false);
            const compiledPreviewUrl = getPreviewUrlForSession(sid) || data.preview_url || '';
            setPreviewUrl(compiledPreviewUrl ? `${compiledPreviewUrl}${compiledPreviewUrl.includes('?') ? '&' : '?'}v=${Date.now()}` : '');
            setBuildLog('');
        } catch (e) {
            setBuildLog(e?.message || 'Vite sandbox compilation failed.');
            setHasInlinePreviewEdits(false);
            setPreviewUrl('');
            setActiveRightTab('preview');
        } finally {
            setIsCompilingSandbox(false);
        }
    }, [sessionId, createSession, serializeMessagesForSave, messages, previewHtml, projectFiles, reactCode, previewMode, previewTitle, pageTitle, pageSlug, pageStatus, model, getCompileUrlForSession, getPreviewUrlForSession, getPrimaryReactCodeFromFiles, getProjectFilesSignature]);
    compileSandboxRef.current = compileSandbox;

    const rebuildFromLatestTemplate = useCallback(async () => {
        if (!starterFilesUrlRef.current || !projectFiles.length) {
            compileSandboxRef.current?.(projectFiles);
            return;
        }

        setIsCompilingSandbox(true);
        setError('');

        try {
            const response = await fetch(starterFilesUrlRef.current, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await response.json().catch(() => null);
            const latestStarterFiles = data?.success && Array.isArray(data.project_files)
                ? data.project_files
                : [];

            if (!response.ok || latestStarterFiles.length === 0) {
                throw new Error('Unable to load the latest AI template files.');
            }

            const previousStarterMap = Object.fromEntries(
                (starterFilesRef.current || [])
                    .filter((file) => file?.path)
                    .map((file) => [file.path, file.content])
            );
            const latestStarterMap = Object.fromEntries(
                latestStarterFiles
                    .filter((file) => file?.path)
                    .map((file) => [file.path, file])
            );
            const currentMap = Object.fromEntries(
                projectFiles
                    .filter((file) => file?.path)
                    .map((file) => [file.path, file])
            );

            for (const [path, latestFile] of Object.entries(latestStarterMap)) {
                const currentFile = currentMap[path];
                const wasTemplateFile = Object.prototype.hasOwnProperty.call(previousStarterMap, path);
                const isUnchanged = currentFile && currentFile.content === previousStarterMap[path];
                const isBuiltInSectionTemplate = path === 'src/components/sections/Features.jsx';
                const isTestTemplateFile = /^src\/components\/sections\/\w+Test\d*\.jsx$/.test(path);

                if (isTestTemplateFile) {
                    if (currentFile) {
                        currentMap[path] = latestFile;
                    }
                } else if (!currentFile || isBuiltInSectionTemplate || wasTemplateFile && isUnchanged) {
                    currentMap[path] = latestFile;
                }
            }

            const updatedFiles = Object.values(currentMap);
            starterFilesRef.current = latestStarterFiles;
            setProjectFiles(updatedFiles);
            await compileSandbox(updatedFiles);
        } catch (error) {
            setError(error?.message || 'Unable to rebuild from the latest AI template.');
            setIsCompilingSandbox(false);
        }
    }, [projectFiles, compileSandbox]);

    useEffect(() => {
        if (previewMode !== 'react') return;
        if (!projectFiles.length) return;
        if (!hasCreatedSessionRef.current) return;
        if (activeRightTabRef.current === 'code') return;

        const nextSignature = getProjectFilesSignature(projectFiles);
        if (nextSignature === lastCompiledSignatureRef.current) {
            return;
        }

        if (compileTimeoutRef.current) {
            clearTimeout(compileTimeoutRef.current);
        }

        compileTimeoutRef.current = setTimeout(() => {
            compileSandbox(null, true);
        }, 900);

        return () => {
            if (compileTimeoutRef.current) {
                clearTimeout(compileTimeoutRef.current);
            }
        };
    }, [projectFiles, previewMode, compileSandbox, getProjectFilesSignature, activeRightTab]);

    useEffect(() => {
        if (!virtualFiles.length) {
            setSelectedFilePath('');
            return;
        }

        if (!virtualFiles.some((file) => file.path === selectedFilePath)) {
            const first = virtualFiles[0].path;
            setSelectedFilePath(first);
        }

        // Auto-open first editable file as a tab when project files load and no tabs are open
        if (openFileTabs.length === 0 && projectFiles.length > 0) {
            const firstCode = projectFiles.find((file) => file && typeof file.path === 'string' && /\.(jsx|tsx|js|ts|css|html)$/i.test(file.path));
            if (firstCode?.path) {
                setOpenFileTabs([firstCode.path]);
                setActiveFileTabPath(firstCode.path);
            }
        }
    }, [virtualFiles, selectedFilePath, openFileTabs, projectFiles]);

    const startResize = useCallback((event) => {
        event?.preventDefault();
        dragRef.current = true;
        setIsResizingPanels(true);
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    }, []);

    useEffect(() => {
        const onMove = (e) => {
            if (!dragRef.current) return;
            setLeftPanelWidth(Math.max(280, Math.min(800, e.clientX)));
        };
        const onUp = () => {
            if (dragRef.current) {
                dragRef.current = false;
                setIsResizingPanels(false);
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
            }
        };
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);
        return () => {
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onUp);
        };
    }, []);

    return (
        <div className="h-screen flex flex-col bg-white" style={{ fontFamily: "Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" }}>
            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
                @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap');

                @keyframes shimmer {
                    0% { background-position: 200% 0; }
                    100% { background-position: -200% 0; }
                }

                @keyframes mp-border-sweep {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                .mp-coder-composer {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    padding: 16px 18px 14px;
                    border-radius: 24px;
                    border: 1px solid rgba(255, 255, 255, 0.07);
                }

                .mp-coder-composer-loading {
                    border-color: rgba(255, 255, 255, 0.1);
                }

                .mp-coder-composer-loading::before {
                    content: "";
                    position: absolute;
                    inset: -1px;
                    border-radius: 25px;
                    background: conic-gradient(from 0deg, transparent 0%, rgba(255,255,255,0.15) 8%, transparent 16%);
                    animation: mp-border-sweep 2.5s linear infinite;
                    z-index: 0;
                    pointer-events: none;
                }

                .mp-coder-textarea {
                    position: relative;
                    z-index: 1;
                    min-height: 30px;
                    border: none !important;
                }
                .mp-coder-textarea:focus {
                    outline: none !important;
                    box-shadow: 0 0 #0000 !important;
                    --tw-ring-color: transparent !important;
                    --tw-border-opacity: 1 !important;
                    border-color: transparent !important;
                }

                .mp-coder-toolbar {
                    position: relative;
                    z-index: 1;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-top: 10px;
                    padding-top: 2px;
                }

                .mp-coder-icon-btn {
                    align-items: center;
                    justify-content: center;
                    width: 34px;
                    height: 34px;
                    border-radius: 9999px;
                    background: rgba(255, 255, 255, 0.06);
                    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
                    flex-shrink: 0;
                }

                .mp-coder-icon-btn:disabled {
                    opacity: 0.35;
                    cursor: not-allowed;
                }

                .mp-coder-send-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 34px;
                    height: 34px;
                    border-radius: 9999px;
                    background: rgb(176 26 26 / 8%);
                    border: none;
                    transition: background 0.15s ease, color 0.15s ease;
                    flex-shrink: 0;
                }

                .mp-coder-send-btn-active {
                    color: rgba(255, 255, 255, 0.95);
                    background: #525252;
                    cursor: pointer;
                }

                .mp-coder-send-btn-active:hover {
                    background: #616161;
                }

                .mp-coder-send-btn-stop {
                    color: rgba(255, 255, 255, 0.95);
                    background: #525252;
                    cursor: pointer;
                }

                .mp-coder-send-btn-stop:hover {
                    background: #616161;
                }

                .scrollbar-hide::-webkit-scrollbar { display: none; }
                .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
            `}</style>

            {/* Main workspace */}
            <div className="flex-1 min-h-0 flex">
                {/* Left: Chat */}
                {!isViewer && (
                <div
                    style={{ width: leftPanelCollapsed ? 0 : leftPanelWidth }}
                    className={`flex-shrink-0 h-full overflow-hidden flex flex-col bg-white ${leftPanelCollapsed ? 'hidden' : ''}`}
                >
                    <div className="h-14 px-3 bg-gray-50 flex items-center gap-3 shrink-0">
                        <div className="relative" ref={titleMenuRef}>
                            <button
                                type="button"
                                onClick={() => setTitleMenuOpen(o => !o)}
                                className="flex items-center gap-3 focus:outline-none"
                            >
                                <div className="h-8 w-8 shrink-0 rounded-lg bg-gray-900 text-white flex items-center justify-center text-xs font-semibold tracking-wide">
                                    {workspaceAppMonogram}
                                </div>
                                <div className="flex items-center gap-1 min-w-0">
                                    <div className="text-left min-w-0">
                                        <div className="text-sm font-semibold text-gray-900 text-left truncate max-w-[180px]">{workspaceAppTitle}</div>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400 shrink-0"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                            </button>
                            {titleMenuOpen && (
                                <div className="absolute left-0 top-full mt-2 w-52 rounded-lg border border-gray-200 bg-white shadow-lg py-1 z-50">
                                    <a
                                        href={effectiveDashboardUrl || '#'}
                                        onClick={() => setTitleMenuOpen(false)}
                                        className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                        Back to dashboard
                                    </a>
                                    <a
                                        href={effectiveNewProjectUrl || '#'}
                                        onClick={() => setTitleMenuOpen(false)}
                                        className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                        New project
                                    </a>
                                    <div className="px-3 py-3 border-t border-gray-100">
                                        {aiBuilderCreditStatus?.system ? (
                                            <div className="space-y-3">
                                                <div>
                                                    <div className="flex items-center justify-between mb-1.5">
                                                        <span className="text-sm font-semibold text-gray-900">Credits</span>
                                                        <span className="text-sm font-medium text-gray-600">
                                                            {aiBuilderCreditStatus.total_credits > 0 ? (
                                                                <>{Number(parseFloat(aiBuilderCreditStatus.remaining_credits || 0).toFixed(2))} left</>
                                                            ) : (
                                                                <>Unlimited</>
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div className="h-1 w-full rounded-full bg-gray-100 overflow-hidden">
                                                        <div
                                                            className="h-full rounded-full bg-blue-600 transition-all"
                                                            style={{
                                                                width: aiBuilderCreditStatus.total_credits > 0
                                                                    ? `${Math.min(100, ((aiBuilderCreditStatus.remaining_credits || 0) / aiBuilderCreditStatus.total_credits) * 100)}%`
                                                                    : '0%',
                                                            }}
                                                        />
                                                    </div>
                                                </div>

                                                <div className="pt-3 border-t border-gray-100">
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-sm font-semibold text-gray-900">Credit usage</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                                    </div>
                                                    {aiBuilderCreditStatus.monthly_renews_at && (
                                                        <div className="text-xs text-gray-500 mt-0.5">
                                                            Renews at {(() => {
                                                                const date = new Date(aiBuilderCreditStatus.monthly_renews_at);
                                                                const day = date.getUTCDate();
                                                                const suffix = ['th', 'st', 'nd', 'rd'][(day % 10 > 3 || Math.floor((day % 100) / 10) === 1) ? 0 : day % 10];
                                                                return `${day}${suffix} ${date.toLocaleDateString('en-GB', { month: 'short', year: 'numeric', timeZone: 'UTC' })}, ${date.toLocaleTimeString('en-GB', { hour: 'numeric', minute: '2-digit', timeZone: 'UTC' })} UTC`;
                                                            })()}
                                                        </div>
                                                    )}

                                                    <div className="mt-3 space-y-3">
                                                        <div>
                                                            <div className="flex items-center justify-between text-xs">
                                                                <span className="text-gray-600">{aiBuilderCreditStatus.system === 'token_based' ? 'Token credits' : 'Message credits'}</span>
                                                                <span className="font-medium text-gray-900">
                                                                    {aiBuilderCreditStatus.total_credits > 0
                                                                        ? <>{Number(parseFloat(aiBuilderCreditStatus.used_credits || 0).toFixed(2))} / {Number(parseFloat(aiBuilderCreditStatus.total_credits || 0).toFixed(2))}</>
                                                                        : <>Unlimited</>
                                                                    }
                                                                </span>
                                                            </div>
                                                            <div className="h-1 w-full rounded-full bg-gray-100 mt-1.5 overflow-hidden">
                                                                <div
                                                                    className="h-full rounded-full bg-orange-500"
                                                                    style={{
                                                                        width: aiBuilderCreditStatus.total_credits > 0
                                                                            ? `${Math.min(100, ((aiBuilderCreditStatus.used_credits || 0) / aiBuilderCreditStatus.total_credits) * 100)}%`
                                                                            : '0%',
                                                                    }}
                                                                />
                                                            </div>
                                                        </div>

                                                        {aiBuilderCreditStatus.daily_limit > 0 && (
                                                            <div>
                                                                <div className="flex items-center justify-between text-xs">
                                                                    <span className="text-gray-600">Daily limit</span>
                                                                    <span className="font-medium text-gray-900">{Number(parseFloat(aiBuilderCreditStatus.daily_used || 0).toFixed(2))} / {Number(parseFloat(aiBuilderCreditStatus.daily_limit || 0).toFixed(2))}</span>
                                                                </div>
                                                                <div className="h-1 w-full rounded-full bg-gray-100 mt-1.5 overflow-hidden">
                                                                    <div
                                                                        className="h-full rounded-full bg-orange-500"
                                                                        style={{
                                                                            width: `${Math.min(100, ((aiBuilderCreditStatus.daily_used || 0) / aiBuilderCreditStatus.daily_limit) * 100)}%`,
                                                                        }}
                                                                    />
                                                                </div>
                                                                {aiBuilderCreditStatus.daily_renews_at && (
                                                                    <div className="text-[10px] text-gray-400 mt-0.5">
                                                                        Resets {(() => {
                                                                            const date = new Date(aiBuilderCreditStatus.daily_renews_at);
                                                                            const day = date.getUTCDate();
                                                                            const suffix = ['th', 'st', 'nd', 'rd'][(day % 10 > 3 || Math.floor((day % 100) / 10) === 1) ? 0 : day % 10];
                                                                            return `${day}${suffix} ${date.toLocaleDateString('en-GB', { month: 'short', year: 'numeric', timeZone: 'UTC' })}, ${date.toLocaleTimeString('en-GB', { hour: 'numeric', minute: '2-digit', timeZone: 'UTC' })} UTC`;
                                                                        })()}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="text-sm text-gray-500">Credits: 12,500</div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                    <AiBuilderFoundryPanel
                        prompt={prompt}
                        setPrompt={setPrompt}
                        screenshot={screenshot}
                        setScreenshot={setScreenshot}
                        screenshotPreviewUrl={screenshotPreviewUrl}
                        model={model}
                        setModel={setModel}
                        error={error}
                        loading={loading}
                        messages={messages}
                        messagesLoading={false}
                        generate={generate}
                        discuss={discuss}
                        stopGeneration={stopGeneration}
                        providerModels={providerModels}
                        canGenerate={!loading && (String(prompt).trim() !== '' || selectedThemeId !== '')}
                        dynamicEvents={dynamicEvents}
                        generationPhase={generationPhase}
                        statusMessage={lastProgressMessageRef.current}
                        selectedElements={selectedElements}
                        onClearSelectedElements={handleClearSelectedElements}
                        suggestionsUrl={suggestionsUrlRef.current}
                        projectFiles={projectFiles}
                        previewHtml={previewHtml}
                        previewUrl={previewUrl}
                        previewTitle={previewTitle || pageTitle}
                        generatedReactCode={generatedReactCode}
                        enabledModels={enabledModels}
                        canDiscuss={!!aiBuilderPermissions.canDiscuss}
                        initialDiscussMode={initialDiscussMode}
                        selectedThemeId={selectedThemeId}
                        themePresets={THEME_PRESETS}
                        selectTheme={setSelectedThemeId}
                        onUpgradePrompt={(modelName) => {
                            if (modelName && typeof window.mailpurseToast === 'function') {
                                window.mailpurseToast({ type: 'info', message: `${modelName} is available on a higher plan. Upgrade to unlock it.` });
                            }
                            window.location.href = '/customer/billing';
                        }}
                    />
                </div>
                )}

                {/* Right: Preview workspace */}
                <div className="flex-1 min-w-0 h-full flex flex-col bg-gray-50">
                    <div className="flex-1 min-h-0 overflow-visible">
                        <div className="h-full flex flex-col shadow-sm overflow-visible">
                            {/* Right panel top bar */}
                            <div className="relative z-10 h-12 px-3 flex items-center gap-3 shrink-0 pt-0">
                                <div className="flex items-center gap-2">
                                    {!isViewer && (
                                    <button
                                        type="button"
                                        onClick={() => setLeftPanelCollapsed(c => !c)}
                                        className="h-7 w-7 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors"
                                        title={leftPanelCollapsed ? 'Expand left panel' : 'Collapse left panel'}
                                    >
                                        {leftPanelCollapsed ? (
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                <path d="M17 12H3" />
                                                <path d="m11 18 6-6-6-6" />
                                                <path d="M21 5v14" />
                                            </svg>
                                        ) : (
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                <path d="M3 19V5" />
                                                <path d="m13 6-6 6 6 6" />
                                                <path d="M7 12h14" />
                                            </svg>
                                        )}
                                    </button>
                                    )}
                                    <div className="flex items-center gap-1 bg-gray-200 rounded-lg p-[2px] border">
                                        {[
                                            { key: 'preview', label: 'Preview' },
                                            ...(!isViewer && (aiBuilderPermissions.canCodePreview || aiBuilderPermissions.canCodeEdit) ? [
                                                { key: 'code', label: 'Code' },
                                            ] : []),
                                            ...(!isViewer ? [
                                                { key: 'dashboard', label: 'Dashboard' },
                                            ] : []),
                                        ].map(({ key, label }) => (
                                            <button
                                                key={key}
                                                type="button"
                                                onClick={() => setActiveRightTab(key)}
                                                className={`h-7 px-3 rounded-md text-sm font-medium transition-colors ${
                                                    activeRightTab === key
                                                        ? 'bg-white text-gray-900'
                                                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                                                }`}
                                            >
                                                {label}
                                            </button>
                                        ))}
                                    </div>
                                    <div className="relative" ref={themeMenuRef}>
                                        <div
                                            className="inline-flex h-9 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-[0_1px_0_rgba(15,23,42,0.02)]"
                                            role="group"
                                            aria-label="Choose a prebuilt theme"
                                        >
                                            <button
                                                type="button"
                                                onClick={() => setThemeMenuOpen((open) => !open)}
                                                className="inline-flex h-9 items-center justify-center gap-2 px-3 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                                aria-haspopup="menu"
                                                aria-expanded={themeMenuOpen}
                                                title={`Choose a prebuilt theme${selectedTheme?.name ? `: ${selectedTheme.name}` : ''}`}
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="h-4.5 w-4.5 shrink-0 text-gray-700 lucide lucide-palette-icon lucide-palette" aria-hidden="true">
                                                    <path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z" />
                                                    <circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
                                                    <circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
                                                    <circle cx="6.5" cy="12.5" r=".5" fill="currentColor" />
                                                    <circle cx="8.5" cy="7.5" r=".5" fill="currentColor" />
                                                </svg>
                                                <span className="sr-only">{selectedTheme?.name || 'Themes'}</span>
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => setThemeMenuOpen((open) => !open)}
                                                className="flex h-9 w-9 items-center justify-center border-l border-gray-200 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700"
                                                aria-haspopup="menu"
                                                aria-expanded={themeMenuOpen}
                                                aria-label="Open theme dropdown"
                                                title="Open theme dropdown"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`h-3.5 w-3.5 shrink-0 transition-transform ${themeMenuOpen ? 'rotate-180' : ''}`}>
                                                    <path d="m6 9 6 6 6-6" />
                                                </svg>
                                            </button>
                                        </div>

                                        {themeMenuOpen ? (
                                            <div className="absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw-1rem)] rounded-2xl border border-gray-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.16)] z-50 overflow-hidden">
                                                <div className="px-4 pt-4 pb-3 border-b border-gray-100">
                                                    <h3 className="text-sm font-semibold tracking-tight text-gray-900">Pre Built Themes</h3>
                                                    <p className="mt-1 text-[11px] text-gray-500">Click a theme to paste its prompt into the input.</p>
                                                </div>

                                                <div className="max-h-72 overflow-auto p-2 space-y-1.5">
                                                    {THEME_PRESETS.map((theme) => {
                                                        const active = theme.id === selectedThemeId;
                                                        const typography = getThemeTypography(theme.id);
                                                        return (
                                                            <button
                                                                key={theme.id}
                                                                type="button"
                                                                onClick={() => handleThemeSelect(theme.id)}
                                                                className={`w-full rounded-xl border px-3 py-2.5 text-left transition-colors ${active ? 'border-gray-900 bg-gray-50' : 'border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300'}`}
                                                            >
                                                                <div className="flex items-center gap-3">
                                                                    <div className="grid grid-cols-2 gap-1 shrink-0 rounded-md border border-gray-200 bg-white p-1">
                                                                        {(Array.isArray(theme.swatches) ? theme.swatches : []).slice(0, 4).map((color, index) => (
                                                                            <span key={`${theme.id}-${index}`} className="h-2.5 w-2.5 rounded-[3px]" style={{ backgroundColor: color }} />
                                                                        ))}
                                                                    </div>
                                                                    <div className="min-w-0 flex-1">
                                                                        <div className="flex items-center justify-between gap-2">
                                                                            <div
                                                                                className="truncate text-sm font-medium text-gray-900"
                                                                                style={typography?.fontFamily ? { fontFamily: typography.fontFamily } : undefined}
                                                                            >
                                                                                {theme.name}
                                                                            </div>
                                                                            {active ? (
                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-900 shrink-0"><path d="M20 6 9 17l-5-5" /></svg>
                                                                            ) : null}
                                                                        </div>
                                                                        <div className="mt-0.5 text-[11px] leading-4 text-gray-500">{theme.description}</div>
                                                                        {typography?.label ? (
                                                                            <div className="mt-1 inline-flex rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-[10px] font-medium tracking-wide text-gray-600">
                                                                                {typography.label}
                                                                            </div>
                                                                        ) : null}
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        ) : null}
                                    </div>


                                    {saveStatus !== 'idle' && (
                                        <div className="flex items-center gap-1.5 pl-2 text-xs">
                                            {saveStatus === 'saving' && (
                                                <>
                                                    <span className="inline-block h-3 w-3 rounded-full border-2 border-gray-300 border-t-gray-600 animate-spin" />
                                                    <span className="text-gray-500">Saving...</span>
                                                </>
                                            )}
                                            {saveStatus === 'saved' && <span className="text-green-600 font-medium">Saved</span>}
                                            {saveStatus === 'error' && <span className="text-red-500">Save failed</span>}
                                        </div>
                                    )}
                                </div>
                                <div className="absolute left-1/2 z-0 flex w-full max-w-[620px] -translate-x-1/2 justify-center px-24 pointer-events-none">
                                    <div className="pointer-events-auto flex w-full items-center gap-1.5 shrink-0">
                                        <button
                                            type="button"
                                            onClick={() => setPreviewDevice((current) => (current === 'desktop' ? 'tablet' : current === 'tablet' ? 'mobile' : 'desktop'))}
                                            className="h-7 w-7 rounded-md hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors shrink-0"
                                            title={`Preview device: ${previewDevice}`}
                                        >
                                            {previewDevice === 'desktop' ? (
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="18" height="14" x="3" y="4" rx="2" ry="2"/><path d="M8 20h8"/><path d="M12 18v2"/></svg>
                                            ) : previewDevice === 'tablet' ? (
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><circle cx="12" cy="18" r="1"/></svg>
                                            ) : (
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="12" height="20" x="6" y="2" rx="2" ry="2"/><circle cx="12" cy="18" r="1"/></svg>
                                            )}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setPreviewRefreshKey((count) => count + 1);
                                                if (previewMode === 'react' && projectFiles.length > 0) {
                                                    rebuildFromLatestTemplate();
                                                }
                                            }}
                                            className="h-7 w-7 rounded-md hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors shrink-0"
                                            title="Rebuild with latest AI template"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 12a9 9 0 0 0-15.5-6.36L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 15.5 6.36L21 16"/><path d="M21 21v-5h-5"/></svg>
                                        </button>
                                        <div className="relative" ref={previewPageMenuRef}>
                                            <button
                                                type="button"
                                                onClick={() => setPreviewPageMenuOpen((open) => !open)}
                                                className="h-8 w-[180px] max-w-[180px] flex-none rounded-md border border-gray-200 bg-white px-3 flex items-center justify-between gap-2 shadow-[0_1px_0_rgba(15,23,42,0.02)] transition-colors hover:bg-gray-50"
                                                title="App pages"
                                            >
                                                <span className="min-w-0 text-left text-xs text-gray-900 font-semibold truncate">
                                                    <span className="truncate">{previewRouteLabel}</span>
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400 shrink-0"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                            {previewPageMenuOpen && appPreviewPages.length > 0 && (
                                                <div className="absolute left-0 top-full mt-2 w-[260px] rounded-xl border border-gray-200 bg-white shadow-lg py-1 z-50 overflow-hidden">
                                                    {appPreviewPages.map((page) => (
                                                        <button
                                                            key={page.path}
                                                            type="button"
                                                            onClick={() => {
                                                                setActivePreviewPage(page.slug);
                                                                setPreviewPageMenuOpen(false);
                                                            }}
                                                            className={`w-full px-3 py-2 text-left hover:bg-gray-50 transition-colors ${activeAppPreviewPage?.slug === page.slug ? 'bg-gray-50' : ''}`}
                                                        >
                                                            <div className="flex items-center justify-between gap-3">
                                                                <span className="text-sm font-medium text-gray-900 truncate">{page.name}</span>
                                                                <span className="text-[11px] font-mono text-gray-500 truncate">{page.slug}</span>
                                                            </div>
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (previewMode === 'react' && showInlineSnapshot && previewHtml) {
                                                    const previewWindow = window.open('', '_blank', 'noopener,noreferrer');
                                                    if (previewWindow) {
                                                        previewWindow.document.open();
                                                        previewWindow.document.write(previewHtml);
                                                        previewWindow.document.close();
                                                    }
                                                } else if (previewMode === 'react' && previewUrl) {
                                                    window.open(previewIframeUrl, '_blank', 'noopener,noreferrer');
                                                } else if (previewMode !== 'react' && previewHtml) {
                                                    const previewWindow = window.open('', '_blank', 'noopener,noreferrer');
                                                    if (previewWindow) {
                                                        previewWindow.document.open();
                                                        previewWindow.document.write(previewHtml);
                                                        previewWindow.document.close();
                                                    }
                                                }
                                            }}
                                            className="h-7 w-7 rounded-md hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors shrink-0"
                                            title="Preview"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-arrow-out-up-right-icon lucide-square-arrow-out-up-right"><path d="M21 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/><path d="m21 3-9 9"/><path d="M15 3h6v6"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 ml-auto">
                                    {aiBuilderPermissions.canShareProject && (
                                    <div className="relative" ref={shareMenuRef}>
                                        <button
                                            type="button"
                                            onClick={() => setShareMenuOpen((open) => !open)}
                                            className="relative h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-[11px] font-semibold text-white shadow-sm ring-2 ring-white"
                                            title="Share project"
                                        >
                                            <span className="absolute -bottom-0.5 -right-0.5 h-4 w-4 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 shadow-[0_1px_2px_rgba(15,23,42,0.12)]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                            </span>
                                            <span className="sr-only">Share project</span>
                                        </button>

                                        {shareMenuOpen && (
                                            <div className="absolute right-0 top-full mt-2 w-[420px] max-w-[calc(100vw-1rem)] rounded-md border border-gray-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.16)] z-50 overflow-hidden">
                                                <div className="px-4 pt-4 pb-3 border-b border-gray-100">
                                                    <h3 className="text-base font-semibold tracking-tight text-gray-900">Share project</h3>
                                                </div>

                                                <div className="px-4 py-4 space-y-4">
                                                    {shareModalIsOwner && (
                                                    <div className="flex items-center gap-2">
                                                        <input
                                                            type="email"
                                                            value={shareModalInviteEmail}
                                                            onChange={(e) => setShareModalInviteEmail(e.target.value)}
                                                            placeholder="Invite by email"
                                                            className="flex-1 h-9 rounded-lg border border-gray-200 bg-white px-3.5 text-[13px] text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-100 transition-all"
                                                            disabled={shareModalLoading}
                                                            onKeyDown={(e) => {
                                                                if (e.key === 'Enter' && shareModalInviteEmail) {
                                                                    inviteCollaborator();
                                                                }
                                                            }}
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={inviteCollaborator}
                                                            disabled={shareModalLoading || !shareModalInviteEmail}
                                                            className="h-9 px-4 rounded-lg bg-[#7c7cf1] text-white text-[13px] font-semibold hover:bg-[#6869ef] transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                                        >
                                                            {shareModalLoading ? 'Inviting…' : 'Invite'}
                                                        </button>
                                                    </div>
                                                    )}

                                                    <div>
                                                        <div className="flex items-center justify-between mb-2 text-[12px] font-medium text-gray-500">
                                                            <span>Who has project access</span>
                                                        </div>

                                                        <div className="space-y-2">
                                                            <div className="flex items-center justify-between gap-3 rounded-xl px-2 py-2 hover:bg-gray-50 transition-colors">
                                                                <div className="flex items-center gap-2 min-w-0">
                                                                    <div className="h-7 w-7 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 shrink-0">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                                                    </div>
                                                                    <div className="min-w-0">
                                                                        <div className="text-[13px] font-medium text-gray-900 truncate">Anyone with the invite link</div>
                                                                    </div>
                                                                </div>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setShareModalInviteRole((current) => (current === 'editor' ? 'viewer' : 'editor'))}
                                                                    className="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-[12px] font-medium text-gray-700 hover:bg-white border border-transparent hover:border-gray-200 transition-colors"
                                                                >
                                                                    {shareModalInviteRole === 'editor' ? 'Can edit' : 'Can view'}
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                                                </button>
                                                            </div>

                                                            {shareModalOwner && (
                                                                <div className="flex items-center justify-between gap-3 rounded-xl px-2 py-2 hover:bg-gray-50 transition-colors">
                                                                    <div className="flex items-center gap-2 min-w-0">
                                                                        <div className="h-7 w-7 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-[11px] font-semibold text-white shrink-0">
                                                                            {(shareModalOwner.name || shareModalOwner.email || 'O').charAt(0).toUpperCase()}
                                                                        </div>
                                                                        <div className="min-w-0">
                                                                            <div className="text-[13px] font-medium text-gray-900 truncate">
                                                                                {shareModalOwner.name || 'Project owner'}
                                                                                {shareModalIsOwner ? ' (you)' : ''}
                                                                            </div>
                                                                            <div className="text-[11px] text-gray-500 truncate">{shareModalOwner.email || ''}</div>
                                                                        </div>
                                                                    </div>
                                                                    <span className="text-[12px] font-medium text-gray-500">Owner</span>
                                                                </div>
                                                            )}

                                                            <button
                                                                type="button"
                                                                onClick={copyInviteLink}
                                                                disabled={!shareModalInviteLink}
                                                                className={`w-full h-9 rounded-lg border text-[13px] font-medium transition-colors inline-flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed ${
                                                                    shareLinkCopied
                                                                        ? 'border-green-200 bg-green-50 text-green-600'
                                                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                                                }`}
                                                            >
                                                                {shareLinkCopied ? (
                                                                    <>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                                        Copied!
                                                                    </>
                                                                ) : (
                                                                    <>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                                        Copy invite link
                                                                    </>
                                                                )}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                    </div>
                                    )}
                                    {!isViewer && (
                                    <button
                                        type="button"
                                        onClick={() => { window.location.href = '/customer/billing'; }}
                                        className="h-9 px-3 rounded-md border border-orange-200 bg-orange-50 text-orange-600 text-xs font-semibold hover:bg-orange-100 transition-colors"
                                    >
                                        Upgrade
                                    </button>
                                    )}
                                    {!isViewer && (
                                    <div className="relative" ref={publishMenuRef}>
                                        <button
                                            type="button"
                                            onClick={() => setPublishMenuOpen((open) => !open)}
                                            className="h-9 px-4 rounded-md bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800 transition-colors inline-flex items-center gap-1.5"
                                        >
                                            Publish
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                        {publishMenuOpen && (
                                            <div className="absolute right-0 mt-2 w-[320px] sm:w-[340px] max-w-[calc(100vw-1rem)] rounded-md border border-gray-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.16)] z-50 overflow-hidden">
                                                <div className="px-4 pt-4 pb-3">
                                                    <h3 className="text-base font-semibold tracking-tight text-gray-900">Publish</h3>
                                                </div>

                                                <div className="px-4 pb-4 space-y-4">
                                                    <div>
                                                        <label className="block text-[13px] font-semibold text-gray-900 mb-2.5">Your website URL</label>
                                                        <div className="rounded-md border border-gray-200 bg-white px-2 py-1 shadow-[0_1px_0_rgba(15,23,42,0.02)]">
                                                            <div className="flex items-center gap-2.5">
                                                                <div className="min-w-0 flex-1 flex items-center gap-0.5">
                                                                    {publishUrlEditing ? (
                                                                        <>
                                                                            {publishUrlIsPath ? (
                                                                                <span className="shrink-0 truncate text-[16px] font-medium tracking-tight text-gray-500">
                                                                                    {publishPathPrefix}
                                                                                </span>
                                                                            ) : null}
                                                                            <input
                                                                                type="text"
                                                                                value={publishSlugValue}
                                                                                onChange={(e) => {
                                                                                    const nextSlug = e.target.value
                                                                                        .trim()
                                                                                        .toLowerCase()
                                                                                        .replace(/[^a-z0-9-]/g, '-')
                                                                                        .replace(/-+/g, '-')
                                                                                        .replace(/^-|-$/g, '');
                                                                                    setPublishSlugDraft(nextSlug);
                                                                                }}
                                                                                className="min-w-0 w-full border-0 bg-transparent p-0 text-[16px] font-semibold tracking-tight text-gray-900 outline-none ring-0 placeholder:text-gray-300"
                                                                                spellCheck="false"
                                                                                autoComplete="off"
                                                                                autoCorrect="off"
                                                                            />
                                                                            {publishUrlSuffix ? (
                                                                                <span className="shrink-0 truncate text-[16px] font-medium tracking-tight text-gray-500">
                                                                                    .{publishUrlSuffix}
                                                                                </span>
                                                                            ) : null}
                                                                        </>
                                                                    ) : (
                                                                        <span className="min-w-0 truncate text-sm font-semibold tracking-tight text-gray-900">
                                                                            {publishUrlIsPath ? publishWebsiteUrl : publishUrlSlug}
                                                                            {!publishUrlIsPath && publishUrlSuffix ? (
                                                                                <span className="text-gray-500">.{publishUrlSuffix}</span>
                                                                            ) : null}
                                                                        </span>
                                                                    )}
                                                                </div>

                                                                {publishUrlEditing ? (
                                                                    <>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                setPublishUrlEditing(false);
                                                                                setPublishSlugDraft(pageSlug || previewSlugLabel);
                                                                            }}
                                                                            className="h-8 w-8 shrink-0 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-900 transition-colors"
                                                                            title="Cancel edit"
                                                                        >
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                                        </button>

                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                const cleanSlug = publishSlugValue
                                                                                    .trim()
                                                                                    .toLowerCase()
                                                                                    .replace(/[^a-z0-9-]/g, '-')
                                                                                    .replace(/-+/g, '-')
                                                                                    .replace(/^-|-$/g, '');
                                                                                if (!cleanSlug) return;
                                                                                setPublishSlugDraft(cleanSlug);
                                                                                setPageSlug(cleanSlug);
                                                                                setPublishUrlEditing(false);
                                                                            }}
                                                                            className="h-8 w-8 shrink-0 rounded-2xl bg-gray-900 text-white shadow-[0_6px_16px_rgba(0,0,0,0.18)] flex items-center justify-center hover:bg-gray-800 transition-colors"
                                                                            title="Confirm edit"
                                                                        >
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                                        </button>
                                                                    </>
                                                                ) : (
                                                                    <>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                navigator.clipboard?.writeText(publishWebsiteUrl);
                                                                                if (typeof window.mailpurseToast === 'function') {
                                                                                    window.mailpurseToast({ type: 'success', message: 'URL copied to clipboard' });
                                                                                }
                                                                            }}
                                                                            className="h-8 w-8 shrink-0 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors"
                                                                            title="Copy URL"
                                                                        >
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                                        </button>

                                                                        <button
                                                                            type="button"
                                                                            onClick={() => setPublishUrlEditing(true)}
                                                                            className="h-8 w-8 shrink-0 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors"
                                                                            title="Edit URL"
                                                                        >
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                                        </button>
                                                                    </>
                                                                )}
                                                            </div>
                                                        </div>

                                                        <div className="mt-3">
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    if (!aiBuilderPermissions.canCustomDomain) {
                                                                        window.location.href = '/customer/billing';
                                                                        return;
                                                                    }
                                                                    setPublishMenuOpen(false);
                                                                    setDashboardSection('domain');
                                                                    setActiveRightTab('dashboard');
                                                                }}
                                                                className="flex items-center gap-2.5 text-sm text-gray-500 hover:text-gray-700 transition-colors w-full"
                                                            >
                                                                <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gray-50 text-gray-400 hover:bg-gray-100 transition-colors">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                                                </span>
                                                                <span className="text-[13px] font-medium">{customDomain ? customDomain : 'Add custom domain'}</span>
                                                                {!aiBuilderPermissions.canCustomDomain && (
                                                                    <span className="inline-flex h-5 items-center rounded-md bg-violet-100 px-1.5 text-[10px] font-semibold text-violet-500">Pro</span>
                                                                )}
                                                                {customDomain && (
                                                                    <span className="ml-auto text-[11px] text-gray-400">{'Manage \u2192'}</span>
                                                                )}
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div className="grid gap-2 md:grid-cols-3">
                                                        <div className="md:col-span-2">
                                                            <label className="block text-[11px] font-medium text-gray-500 mb-1">Privacy</label>
                                                            <div className="relative">
                                                                <select
                                                                    value={appVisibility}
                                                                    onChange={(e) => setAppVisibility(e.target.value)}
                                                                    className="w-full h-10 rounded-lg border border-gray-200 bg-white px-3 text-[12px] text-gray-900 outline-none appearance-none"
                                                                >
                                                                    <option value="public">Public — anyone with the link</option>
                                                                    <option value="private">Private — admin only</option>
                                                                    <option value="password">Password protected</option>
                                                                </select>
                                                                <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div className="md:col-span-1 flex items-end">
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    setPublishMenuOpen(false);
                                                                    handleSave({ status: 'published', visibility: appVisibility, password: publishPassword });
                                                                }}
                                                                disabled={saving}
                                                                className="h-10 w-full rounded-lg bg-gray-900 text-white text-[12px] font-semibold hover:bg-gray-800 transition-colors disabled:opacity-50"
                                                            >
                                                                {saving ? 'Publishing…' : 'Publish'}
                                                            </button>
                                                        </div>
                                                    </div>

                                                    {appVisibility === 'password' && (
                                                        <div>
                                                            <label className="block text-[11px] font-medium text-gray-500 mb-1">Password</label>
                                                            <input
                                                                type="password"
                                                                value={publishPassword}
                                                                onChange={(e) => setPublishPassword(e.target.value)}
                                                                placeholder="Set access password"
                                                                className="w-full h-8 rounded-lg border border-gray-200 bg-white px-2.5 text-[12px] text-gray-900 outline-none placeholder:text-gray-400"
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    )}

                                </div>
                            </div>

                            {/* Content */}
                            <div className="flex-1 min-h-0 relative bg-gray-50 px-3.5 pb-3.5">
                                <div className="h-full rounded-md border border-gray-200 bg-white shadow-sm overflow-hidden relative">
                                    {!leftPanelCollapsed && (
                                        <div
                                            onMouseDown={startResize}
                                            className="group absolute inset-y-0 left-0 z-30 w-3 -translate-x-1/2 cursor-col-resize"
                                            title="Drag to resize"
                                        >
                                            <span
                                                className={`pointer-events-none absolute inset-y-0 left-1/2 w-px -translate-x-1/2 transition-colors ${
                                                    isResizingPanels ? 'bg-[#2563eb]' : 'bg-transparent group-hover:bg-[#2563eb]'
                                                }`}
                                            />
                                        </div>
                                    )}
                                    {activeRightTab === 'preview' ? (
                                    <div className="h-full relative bg-gray-50">
                                        {!hasPreviewContent && (loading || isCompilingSandbox) ? (
                                            isCompilingSandbox ? (
                                                <div className="h-full flex flex-col items-center justify-center bg-white px-6">
                                                    <div className="w-56 h-56">
                                                        <Lottie animationData={buildingAnimation} loop={true} autoplay={true} />
                                                    </div>
                                                    <p className="text-sm text-gray-500 mt-2">Building your page…</p>
                                                </div>
                                            ) : (
                                                <AiBuilderPreloader />
                                            )
                                        ) : (
                                            <>
                                                {previewMode === 'react' ? (
                                                    (showInlineSnapshot && previewHtml) ? (
                                                        <div className={`h-full overflow-auto bg-gray-50 relative ${previewDevice === 'desktop' ? '' : 'flex justify-center'}`}>
                                                            <div className={previewViewportClass}>
                                                                <iframe
                                                                    ref={previewIframeRef}
                                                                    key={`${previewRefreshKey}-${previewDevice}`}
                                                                    srcDoc={previewHtml}
                                                                    onLoad={() => { setPreviewSelectionMode(selectionModeActive); setPreviewInlineEditMode(inlineEditMode); applyPreviewStyleOverrides(styleOverrides); }}
                                                                    className="w-full h-full border-0 rounded-md bg-white shadow-sm"
                                                                    sandbox="allow-scripts allow-forms allow-popups"
                                                                    title="Edited Page Preview"
                                                                />
                                                            </div>
                                                            {isCompilingSandbox ? (
                                                                <div className="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/80 backdrop-blur-md">
                                                                    <div className="w-56 h-56">
                                                                        <Lottie animationData={buildingAnimation} loop={true} autoplay={true} />
                                                                    </div>
                                                                    <p className="text-sm text-gray-500 mt-2">Building your page…</p>
                                                                </div>
                                                            ) : loading ? (
                                                                <AiBuilderPreloader overlay />
                                                            ) : null}
                                                        </div>
                                                    ) : previewIframeUrl ? (
                                                        <div className={`h-full overflow-auto bg-gray-50 relative ${previewDevice === 'desktop' ? '' : 'flex justify-center'}`}>
                                                            <div className={previewViewportClass}>
                                                                <iframe
                                                                    ref={previewIframeRef}
                                                                    key={`${previewRefreshKey}-${previewDevice}`}
                                                                    src={previewIframeUrl}
                                                                    onLoad={() => { setPreviewSelectionMode(selectionModeActive); setPreviewInlineEditMode(inlineEditMode); applyPreviewStyleOverrides(styleOverrides); }}
                                                                    className="w-full h-full border-0 rounded-md bg-white shadow-sm"
                                                                    title="Page Preview"
                                                                />
                                                            </div>
                                                            {isCompilingSandbox ? (
                                                                <div className="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/80 backdrop-blur-md">
                                                                    <div className="w-56 h-56">
                                                                        <Lottie animationData={buildingAnimation} loop={true} autoplay={true} />
                                                                    </div>
                                                                    <p className="text-sm text-gray-500 mt-2">Building your page…</p>
                                                                </div>
                                                            ) : loading ? (
                                                                <AiBuilderPreloader overlay />
                                                            ) : null}
                                                        </div>
                                                    ) : buildLog ? (
                                                        <div className="h-full flex flex-col items-center justify-center p-8 text-center">
                                                            <div className="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mb-4">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-red-500"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                                                            </div>
                                                            <h3 className="text-sm font-semibold text-slate-800 mb-2">Build failed</h3>
                                                            <div className="max-w-lg w-full bg-[#1e1e1e] rounded-lg p-4 text-left overflow-auto max-h-[60vh]">
                                                                <pre className="text-xs text-red-400 whitespace-pre-wrap break-words" style={{ fontFamily: "'JetBrains Mono', monospace" }}>{buildLog}</pre>
                                                            </div>
                                                            <p className="text-xs text-slate-500 mt-4">Edit the code in the Code tab and click Compile to retry.</p>
                                                        </div>
                                                    ) : previewHtml ? (
                                                        <div className="h-full flex flex-col items-center justify-center p-8 text-center bg-white">
                                                            <div className="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center mb-3 text-gray-400">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                                            </div>
                                                            <h3 className="text-sm font-semibold text-slate-800 mb-1">Preview needs compilation</h3>
                                                            <p className="text-xs text-slate-500 max-w-xs">The compiled React preview requires the sandbox build. Run Compile from the Code tab to generate it.</p>
                                                        </div>
                                                    ) : (
                                                        <div className="h-full flex items-center justify-center text-slate-400 text-sm">
                                                            No preview available
                                                        </div>
                                                    )
                                                ) : (
                                                    <div className={`h-full overflow-auto bg-gray-50 relative ${previewDevice === 'desktop' ? '' : 'flex justify-center'}`}>
                                                        <div className={previewViewportClass}>
                                                            <iframe
                                                                ref={previewIframeRef}
                                                                key={`${previewRefreshKey}-${previewDevice}`}
                                                                {...(previewIframeUrl ? { src: previewIframeUrl } : { srcDoc: previewHtml, sandbox: 'allow-scripts allow-same-origin' })}
                                                                onLoad={() => { setPreviewSelectionMode(selectionModeActive); setPreviewInlineEditMode(inlineEditMode); applyPreviewStyleOverrides(styleOverrides); }}
                                                                className="w-full h-full border-0 rounded-md bg-white shadow-sm"
                                                                title="Page Preview"
                                                            />
                                                        </div>
                                                        {isCompilingSandbox ? (
                                                            <div className="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/80 backdrop-blur-md">
                                                                <div className="w-56 h-56">
                                                                    <Lottie animationData={buildingAnimation} loop={true} autoplay={true} />
                                                                </div>
                                                                <p className="text-sm text-gray-500 mt-2">Building your page…</p>
                                                            </div>
                                                        ) : loading ? (
                                                            <AiBuilderPreloader overlay />
                                                        ) : null}
                                                    </div>
                                                )}
                                        </>
                                    )}

                                        {/* Inline edit toolbar */}
                                        {inlineEditTarget && inlineEditMode ? (
                                            <InlineEditToolbar
                                                target={inlineEditTarget}
                                                iframeRef={previewIframeRef}
                                                onClose={() => setInlineEditTarget(null)}
                                                onCode={handlePreviewEditCode}
                                                onDelete={handlePreviewEditDelete}
                                                onStyleChange={handleInlineEditStyleChange}
                                                onLinkChange={(path, url, target) => previewIframeRef.current?.contentWindow?.postMessage({ type: 'MAILPURSE_SET_LINK', path, url, target }, '*')}
                                                onImageChange={(path, src) => previewIframeRef.current?.contentWindow?.postMessage({ type: 'MAILPURSE_SET_IMAGE_SOURCE', path, src }, '*')}
                                                onReplaceWithImage={(path, src) => previewIframeRef.current?.contentWindow?.postMessage({ type: 'MAILPURSE_REPLACE_WITH_IMAGE', path, src }, '*')}
                                                onReplaceWithSvg={(path, svg) => previewIframeRef.current?.contentWindow?.postMessage({ type: 'MAILPURSE_REPLACE_WITH_SVG', path, svg }, '*')}
                                                imageUploadUrl={mountEl.current?.dataset?.imageUploadUrl || ''}
                                            />
                                        ) : null}

                                        {/* Floating glassmorphism selection/edit panel */}
                                        {hasPreviewContent && aiBuilderPermissions.canLivePreviewEdit && (
                                        <div className="absolute bottom-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 pointer-events-auto">
                                            {inlineEditMode ? (
                                                <div className="flex items-center gap-2 px-1 py-1 rounded-3xl bg-white/80 backdrop-blur-xl border border-white/40 shadow-xl">
                                                    <div className="inline-flex items-center gap-1.5 px-3 py-2 rounded-3xl bg-purple-100 text-purple-700 text-xs font-semibold">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                        Edit mode
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={handlePreviewEditCancel}
                                                        className="h-8 px-3 rounded-full bg-white/80 text-gray-800 text-xs font-semibold hover:bg-white transition-colors"
                                                    >
                                                        Done
                                                    </button>
                                                </div>
                                            ) : selectionModeActive ? (
                                                <div className="flex items-center gap-2 p-1 rounded-3xl bg-white/80 backdrop-blur-xl border border-white/40 shadow-xl">
                                                    <button
                                                        type="button"
                                                        onClick={handleClearSelectedElements}
                                                        className="inline-flex items-center gap-1.5 pl-1 pr-3 py-1.5 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold hover:bg-blue-200 transition-colors"
                                                    >
                                                        <span className="inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-200 text-blue-700">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                                        </span>
                                                        {selectedElements.length} selection{selectedElements.length === 1 ? '' : 's'}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={handlePreviewSelection}
                                                        className="h-8 px-3 rounded-lg bg-white/80 text-gray-800 text-xs font-semibold hover:bg-white transition-colors"
                                                    >
                                                        Done
                                                    </button>
                                                </div>
                                            ) : (
                                                <div className="flex items-center gap-2 p-1 rounded-3xl bg-white/80 backdrop-blur-xl border border-white/40 shadow-xl">
                                                    <button
                                                        type="button"
                                                        onClick={handlePreviewEditStart}
                                                        className="h-8 px-3 rounded-3xl text-gray-800 text-xs font-semibold hover:bg-gray-100 transition-colors inline-flex items-center gap-1"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={handlePreviewSelection}
                                                        className="h-8 px-3 rounded-3xl text-xs font-semibold hover:bg-gray-100 transition-colors inline-flex items-center gap-1"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-dashed-mouse-pointer-icon lucide-square-dashed-mouse-pointer"><path d="M12.034 12.681a.498.498 0 0 1 .647-.647l9 3.5a.5.5 0 0 1-.033.943l-3.444 1.068a1 1 0 0 0-.66.66l-1.067 3.443a.5.5 0 0 1-.943.033z"/><path d="M5 3a2 2 0 0 0-2 2"/><path d="M19 3a2 2 0 0 1 2 2"/><path d="M5 21a2 2 0 0 1-2-2"/><path d="M9 3h1"/><path d="M9 21h2"/><path d="M14 3h1"/><path d="M3 9v1"/><path d="M21 9v2"/><path d="M3 14v1"/></svg>
                                                        Selection
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                        )}
                                    </div>
                                ) : activeRightTab === 'code' ? (
                                    hasReactWorkspace ? (
                                        <div className="overflow-auto h-full grid grid-cols-[240px_minmax(0,1fr)]">
                                            <div className="border-r border-gray-200 bg-gray-50 overflow-auto">
                                                <FileTree
                                                    files={virtualFiles}
                                                    selectedPath={selectedFilePath}
                                                    onSelect={openFileTab}
                                                />
                                            </div>
                                            <div className="h-full flex flex-col bg-white text-gray-800">
                                                {/* Tab bar */}
                                                <div className="flex items-center border-b border-gray-200 bg-gray-50 overflow-x-auto">
                                                    {openFileTabs.filter(Boolean).map((tabPath) => {
                                                        const isActive = tabPath === activeFileTabPath;
                                                        const fileName = tabPath.split('/').pop() || tabPath;
                                                        return (
                                                            <button
                                                                key={tabPath}
                                                                type="button"
                                                                onClick={() => {
                                                                    setActiveFileTabPath(tabPath);
                                                                    setSelectedFilePath(tabPath);
                                                                }}
                                                                className={`group relative flex items-center gap-2 px-3 py-2 text-[13px] font-medium whitespace-nowrap transition-colors ${
                                                                    isActive
                                                                        ? 'bg-white text-gray-900 border border-t-0 border-b-0 border-blue-500'
                                                                        : 'text-gray-500 hover:text-gray-800 hover:bg-gray-100'
                                                                }`}
                                                            >
                                                                <span className="truncate max-w-[140px]">{fileName}</span>
                                                                <span
                                                                    role="button"
                                                                    tabIndex={0}
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        closeFileTab(tabPath);
                                                                    }}
                                                                    onKeyDown={(e) => {
                                                                        if (e.key === 'Enter' || e.key === ' ') {
                                                                            e.stopPropagation();
                                                                            closeFileTab(tabPath);
                                                                        }
                                                                    }}
                                                                    className="inline-flex items-center justify-center rounded-sm hover:bg-gray-200 text-gray-400 hover:text-gray-700 transition-colors"
                                                                    style={{ width: 14, height: 14 }}
                                                                >
                                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                                </span>
                                                            </button>
                                                        );
                                                    })}
                                                    <div className="flex-1" />
                                                    {hasLiveCodeChanges ? (
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                if (primaryPageFile?.path) {
                                                                    updateProjectFile(primaryPageFile.path, generatedReactCode);
                                                                    openFileTab(primaryPageFile.path);
                                                                }
                                                            }}
                                                            disabled={!primaryPageFile?.path}
                                                            className="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 mr-2 text-[11px] font-medium text-gray-700 hover:bg-gray-200 transition-colors flex-shrink-0"
                                                        >
                                                            Reset to generated
                                                        </button>
                                                    ) : null}
                                                </div>
                                                {aiBuilderPermissions.canCodePreview && !aiBuilderPermissions.canCodeEdit && (
                                                    <div className="flex items-center justify-center gap-3 px-4 py-2.5 bg-gray-50 border-b border-gray-200 text-xs text-gray-600">
                                                        <span className="flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-500"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                            Code editing is only available on paid plans.
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => { window.location.href = '/customer/billing'; }}
                                                            className="inline-flex items-center gap-1.5 rounded-lg bg-orange-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-600 transition-colors"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                                                            View plans
                                                        </button>
                                                    </div>
                                                )}
                                                {/* File path breadcrumb */}
                                                <div className="px-3 py-1.5 border-b-0 border-gray-100 text-[11px] text-gray-400 truncate">
                                                    {editableFile?.path || ''}
                                                </div>
                                                {!detectedComponentName ? (
                                                    <div className="mx-4 mt-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-[12px] text-amber-800">
                                                        The editor could not detect a top-level React component. Define something like <code style={{ fontFamily: "'JetBrains Mono', monospace" }}>const LandingPage = () =&gt; (...)</code> to restore live preview.
                                                    </div>
                                                ) : null}
                                                <div className="flex-1 min-h-0 relative">
                                                    <Editor
                                                        height="100%"
                                                        path={editableFile?.path || 'untitled.js'}
                                                        language={(() => {
                                                            const ext = (editableFile?.path || '').split('.').pop()?.toLowerCase();
                                                            if (ext === 'css') return 'css';
                                                            if (ext === 'html') return 'html';
                                                            if (ext === 'json') return 'json';
                                                            if (ext === 'ts' || ext === 'tsx') return 'typescript';
                                                            return 'javascript';
                                                        })()}
                                                        value={editableFile?.content || ''}
                                                        onChange={(value) => {
                                                            const current = editableFileRef.current;
                                                            if (current?.path && value !== current.content) {
                                                                updateProjectFile(current.path, value || '');
                                                            }
                                                        }}
                                                        onMount={(editor, monaco) => {
                                                            monaco.languages.typescript.typescriptDefaults.setDiagnosticsOptions({
                                                                noSemanticValidation: true,
                                                                noSyntaxValidation: false,
                                                            });
                                                            monaco.languages.typescript.javascriptDefaults.setDiagnosticsOptions({
                                                                noSemanticValidation: true,
                                                                noSyntaxValidation: false,
                                                            });
                                                        }}
                                                        options={{
                                                            fontSize: 14,
                                                            fontWeight: '500',
                                                            fontFamily: "'JetBrains Mono', monospace",
                                                            fontLigatures: true,
                                                            lineNumbers: 'on',
                                                            minimap: { enabled: false },
                                                            scrollBeyondLastLine: false,
                                                            automaticLayout: true,
                                                            wordWrap: 'on',
                                                            tabSize: 2,
                                                            readOnly: !editableFile?.path || !aiBuilderPermissions.canCodeEdit,
                                                            padding: { top: 16 },
                                                            renderLineHighlight: 'line',
                                                            matchBrackets: 'always',
                                                            folding: true,
                                                        }}
                                                        theme="light"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="h-full flex flex-col items-center justify-center px-6 text-center text-gray-500">
                                            <div className="h-14 w-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-4 text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                            </div>
                                            <p className="text-sm font-semibold text-gray-900">No editable React code yet</p>
                                            <p className="text-xs mt-1 max-w-md">Generate a page to inspect its React source here.</p>
                                        </div>
                                    )
                                ) : activeRightTab === 'dashboard' ? (
                                    <AppDashboardPanel
                                        pageTitle={pageTitle}
                                        previewTitle={previewTitle}
                                        pageSlug={pageSlug}
                                        pageStatus={pageStatus}
                                        previewMode={previewMode}
                                        sessionId={sessionId}
                                        model={model}
                                        hasLiveCodeChanges={hasLiveCodeChanges}
                                        virtualFiles={virtualFiles}
                                        previewTheme={previewTheme}
                                        savedPageId={savedPageId}
                                        appMetaTitle={appMetaTitle}
                                        setAppMetaTitle={setAppMetaTitle}
                                        appMetaLogo={appMetaLogo}
                                        setAppMetaLogo={setAppMetaLogo}
                                        appMetaIcon={appMetaIcon}
                                        setAppMetaIcon={setAppMetaIcon}
                                        appMetaDescription={appMetaDescription}
                                        setAppMetaDescription={setAppMetaDescription}
                                        appMetaSocialImage={appMetaSocialImage}
                                        setAppMetaSocialImage={setAppMetaSocialImage}
                                        appMetaSaveStatus={appMetaSaveStatus}
                                        saveAppMeta={saveAppMeta}
                                        customDomain={customDomain}
                                        customDomainLoading={customDomainLoading}
                                        customDomainError={customDomainError}
                                        customDomainSuccess={customDomainSuccess}
                                        setCustomDomain={setCustomDomain}
                                        updateCustomDomain={updateCustomDomain}
                                        serverIp={effectiveServerIp}
                                        serverHost={effectiveServerHost}
                                        supabaseIntegration={supabaseIntegration}
                                        setSupabaseIntegration={setSupabaseIntegration}
                                        supabaseForm={supabaseForm}
                                        setSupabaseForm={setSupabaseForm}
                                        supabaseConfigs={supabaseConfigs}
                                        supabaseCapabilities={effectiveSupabaseCapabilities}
                                        supabaseLoading={supabaseLoading}
                                        supabaseError={supabaseError}
                                        supabaseUsers={supabaseUsers}
                                        supabaseBuckets={supabaseBuckets}
                                        supabaseTables={supabaseTables}
                                        supabaseTableData={supabaseTableData}
                                        supabaseTableDataLoading={supabaseTableDataLoading}
                                        supabaseTableDataError={supabaseTableDataError}
                                        connectSupabase={connectSupabase}
                                        connectSupabaseOAuth={connectSupabaseOAuth}
                                        disconnectSupabase={disconnectSupabase}
                                        loadSupabaseUsers={loadSupabaseUsers}
                                        loadSupabaseBuckets={loadSupabaseBuckets}
                                        loadSupabaseTables={loadSupabaseTables}
                                        loadSupabaseTableData={loadSupabaseTableData}
                                        exportUrlTemplate={effectiveExportUrlTemplate}
                                        initialSection={dashboardSection}
                                        onOpenPreview={() => setActiveRightTab('preview')}
                                        onOpenCode={() => setActiveRightTab('code')}
                                        platformBadge={aiBuilderCapabilities?.platformBadge}
                                    />
                                ) : showPreview && (previewIframeUrl || previewHtml) ? (
                                    <div className="h-full">
                                        {previewIframeUrl ? (
                                            <iframe
                                                src={previewIframeUrl}
                                                className="w-full h-full border-0"
                                                title="Page Preview"
                                            />
                                        ) : (
                                            <iframe
                                                srcDoc={previewHtml}
                                                className="w-full h-full border-0"
                                                sandbox="allow-scripts"
                                                title="Page Preview"
                                            />
                                        )}
                                    </div>
                                ) : loading || isCompilingSandbox ? (
                                    isCompilingSandbox ? (
                                        <div className="h-full flex flex-col items-center justify-center bg-white px-6">
                                            <div className="w-56 h-56">
                                                <Lottie animationData={buildingAnimation} loop={true} autoplay={true} />
                                            </div>
                                            <p className="text-sm text-gray-500 mt-2">Building your page…</p>
                                        </div>
                                    ) : (
                                        <AiBuilderPreloader />
                                    )
                                ) : (
                                    <div className="h-full flex flex-col items-center justify-center px-6 text-center text-gray-500 bg-white">
                                        <div className="h-16 w-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4 text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="3" x2="21" y1="9" y2="9"/><line x1="9" x2="9" y1="21" y2="9"/></svg>
                                        </div>
                                        <p className="text-sm font-semibold text-gray-900">Your generated workspace will appear here</p>
                                        <p className="text-xs mt-1 max-w-md">Generate a page to preview it, inspect the React code, and manage project metadata from one workspace.</p>
                                    </div>
                                )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    );
}

function initPageAiBuilder() {
    const el = document.getElementById('page-ai-builder-react');
    if (!el) return;

    try {
        const root = el.__mailpurseReactRoot || createRoot(el);
        el.__mailpurseReactRoot = root;
        root.render(<PageAiBuilder />);
    } catch (e) {
        // ignore
    }
}

function unmountPageAiBuilder() {
    const el = document.getElementById('page-ai-builder-react');
    if (!el) return;

    try {
        if (el.__mailpurseReactRoot) {
            el.__mailpurseReactRoot.unmount();
            delete el.__mailpurseReactRoot;
        }
    } catch (e) {
        // ignore
    }
}

if (window.Turbo) {
    document.addEventListener('turbo:load', initPageAiBuilder);
    document.addEventListener('turbo:before-cache', unmountPageAiBuilder);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageAiBuilder);
} else {
    initPageAiBuilder();
}

export { initPageAiBuilder, unmountPageAiBuilder };
