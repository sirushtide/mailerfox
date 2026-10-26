import * as React from 'react';
import { forwardRef } from 'react';
import { cn } from '../../lib/utils.js';

const Separator = forwardRef(({ className, orientation = 'horizontal', decorative = true, ...props }, ref) => (
    <div
        ref={ref}
        role={decorative ? 'none' : 'separator'}
        aria-orientation={orientation === 'vertical' ? 'vertical' : undefined}
        className={cn('shrink-0 bg-border', orientation === 'vertical' ? 'w-px' : 'h-px', className)}
        {...props}
    />
));
Separator.displayName = 'Separator';

export { Separator };
