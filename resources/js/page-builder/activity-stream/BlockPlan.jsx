import React from 'react';

function PlanMark({ status, active }) {
    if (status === 'done') {
        return (
            <span className="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <svg width="9" height="9" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1.5 5.5L4 8L8.5 2.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            </span>
        );
    }
    if (status === 'in_progress') {
        return (
            <span aria-hidden className={`inline-block h-3.5 w-3.5 shrink-0 rounded-full border border-gray-300 border-t-blue-500 ${active ? 'animate-spin' : ''}`} />
        );
    }
    return <span className="inline-block h-3.5 w-3.5 shrink-0 rounded-full border border-gray-300" />;
}

export function BlockPlan({ block }) {
    const items = Array.isArray(block?.plan) ? block.plan : [];
    if (items.length === 0) return null;

    const buildingCount = items.filter((item) => item.status === 'in_progress').length;
    const doneCount = items.filter((item) => item.status === 'done').length;

    return (
        <div className="mt-1 rounded-lg border border-gray-200 bg-white/60 p-3 dark:border-gray-700 dark:bg-white/5">
            <div className="mb-2 flex items-center justify-between">
                <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Build plan</p>
                <span className={`text-[11px] font-medium text-gray-400 ${block?.status === 'progress' ? 'animate-pulse text-blue-500' : ''}`}>
                    {doneCount}/{items.length}
                </span>
            </div>
            <ul className="space-y-1.5">
                {items.map((item) => (
                    <li key={item.id || item.title} className="flex items-center gap-2">
                        <PlanMark status={item.status} active={block?.status === 'progress'} />
                        <span
                            className={`text-[13px] ${
                                item.status === 'done'
                                    ? 'text-gray-400 line-through'
                                    : item.status === 'in_progress'
                                      ? 'font-medium text-gray-900 dark:text-white'
                                      : 'text-gray-600 dark:text-gray-300'
                            }`}
                        >
                            {item.title}
                        </span>
                    </li>
                ))}
            </ul>
            {buildingCount > 0 ? (
                <p className="mt-2 text-[11px] text-gray-400">
                    Building the highlighted section now…
                </p>
            ) : null}
        </div>
    );
}