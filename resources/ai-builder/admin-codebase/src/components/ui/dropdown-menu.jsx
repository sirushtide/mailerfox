import * as React from 'react';
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { cn } from '../../lib/utils.js';
import { Slot } from './slot.jsx';

const DropdownMenuContext = createContext(null);

function useDropdown() {
    const context = useContext(DropdownMenuContext);
    if (!context) {
        throw new Error('DropdownMenu components must be used within a <DropdownMenu>.');
    }
    return context;
}

function DropdownMenu({ open: openProp, defaultOpen = false, onOpenChange, children, ...props }) {
    const [openState, setOpenState] = useState(defaultOpen);
    const open = openProp !== undefined ? openProp : openState;
    const [triggerRef, setTriggerRef] = useState(null);

    const setOpen = useCallback(
        (value) => {
            if (openProp === undefined) {
                setOpenState(value);
            }
            onOpenChange?.(value);
        },
        [openProp, onOpenChange]
    );

    const value = useMemo(
        () => ({ open, setOpen, triggerRef, setTriggerRef }),
        [open, setOpen, triggerRef]
    );

    return (
        <DropdownMenuContext.Provider value={value} {...props}>
            {children}
        </DropdownMenuContext.Provider>
    );
}

function DropdownMenuTrigger({ asChild = false, children, onClick, ...props }) {
    const { open, setOpen, setTriggerRef } = useDropdown();

    const handleClick = (e) => {
        onClick?.(e);
        setOpen(!open);
    };

    const setRef = (node) => {
        setTriggerRef(node);
    };

    if (asChild) {
        return (
            <Slot ref={setRef} data-state={open ? 'open' : 'closed'} onClick={handleClick} aria-haspopup="menu" aria-expanded={open} {...props}>
                {children}
            </Slot>
        );
    }

    return (
        <button
            ref={setRef}
            type="button"
            data-state={open ? 'open' : 'closed'}
            onClick={handleClick}
            aria-haspopup="menu"
            aria-expanded={open}
            {...props}
        >
            {children}
        </button>
    );
}

function DropdownMenuContent({ className, side = 'bottom', align = 'start', sideOffset = 4, children, ...props }) {
    const { open, setOpen, triggerRef } = useDropdown();
    const [rect, setRect] = useState(null);

    useEffect(() => {
        if (open && triggerRef) {
            setRect(triggerRef.getBoundingClientRect());
        }
    }, [open, triggerRef]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }
        const onPointerDown = (e) => {
            if (contentRef.current && contentRef.current.contains(e.target)) {
                return;
            }
            if (triggerRef && triggerRef.contains(e.target)) {
                return;
            }
            setOpen(false);
        };
        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open, setOpen, triggerRef]);

    const contentRef = useRef(null);

    if (!open) {
        return null;
    }

    if (!rect) {
        return null;
    }

    let top = null;
    let left = null;
    let bottom = null;
    const transforms = [];

    if (side === 'top') {
        top = rect.top - sideOffset;
        transforms.push('translateY(-100%)');
    } else {
        top = rect.bottom + sideOffset;
    }

    if (align === 'end') {
        left = rect.right;
        transforms.push('translateX(-100%)');
    } else if (align === 'center') {
        left = rect.left + rect.width / 2;
        transforms.push('translateX(-50%)');
    } else {
        left = rect.left;
    }

    const transform = transforms.length ? transforms.join(' ') : undefined;

    return createPortal(
        <div
            ref={contentRef}
            role="menu"
            data-state="open"
            style={{
                position: 'fixed',
                top: top !== null ? `${top}px` : undefined,
                left: left !== null ? `${left}px` : undefined,
                bottom: bottom !== null ? `${bottom}px` : undefined,
                transform,
                zIndex: 100,
                '--radix-dropdown-menu-trigger-width': `${rect.width}px`,
            }}
            className={cn(
                'min-w-[8rem] overflow-hidden rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-lg',
                className
            )}
            {...props}
        >
            {children}
        </div>,
        document.body
    );
}

function DropdownMenuItem({ className, inset = false, variant = 'default', onClick, children, ...props }) {
    const { setOpen } = useDropdown();

    const handleClick = (e) => {
        onClick?.(e);
        setOpen(false);
    };

    return (
        <button
            type="button"
            role="menuitem"
            onClick={handleClick}
            className={cn(
                'flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-popover-foreground outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground',
                inset && 'pl-8',
                variant === 'destructive' && 'text-destructive hover:bg-destructive/10 hover:text-destructive',
                '[&>svg]:size-4 [&>svg]:shrink-0',
                className
            )}
            {...props}
        >
            {children}
        </button>
    );
}

function DropdownMenuCheckboxItem({ className, children, checked, onCheckedChange, ...props }) {
    return (
        <button type="button" role="menuitemcheckbox" aria-checked={checked} onClick={() => onCheckedChange?.(!checked)} className={cn('relative flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground', className)} {...props}>
            {children}
        </button>
    );
}

function DropdownMenuRadioItem({ className, children, ...props }) {
    return (
        <button type="button" role="menuitemradio" className={cn('relative flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground', className)} {...props}>
            {children}
        </button>
    );
}

function DropdownMenuLabel({ className, inset = false, ...props }) {
    return <div className={cn('px-2 py-1.5 text-sm font-semibold', inset && 'pl-8', className)} {...props} />;
}

function DropdownMenuGroup({ className, ...props }) {
    return <div className={cn('flex w-full flex-col', className)} {...props} />;
}

function DropdownMenuSeparator({ className, ...props }) {
    return <div className={cn('-mx-1 my-1 h-px bg-border', className)} {...props} />;
}

function DropdownMenuShortcut({ className, ...props }) {
    return <span className={cn('ml-auto text-xs tracking-widest text-muted-foreground', className)} {...props} />;
}

function DropdownMenuSub({ ...props }) {
    return <div {...props} />;
}

export {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuCheckboxItem,
    DropdownMenuRadioItem,
    DropdownMenuLabel,
    DropdownMenuGroup,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuSub,
};
