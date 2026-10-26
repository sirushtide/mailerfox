import { useEffect, useMemo, useRef, useState } from 'react';

const KNOWN_TYPES = new Set(['thought', 'narration', 'tool', 'summary', 'plan']);

function parseThoughtSeconds(content) {
    const match = String(content || '').match(/Thought for\s+(\d+)s/i);
    return match ? parseInt(match[1], 10) : null;
}

function messageToBlock(msg, index, { loading, isLast }) {
    const status = String(msg?.status || '');
    const content = typeof msg?.content === 'string' ? msg.content : '';
    const thought = typeof msg?.thought === 'string' && msg.thought.trim() !== '' ? msg.thought.trim() : '';

    let type = KNOWN_TYPES.has(msg?.type) ? msg.type : null;
    if (!type) {
        if (status === 'thought') type = 'thought';
        else if (status === 'progress') type = msg?.file ? 'tool' : 'narration';
        else if (status === 'error') type = 'error';
        else if (status === 'cancelled') type = 'cancelled';
        else if (status === 'ok' || status === 'selection') type = 'result';
        else type = 'narration';
    }

    let duration = null;
    if (Number.isFinite(Number(msg?.duration)) && Number(msg.duration) > 0) {
        duration = Math.max(1, Math.round(Number(msg.duration)));
    }
    if (type === 'thought' && duration === null) {
        duration = parseThoughtSeconds(content);
    }

    const parsed = Number.isFinite(Date.parse(msg?.created_at)) ? Date.parse(msg.created_at) : null;

    return {
        id: msg?.id || `block-${index}`,
        type,
        content,
        thought,
        file: typeof msg?.file === 'string' ? msg.file : '',
        detail: typeof msg?.detail === 'string' && msg.detail.trim() !== '' ? msg.detail : null,
        action: typeof msg?.action === 'string' ? msg.action : null,
        plan: Array.isArray(msg?.plan) ? msg.plan : [],
        duration,
        status,
        startedAt: parsed || Date.now() + index,
    };
}

function computeDurations(blocks, { loading, clientStart }) {
    const list = blocks.map((block) => ({ ...block }));
    const now = Date.now();
    for (let i = 0; i < list.length; i++) {
        const next = list[i + 1];
        if (list[i].type !== 'thought' || list[i].duration) continue;
        const startAt = clientStart.get(list[i].id);
        if (!Number.isFinite(startAt)) continue;
        if (next) {
            const nextAt = clientStart.get(next.id);
            if (Number.isFinite(nextAt)) {
                const diff = Math.round((nextAt - startAt) / 1000);
                list[i].duration = Math.max(0, diff);
            }
        } else if (loading) {
            // The active thought must use its own start, not the generation timer.
            list[i].duration = Math.max(0, Math.floor((now - startAt) / 1000));
        }
    }
    return list;
}

function deriveStatus({ items, loading, error }) {
    if (items.some((m) => m.status === 'error') || error) return 'error';
    if (items.some((m) => m.status === 'cancelled')) return 'cancelled';
    if (loading) return 'running';
    if (items.length > 0) return 'done';
    return 'idle';
}

export function useBlockStream({ items = [], loading = false, statusMessage = '', error = '' }) {
    const clientStartRef = useRef(new Map());

    const blocks = useMemo(() => {
        const now = Date.now();
        for (const msg of items) {
            if (msg?.id && !clientStartRef.current.has(msg.id)) {
                clientStartRef.current.set(msg.id, now);
            }
        }
        const mapped = items.map((m, index) => messageToBlock(m, index, {
            loading,
            isLast: index === items.length - 1,
        }));
        return computeDurations(mapped, { loading, clientStart: clientStartRef.current });
    }, [items, loading]);

    const status = deriveStatus({ items, loading, error });

    const [elapsed, setElapsed] = useState(0);
    const wasRunningRef = useRef(false);

    useEffect(() => {
        const isRunning = loading || status === 'running';
        if (isRunning) {
            if (!wasRunningRef.current) {
                setElapsed(0);
                wasRunningRef.current = true;
            }
            const id = window.setInterval(() => setElapsed((value) => value + 1), 1000);
            return () => window.clearInterval(id);
        }
        wasRunningRef.current = false;
        return undefined;
    }, [loading, status]);

    return { blocks, status, elapsed };
}
