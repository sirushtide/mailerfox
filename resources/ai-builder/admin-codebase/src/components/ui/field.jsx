import * as React from 'react';
import { cn } from '../../lib/utils.js';

const displayClass = /(?:^|\s)(?:flex|inline-flex|grid|inline-grid|block|inline-block|table)(?:$|\s)/;

function Field({ className, ...props }) {
    const override = displayClass.test(className || '');
    return (
        <div data-slot="field" className={cn(!override && 'flex flex-col gap-2', className)} {...props} />
    );
}

function FieldGroup({ className, ...props }) {
    return (
        <div data-slot="field-group" className={cn('flex flex-col gap-4', className)} {...props} />
    );
}

function FieldLabel({ className, ...props }) {
    return (
        <label data-slot="field-label" className={cn('text-sm font-medium leading-none text-foreground', className)} {...props} />
    );
}

function FieldDescription({ className, ...props }) {
    return (
        <p data-slot="field-description" className={cn('text-sm text-muted-foreground', className)} {...props} />
    );
}

function FieldSeparator({ className, children, ...props }) {
    return (
        <div data-slot="field-separator" className={cn('flex items-center gap-3 text-xs text-muted-foreground', className)} {...props}>
            <div className="h-px flex-1 bg-border" />
            <span data-slot="field-separator-content" className="shrink-0">{children}</span>
            <div className="h-px flex-1 bg-border" />
        </div>
    );
}

export { Field, FieldDescription, FieldGroup, FieldLabel, FieldSeparator };
