import React from 'react';
import { ControlsRenderer } from './controls/renderer.jsx';
import { siteSettingsSchema } from './schemas/siteSettings.js';

export function GlobalOverridesPanel({ value, onChange, globals, uploadUrl, onClose }) {
    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            onClick={(e) => {
                if (e.target === e.currentTarget) onClose();
            }}
        >
            <div className="w-full max-w-2xl max-h-[80vh] overflow-y-auto rounded-lg bg-white p-4 shadow-xl dark:bg-gray-900">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Page Global Overrides</h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        aria-label="Close"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Override site-wide colors, typography, and button presets for this page only.
                </p>
                <ControlsRenderer
                    schema={siteSettingsSchema}
                    value={value}
                    onChange={onChange}
                    globals={globals}
                    uploadUrl={uploadUrl}
                />
            </div>
        </div>
    );
}
