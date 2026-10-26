import * as React from 'react';
import { cn } from '../../lib/utils.js';

const badgeVariants = ({ variant = 'default', className = '' } = {}) =>
    cn(
        'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-ring',
        {
            default: 'border-transparent bg-primary text-primary-foreground',
            secondary: 'border-transparent bg-secondary text-secondary-foreground',
            outline: 'border-border bg-background text-foreground',
            success: 'border-transparent bg-emerald-100 text-emerald-700',
            destructive: 'border-transparent bg-destructive text-destructive-foreground',
            warning: 'border-transparent bg-amber-100 text-amber-700',
            info: 'border-transparent bg-sky-100 text-sky-700',
            muted: 'border-transparent bg-muted text-muted-foreground',
        }[variant] || 'border-transparent bg-secondary text-secondary-foreground',
        className
    );

function Badge({ className, variant = 'default', ...props }) {
    return <span className={badgeVariants({ variant, className })} {...props} />;
}

export { Badge, badgeVariants };
