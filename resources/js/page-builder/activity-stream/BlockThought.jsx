import React, { useState } from 'react';
import { ChevronDown } from 'lucide-react';

export function BlockThought({ block, loading = false, elapsed = 0 }) {
    const [open, setOpen] = useState(false);
    const hasText = (block.thought && block.thought.trim() !== '') || (block.content && block.content.trim() !== '');
    const text = block.thought || block.content;
    const ownElapsed = Number.isFinite(Number(block.startedAt))
        ? Math.max(0, Math.floor((Date.now() - Number(block.startedAt)) / 1000))
        : elapsed;
    const duration = block.duration ?? (loading ? ownElapsed : null);
    const label = loading
        ? 'Thinking…'
        : duration != null
            ? `Thought for ${Math.max(1, duration)}s`
            : 'Thought';

    return (
        <div className="mb-1">
            {hasText ? (
                <button
                    type="button"
                    onClick={() => setOpen((value) => !value)}
                    className="group inline-flex items-center gap-1 rounded text-[13px] text-gray-800 transition-colors hover:text-gray-950"
                >
                    <ChevronDown className={`h-3 w-3 shrink-0 text-gray-400 transition-transform ${open ? 'rotate-180' : ''}`} />
                    <span className={loading ? 'mp-text-shimmer font-medium' : 'font-medium'}>{label}</span>
                </button>
            ) : (
                <div className="inline-flex items-center gap-1 text-[13px] text-gray-800">
                    <ChevronDown className="h-3 w-3 shrink-0 text-gray-400" />
                    <span className={loading ? 'mp-text-shimmer font-medium' : 'font-medium'}>{label}</span>
                </div>
            )}
            {open && hasText ? (
                <p className="ml-4 mt-0.5 border-l-2 border-gray-200 pl-2.5 text-[12.5px] italic leading-relaxed text-gray-500">
                    {text}
                </p>
            ) : null}
        </div>
    );
}
