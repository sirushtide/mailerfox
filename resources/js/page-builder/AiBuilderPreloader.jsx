import React, { useEffect, useState } from 'react';

const MESSAGES = [
    'Planning your layout',
    'Writing the code',
    'Designing the interface',
    'Preparing the preview',
    'Almost there',
];

export function AiBuilderPreloader({ overlay = false }) {
    const [index, setIndex] = useState(0);

    useEffect(() => {
        const interval = setInterval(() => {
            setIndex((prev) => (prev + 1) % MESSAGES.length);
        }, 4500);
        return () => clearInterval(interval);
    }, []);

    return (
        <div
            className={`flex flex-col items-center justify-center ${
                overlay
                    ? 'absolute inset-0 z-20 backdrop-blur-md bg-white/50'
                    : 'h-full w-full bg-gradient-to-t from-orange-100/70 via-white to-white'
            }`}
        >
            <h2 className="text-lg font-semibold text-gray-900">We are building your app</h2>
            <p className="mt-2 text-sm text-gray-500 transition-opacity duration-500 ease-in-out">
                {MESSAGES[index]}
                <span className="inline-flex ml-px">
                    <span className="mp-ellipsis-dot">.</span>
                    <span className="mp-ellipsis-dot" style={{ animationDelay: '0.2s' }}>.</span>
                    <span className="mp-ellipsis-dot" style={{ animationDelay: '0.4s' }}>.</span>
                </span>
            </p>
            <style>{`
                @keyframes mp-ellipsis {
                    0%, 100% { opacity: 0.25; transform: translateY(0); }
                    50% { opacity: 1; transform: translateY(-2px); }
                }
                .mp-ellipsis-dot {
                    display: inline-block;
                    animation: mp-ellipsis 1.2s infinite ease-in-out;
                }
            `}</style>
        </div>
    );
}
