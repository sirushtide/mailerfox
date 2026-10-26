import React from 'react';
import { useBlockStream } from './useBlockStream.js';
import { BlockThought } from './BlockThought.jsx';
import { BlockNarration } from './BlockNarration.jsx';
import { BlockTool } from './BlockTool.jsx';
import { BlockSummary } from './BlockSummary.jsx';
import { BlockPlan } from './BlockPlan.jsx';

function renderBlock(block, { isLast, loading, elapsed }) {
    const active = isLast && loading;
    switch (block.type) {
        case 'thought':
            return <BlockThought key={block.id} block={block} loading={active} elapsed={elapsed} />;
        case 'tool':
            return <BlockTool key={block.id} block={block} active={active} />;
        case 'plan':
            return <BlockPlan key={block.id} block={block} />;
        case 'summary':
            return <BlockSummary key={block.id} block={block} />;
        case 'result':
            return (
                <p key={block.id} className="mt-1 text-[13px] leading-relaxed text-gray-800">
                    {block.content}
                </p>
            );
        case 'error':
            return (
                <p key={block.id} className="mb-1 text-[13px] leading-relaxed text-red-600">
                    {block.content || 'Something went wrong.'}
                </p>
            );
        case 'cancelled':
            return (
                <p key={block.id} className="mb-1 text-[13px] leading-relaxed text-gray-400">
                    {block.content || 'Cancelled.'}
                </p>
            );
        default:
            return <BlockNarration key={block.id} block={block} active={active} />;
    }
}

export function BlockStream({
    items = [],
    loading = false,
    statusMessage = '',
    error = '',
    compact = false,
    className = '',
}) {
    const { blocks, status, elapsed } = useBlockStream({ items, loading, statusMessage, error });

    const liveMessage = typeof statusMessage === 'string' && statusMessage.trim() !== '' ? statusMessage.trim() : null;
    const lastBlock = blocks[blocks.length - 1];
    const showLiveLine = loading && blocks.length > 0 && lastBlock && lastBlock.type !== 'thought' && liveMessage && lastBlock.content !== liveMessage;

    if (blocks.length === 0) {
        if (!loading) return null;
        return (
            <div className={`flex flex-col ${className}`}>
                <p className="text-[13px] text-gray-500">
                    <span className="mr-1.5 inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-gray-400 align-middle" />
                    {liveMessage || 'Thinking…'}
                </p>
            </div>
        );
    }

    return (
        <div className={`flex flex-col ${className}`}>
            {blocks.map((block, index) => renderBlock(block, {
                isLast: index === blocks.length - 1,
                loading,
                elapsed,
            }))}
            {showLiveLine ? (
                <p className="text-[13px] text-gray-500">
                    <span className="mr-1.5 inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-gray-400 align-middle" />
                    {liveMessage}
                </p>
            ) : null}
            {loading && blocks.length > 0 ? (
                <div className="mt-1 ml-0.5 inline-block h-2.5 w-2.5 animate-pulse rounded-full bg-orange-500" />
            ) : null}
        </div>
    );
}
