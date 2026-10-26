import * as React from 'react';
import { ChevronRight, MoreHorizontal } from 'lucide-react';
import { cn } from '../../lib/utils.js';
import { Slot } from './slot.jsx';

function Breadcrumb({ ...props }) {
    return <nav aria-label="breadcrumb" {...props} />;
}

function BreadcrumbList({ className, ...props }) {
    return (
        <ol
            className={cn('flex flex-wrap items-center gap-1.5 break-words text-sm text-muted-foreground sm:gap-2.5', className)}
            {...props}
        />
    );
}

function BreadcrumbItem({ className, ...props }) {
    return <li className={cn('inline-flex items-center gap-1.5', className)} {...props} />;
}

function BreadcrumbLink({ asChild = false, className, ...props }) {
    const Comp = asChild ? Slot : 'a';
    return <Comp className={cn('transition-colors hover:text-foreground', className)} {...props} />;
}

function BreadcrumbPage({ className, ...props }) {
    return (
        <span
            role="link"
            aria-disabled="true"
            aria-current="page"
            className={cn('font-normal text-foreground', className)}
            {...props}
        />
    );
}

function BreadcrumbSeparator({ children, className, ...props }) {
    return (
        <li role="presentation" aria-hidden="true" className={cn('[&>svg]:size-3.5', className)} {...props}>
            {children ?? <ChevronRight />}
        </li>
    );
}

function BreadcrumbEllipsis({ className, ...props }) {
    return (
        <span role="presentation" aria-hidden="true" className={cn('flex h-9 w-9 items-center justify-center', className)} {...props}>
            <MoreHorizontal className="size-4" />
            <span className="sr-only">More</span>
        </span>
    );
}

export { Breadcrumb, BreadcrumbList, BreadcrumbItem, BreadcrumbLink, BreadcrumbPage, BreadcrumbSeparator, BreadcrumbEllipsis };
