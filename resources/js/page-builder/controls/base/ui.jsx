import React, { useState } from 'react';
import { BuilderViewportContext } from '../builderMode.js';

const DEVICE_OPTIONS = [
    { id: 'desktop', label: 'Desktop', icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
    ) },
    { id: 'tablet', label: 'Tablet', icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="16" height="20" x="4" y="2" rx="2"/><line x1="12" x2="12.01" y1="18" y2="18"/></svg>
    ) },
    { id: 'mobile', label: 'Mobile', icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="12" height="20" x="6" y="2" rx="2"/><line x1="12" x2="12.01" y1="18" y2="18"/></svg>
    ) },
];

export function DeviceIcons() {
    const ctx = React.useContext(BuilderViewportContext);
    const device = ctx && typeof ctx.viewport === 'string' ? ctx.viewport : 'desktop';
    const setDevice = (id) => {
        if (ctx && typeof ctx.setViewport === 'function') ctx.setViewport(id);
    };

    return (
        <div className="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-0.5">
            {DEVICE_OPTIONS.map((d) => (
                <button
                    key={d.id}
                    type="button"
                    title={d.label}
                    onClick={() => setDevice(d.id)}
                    className={`inline-flex h-6 w-7 items-center justify-center rounded-md transition-colors ${
                        device === d.id
                            ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300'
                            : 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800'
                    }`}
                >
                    {d.icon}
                </button>
            ))}
        </div>
    );
}

export function DeviceSwitcher({ label = 'Responsive' } = {}) {
    return (
        <div className="flex items-center justify-between py-1">
            <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{label}</span>
            <DeviceIcons />
        </div>
    );
}


export function HeadingControl({ label }) {
    return (
        <div className="pt-2 pb-1">
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{label}</div>
        </div>
    );
}

export function DividerControl() {
    return <hr className="border-gray-200 dark:border-gray-700" />;
}

export function NoticeControl({ label, message, type = 'info' }) {
    const bg = type === 'warning' ? 'bg-yellow-50 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200' : 'bg-blue-50 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200';
    return (
        <div className={`rounded-md p-2 text-xs ${bg}`}>
            <strong>{label}</strong>
            {message ? <div className="mt-1">{message}</div> : null}
        </div>
    );
}

export function RawHtmlControl({ html }) {
    return <div dangerouslySetInnerHTML={{ __html: html || '' }} />;
}

export function PopoverToggleControl({ label, children, defaultOpen = false }) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
                <span>{label}</span>
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
            {open ? <div className="px-3 pb-3 space-y-3 border-t border-gray-100 dark:border-gray-800">{children}</div> : null}
        </div>
    );
}

export function TabsControl({ tabs = [], activeTab: controlled, onChange, children }) {
    const [internal, setInternal] = useState((tabs[0] && tabs[0].value) || 0);
    const active = controlled !== undefined ? controlled : internal;
    const setActive = (v) => {
        if (onChange) onChange(v);
        setInternal(v);
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                {tabs.map((tab) => (
                    <button
                        key={tab.value}
                        type="button"
                        onClick={() => setActive(tab.value)}
                        className={`flex-1 h-8 text-xs font-medium transition-colors ${
                            active === tab.value
                                ? 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white'
                                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5'
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>
            <div>{children}</div>
        </div>
    );
}

// Responsive column-count control: desktop resizes the column array, tablet /
// mobile store per-breakpoint counts used to hide extra columns.
export function ColumnsControl({ label = 'Columns', value, onChange }) {
    const ctx = React.useContext(BuilderViewportContext);
    const device = ctx && typeof ctx.viewport === 'string' ? ctx.viewport : 'desktop';
    const model = value && typeof value === 'object' ? value : {};
    const style = model.style && typeof model.style === 'object' ? model.style : {};
    const desktop = Array.isArray(model.columns) ? model.columns.length : 1;
    const tablet = Number.isFinite(Number(style.columnsTablet)) ? Number(style.columnsTablet) : desktop;
    const mobile = Number.isFinite(Number(style.columnsMobile)) ? Number(style.columnsMobile) : desktop;
    const current = device === 'tablet' ? tablet : device === 'mobile' ? mobile : desktop;

    const setCount = (raw) => {
        const n = Math.max(1, Math.min(6, Math.round(Number(raw) || 1)));
        if (device === 'desktop') {
            onChange({ columns: { length: String(n) } });
        } else if (device === 'tablet') {
            onChange({ style: { columnsTablet: n } });
        } else {
            onChange({ style: { columnsMobile: n } });
        }
    };

    return (
        <div className="space-y-1">
            <div className="flex items-center justify-between">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300">{label}</label>
                <DeviceIcons />
            </div>
            <select
                value={String(current)}
                onChange={(e) => setCount(e.target.value)}
                className="block w-full rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
            >
                {[1, 2, 3, 4, 5, 6].map((n) => (
                    <option key={n} value={n}>{n}</option>
                ))}
            </select>
        </div>
    );
}

