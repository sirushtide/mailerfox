import React, { useState } from 'react';

export function StyleAccordionItem({ title, children, defaultOpen = true }) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <div className="border-b !mt-0 !mb-0 py-1.5 border-gray-200 dark:border-gray-700 w-full bg-white dark:bg-gray-900">
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold leading-none text-gray-900 dark:text-gray-200"
            >
                <span>{title}</span>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className={`transition-transform ${open ? 'rotate-180' : ''}`}
                >
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            {open ? (
                <div className="py-2 space-y-4 w-full">{children}</div>
            ) : null}
        </div>
    );
}

export function InspectorSection({ title, children }) {
    return (
        <div className="space-y-3">
            {title ? (
                <div className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {title}
                </div>
            ) : null}
            {children}
        </div>
    );
}
