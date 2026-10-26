import * as React from 'react';
import { createContext, useContext, useState } from 'react';
import { cn } from '../../lib/utils.js';
import { Slot } from './slot.jsx';

const CollapsibleContext = createContext(null);

function useCollapsible() {
    const context = useContext(CollapsibleContext);
    if (!context) {
        throw new Error('Collapsible components must be used within a <Collapsible>.');
    }
    return context;
}

function Collapsible({ open: openProp, defaultOpen = false, onOpenChange, className, children, ...props }) {
    const [openState, setOpenState] = useState(defaultOpen);
    const open = openProp !== undefined ? openProp : openState;

    const setOpen = (value) => {
        if (openProp === undefined) {
            setOpenState(value);
        }
        onOpenChange?.(value);
    };

    return (
        <CollapsibleContext.Provider value={{ open, setOpen }}>
            <div data-state={open ? 'open' : 'closed'} className={className} {...props}>
                {children}
            </div>
        </CollapsibleContext.Provider>
    );
}

function CollapsibleTrigger({ asChild = false, children, className, onClick, ...props }) {
    const { setOpen, open } = useCollapsible();

    const handleClick = (e) => {
        onClick?.(e);
        setOpen(!open);
    };

    if (asChild) {
        return (
            <Slot onClick={handleClick} aria-expanded={open} {...props}>
                {children}
            </Slot>
        );
    }

    return (
        <button type="button" onClick={handleClick} aria-expanded={open} className={cn('w-full', className)} {...props}>
            {children}
        </button>
    );
}

function CollapsibleContent({ className, children, ...props }) {
    const { open } = useCollapsible();
    return (
        <div data-state={open ? 'open' : 'closed'} className={cn('overflow-hidden', className)} {...props}>
            {children}
        </div>
    );
}

export { Collapsible, CollapsibleTrigger, CollapsibleContent };
