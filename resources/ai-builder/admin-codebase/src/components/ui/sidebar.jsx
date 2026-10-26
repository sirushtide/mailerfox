import * as React from 'react';
import { createContext, forwardRef, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { PanelLeft } from 'lucide-react';
import { useIsMobile } from '../../hooks/use-mobile.js';
import { cn } from '../../lib/utils.js';
import { Slot } from './slot.jsx';

const SIDEBAR_WIDTH = '16rem';
const SIDEBAR_WIDTH_MOBILE = '18rem';
const SIDEBAR_WIDTH_ICON = '3rem';
const SIDEBAR_KEYBOARD_SHORTCUT = 'b';

const SidebarContext = createContext(null);

export function useSidebar() {
    const context = useContext(SidebarContext);
    if (!context) {
        throw new Error('useSidebar must be used within SidebarProvider.');
    }
    return context;
}

function getOpenStateFromCookie() {
    try {
        const value = document.cookie
            .split('; ')
            .find((row) => row.startsWith('sidebar_state='));
        return value ? value.split('=')[1] === 'true' : true;
    } catch {
        return true;
    }
}

export const SidebarProvider = forwardRef(({ defaultOpen = true, open: openProp, onOpenChange, className, style, children, ...props }, ref) => {
    const isMobile = useIsMobile();
    const [openMobile, setOpenMobile] = useState(false);
    const [openState, setOpenState] = useState(defaultOpen);

    const open = openProp !== undefined ? openProp : openState;
    const openRef = useRef(open);
    useEffect(() => {
        openRef.current = open;
    }, [open]);

    const setOpen = useCallback(
        (value) => {
            const next = typeof value === 'function' ? value(openRef.current) : value;
            openRef.current = next;
            if (openProp === undefined) {
                setOpenState(next);
            }
            onOpenChange?.(next);
        },
        [onOpenChange, openProp]
    );

    const toggleSidebar = useCallback(() => {
        if (isMobile) {
            setOpenMobile((prev) => !prev);
            return;
        }
        setOpen(!openRef.current);
    }, [isMobile, setOpen]);

    useEffect(() => {
        const onKeyDown = (e) => {
            if (e.key === SIDEBAR_KEYBOARD_SHORTCUT && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                toggleSidebar();
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [toggleSidebar]);

    const value = useMemo(
        () => ({
            state: open ? 'expanded' : 'collapsed',
            collapsible: !isMobile && !open ? 'icon' : 'none',
            open,
            setOpen,
            isMobile,
            openMobile,
            setOpenMobile,
            toggleSidebar,
        }),
        [open, setOpen, isMobile, openMobile, toggleSidebar]
    );

    return (
        <SidebarContext.Provider value={value}>
            <div
                ref={ref}
                data-slot="sidebar-provider"
                data-collapsible={!isMobile && !open ? 'icon' : 'none'}
                className={cn('group/sidebar-wrapper flex min-h-svh w-full', className)}
                style={{ '--sidebar-width': SIDEBAR_WIDTH, '--sidebar-width-icon': SIDEBAR_WIDTH_ICON, '--sidebar-width-mobile': SIDEBAR_WIDTH_MOBILE, ...style }}
                {...props}
            >
                {children}
            </div>
        </SidebarContext.Provider>
    );
});
SidebarProvider.displayName = 'SidebarProvider';

export const Sidebar = forwardRef(({ side = 'left', variant = 'sidebar', collapsible = 'offcanvas', className, children, ...props }, ref) => {
    const { isMobile, state, openMobile, setOpenMobile, open, toggleSidebar } = useSidebar();

    // Desktop: collapse to an icon rail when the provider is toggled closed.
    // Mobile: render as an overlay sheet driven by openMobile.
    const effective = isMobile ? 'offcanvas' : (state === 'collapsed' ? 'icon' : 'none');

    return (
        <>
            <aside
                ref={ref}
                data-slot="sidebar"
                data-sidebar="sidebar"
                data-collapsible={effective}
                data-mobile={isMobile ? 'true' : undefined}
                data-open={isMobile && openMobile ? 'true' : 'false'}
                className={cn(
                    'group/sidebar flex h-svh shrink-0 flex-col bg-sidebar text-sidebar-foreground transition-[width,transform] duration-200 ease-linear',
                    'sticky top-0',
                    side === 'right' ? 'right-0 order-last' : 'left-0 order-first',
                    effective === 'icon' && !isMobile ? 'data-[collapsible=icon]' : '',
                    className
                )}
                {...props}
            >
                {children}
            </aside>
            {isMobile && openMobile && (
                <div data-slot="sidebar-overlay" className="fixed inset-0 z-40 bg-black/50" onClick={() => setOpenMobile(false)} />
            )}
        </>
    );
});
Sidebar.displayName = 'Sidebar';

export function SidebarContent({ className, ...props }) {
    const { isMobile } = useSidebar();
    return (
        <div
            data-slot="sidebar-content"
            data-mobile-viewport={isMobile ? 'mobile' : 'desktop'}
            className={cn('flex w-full flex-1 flex-col overflow-y-auto', className)}
            {...props}
        />
    );
}

export function SidebarHeader({ className, ...props }) {
    return <div data-slot="sidebar-header" className={cn('flex w-full flex-col p-2', className)} {...props} />;
}

export function SidebarFooter({ className, ...props }) {
    return <div data-slot="sidebar-footer" className={cn('flex w-full flex-col gap-2 p-2', className)} {...props} />;
}

export function SidebarRail({ className, ...props }) {
    const { toggleSidebar } = useSidebar();
    return (
        <button
            data-slot="sidebar-rail"
            aria-label="Toggle Sidebar"
            tabIndex={-1}
            onClick={toggleSidebar}
            title="Collapse Sidebar"
            className={cn(
                'group/rail absolute inset-y-0 z-20 hidden w-4 -translate-x-1/2 cursor-ew-resize items-center justify-center transition-all ease-linear md:flex',
                'right-0',
                className
            )}
            {...props}
        />
    );
}

export function SidebarInset({ className, ...props }) {
    return (
        <main
            data-slot="sidebar-inset"
            className={cn('relative flex w-full flex-1 flex-col bg-background', className)}
            {...props}
        />
    );
}

export function SidebarTrigger({ className, onClick, ...props }) {
    const { toggleSidebar, isMobile, openMobile, setOpenMobile } = useSidebar();

    const handleClick = (e) => {
        onClick?.(e);
        if (isMobile) {
            setOpenMobile(!openMobile);
        } else {
            toggleSidebar();
        }
    };

    return (
        <button
            data-slot="sidebar-trigger"
            aria-label="Toggle Sidebar"
            onClick={handleClick}
            className={cn(
                'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-background text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                className
            )}
            {...props}
        >
            <PanelLeft className="size-4" />
            <span className="sr-only">Toggle Sidebar</span>
        </button>
    );
}

export function SidebarGroup({ className, ...props }) {
    return <div data-slot="sidebar-group" className={cn('flex w-full flex-col', className)} {...props} />;
}

export function SidebarGroupLabel({ className, ...props }) {
    return (
        <div
            data-slot="sidebar-group-label"
            className={cn('flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium text-sidebar-foreground/70 outline-none', className)}
            {...props}
        />
    );
}

export function SidebarGroupAction({ className, ...props }) {
    return (
        <button
            data-slot="sidebar-group-action"
            className={cn('absolute right-3 top-0 flex aspect-square h-5 w-5 items-center justify-center rounded-md text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground', className)}
            {...props}
        />
    );
}

export function SidebarMenu({ className, ...props }) {
    return <ul data-slot="sidebar-menu" className={cn('flex w-full flex-col gap-1', className)} {...props} />;
}

export function SidebarMenuItem({ className, ...props }) {
    return <li data-slot="sidebar-menu-item" className={cn('group/menu-item relative', className)} {...props} />;
}

export const SidebarMenuButton = forwardRef(({ asChild = false, isActive = false, variant = 'default', size = 'default', tooltip, className, onClick, ...props }, ref) => {
    const { isMobile, setOpenMobile } = useSidebar();

    const handleClick = (e) => {
        if (isMobile) {
            setOpenMobile(false);
        }
        onClick?.(e);
    };

    const classes = cn(
        'group/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm font-medium outline-none transition-[width,height,padding] focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50',
        size === 'sm' ? 'h-8 text-xs' : 'h-9',
        variant === 'outline' ? 'border border-border bg-transparent' : '',
        'data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground data-[active=true]:font-semibold',
        'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
        '[&>svg]:size-4 [&>svg]:shrink-0',
        className
    );

    if (asChild) {
        return (
            <Slot ref={ref} data-active={isActive || undefined} title={tooltip} onClick={handleClick} className={classes} {...props} />
        );
    }

    return (
        <button ref={ref} type="button" data-active={isActive || undefined} title={tooltip} onClick={handleClick} className={classes} {...props} />
    );
});
SidebarMenuButton.displayName = 'SidebarMenuButton';

export function SidebarMenuAction({ className, asChild = false, showOnHover = false, onClick, ...props }) {
    const Comp = asChild ? Slot : 'button';
    return (
        <Comp
            data-slot="sidebar-menu-action"
            onClick={onClick}
            className={cn(
                'absolute right-1 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md p-0 text-sidebar-foreground/70 outline-none transition-transform hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 focus-visible:ring-ring',
                showOnHover && 'opacity-0 group-hover/menu-item:opacity-100',
                className
            )}
            {...props}
        />
    );
}

export function SidebarMenuBadge({ className, ...props }) {
    return <div className={cn('absolute right-1 top-1/2 -translate-y-1/2', className)} {...props} />;
}

export function SidebarMenuSub({ className, ...props }) {
    return (
        <ul
            data-slot="sidebar-menu-sub"
            className={cn('mx-3.5 flex min-w-0 flex-col gap-1 border-l border-sidebar-border px-2.5 py-0.5', className)}
            {...props}
        />
    );
}

export function SidebarMenuSubItem({ className, ...props }) {
    return <li data-slot="sidebar-menu-sub-item" className={cn('group/menu-sub-item relative', className)} {...props} />;
}

export const SidebarMenuSubButton = forwardRef(({ asChild = false, size = 'md', isActive = false, className, ...props }, ref) => {
    const classes = cn(
        'flex h-7 min-w-0 items-center gap-2 overflow-hidden rounded-md px-2 text-sidebar-foreground/70 outline-none hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50',
        'data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground',
        '[&>svg]:size-3.5 [&>svg]:shrink-0',
        className
    );

    if (asChild) {
        return <Slot ref={ref} data-active={isActive || undefined} className={classes} {...props} />;
    }

    return <a ref={ref} href="#" data-active={isActive || undefined} className={classes} {...props} />;
});
SidebarMenuSubButton.displayName = 'SidebarMenuSubButton';

export { SidebarContext };
