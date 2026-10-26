import React from 'react';

export function BlockNarration({ block, active = false }) {
    if (!block.content || block.content.trim() === '') return null;
    return (
        <p className={`mb-1 whitespace-pre-wrap text-[13px] leading-relaxed text-gray-700 ${active ? 'mp-text-shimmer' : ''}`}>
            {block.content}
        </p>
    );
}
