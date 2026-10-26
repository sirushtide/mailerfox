import * as React from 'react';
import { cn } from '../../lib/utils.js';

function PageHeader({ title, description = '', actions = null, className, children, ...props }) {
    return (
        <div className={cn('flex flex-col gap-4 md:flex-row md:items-center md:justify-between', className)} {...props}>
            <div className="min-w-0">
                <h1 className="text-xl font-bold tracking-tight text-foreground md:text-2xl">{title}</h1>
                {description && <p className="mt-1 text-sm text-muted-foreground">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
            {children}
        </div>
    );
}

export { PageHeader };
