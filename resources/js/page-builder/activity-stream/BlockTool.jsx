import React, { useState } from 'react';
import { ChevronDown } from 'lucide-react';

function actionLabel(action) {
    const value = String(action || 'edit').trim();
    if (value === '') return 'Edit';
    return value.charAt(0).toUpperCase() + value.slice(1);
}

export function BlockTool({ block, active = false }) {
    const [open, setOpen] = useState(false);
    const hasExtra = block.content && block.content.trim() !== '' && block.content.trim().toLowerCase() !== actionLabel(block.action).toLowerCase();

    return (
        <div className="mb-1">
            <button
                type="button"
                onClick={() => hasExtra && setOpen((value) => !value)}
                className={`flex w-full flex-wrap items-center gap-x-1.5 gap-y-0.5 rounded px-1 -mx-1 text-left text-[13px] ${hasExtra ? 'cursor-pointer hover:bg-gray-50' : 'cursor-default'} ${active ? 'mp-text-shimmer font-medium' : ''}`}
            >
                <span className="shrink-0 text-gray-500">{actionLabel(block.action)}</span>
                {block.detail ? (
                    <span className="shrink-0 font-mono text-[12px] text-gray-400">{block.detail}</span>
                ) : null}
                {hasExtra ? (
                    <ChevronDown className={`h-3 w-3 shrink-0 text-gray-400 transition-transform ${open ? 'rotate-180' : ''}`} />
                ) : null}
            </button>
            {open && hasExtra ? (
                <p className="ml-2 mt-0.5 border-l-2 border-gray-200 pl-2.5 text-[12.5px] leading-relaxed text-gray-500">
                    {block.content}
                </p>
            ) : null}
        </div>
    );
}
