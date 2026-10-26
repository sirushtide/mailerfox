import React from 'react';

export function Switcher({ value, onChange, label, className = '' }) {
    const isOn = Boolean(value);
    return (
        <div className={`flex items-center justify-between ${className}`}>
            {label ? (
                <span className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</span>
            ) : null}
            <button
                type="button"
                role="switch"
                aria-checked={isOn}
                onClick={() => onChange(!isOn)}
                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 ${
                    isOn ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'
                }`}
            >
                <span
                    className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                        isOn ? 'translate-x-6' : 'translate-x-1'
                    }`}
                />
            </button>
        </div>
    );
}
