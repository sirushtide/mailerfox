import React from 'react';

export function BlockSummary({ block }) {
    return (
        <div className="mt-2 border-t border-gray-100 pt-2">
            <p className="text-[13px] leading-relaxed text-gray-800">
                {block.content}
            </p>
        </div>
    );
}
