import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    const merged = inputs
        .filter(Boolean)
        .map((input) => {
            if (typeof input === 'string') return input;
            if (Array.isArray(input)) return input.filter(Boolean).join(' ');
            return Object.entries(input || {})
                .filter(([, value]) => value)
                .map(([key]) => key)
                .join(' ');
        })
        .join(' ');

    return twMerge(merged);
}
