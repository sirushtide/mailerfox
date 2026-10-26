import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

export function Popover({ trigger, children, width = 320, maxHeight = 520, title }) {
    const triggerRef = useRef(null);
    const panelRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [panelStyle, setPanelStyle] = useState(() => ({ top: 0, left: 0, width, maxHeight, placement: 'bottom' }));

    useEffect(() => {
        if (!open) return;

        const updatePosition = () => {
            const el = triggerRef.current;
            if (!el) return;

            const rail = el.closest('[data-mp-builder-rail="1"]');
            const railRect = rail ? rail.getBoundingClientRect() : null;
            const boundsLeft = railRect ? railRect.left : 8;
            const boundsRight = railRect ? railRect.right : window.innerWidth - 8;
            const boundsWidth = Math.max(0, boundsRight - boundsLeft);

            const rect = el.getBoundingClientRect();
            const gap = 8;
            const maxWidth = Math.min(window.innerWidth - 16, boundsWidth - 16);
            const w = Math.max(220, Math.min(width, Math.max(220, maxWidth)));

            let left = rect.right - w;
            left = Math.max(boundsLeft + 8, Math.min(left, boundsRight - w - 8));

            const belowTop = rect.bottom + gap;
            const spaceBelow = window.innerHeight - belowTop - 8;
            const spaceAbove = rect.top - 8;

            const placeBelow = spaceBelow >= 260 || spaceBelow >= spaceAbove;
            const aboveHeight = Math.min(320, Math.max(180, spaceAbove - gap));
            const top = placeBelow ? belowTop : Math.max(8, rect.top - gap - aboveHeight);
            const mh = Math.max(180, Math.min(maxHeight + 40, placeBelow ? spaceBelow : aboveHeight));

            setPanelStyle({ top, left, width: w, maxHeight: mh, placement: placeBelow ? 'bottom' : 'top' });
        };

        updatePosition();
        window.addEventListener('resize', updatePosition);
        window.addEventListener('scroll', updatePosition, true);

        return () => {
            window.removeEventListener('resize', updatePosition);
            window.removeEventListener('scroll', updatePosition, true);
        };
    }, [open]);

    useEffect(() => {
        if (!open) return;

        const onClick = (e) => {
            const t = e.target;
            if (panelRef.current && panelRef.current.contains(t)) return;
            if (triggerRef.current && triggerRef.current.contains(t)) return;
            setOpen(false);
        };

        const onKeyDown = (e) => {
            if (e.key === 'Escape') setOpen(false);
        };

        document.addEventListener('click', onClick);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('click', onClick);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    const arrowClassName =
        panelStyle.placement === 'bottom'
            ? 'absolute right-3 h-4 w-4 rotate-45 border-l border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900'
            : 'absolute right-3 h-4 w-4 rotate-45 border-r border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900';

    return (
        <div className="relative">
            <div ref={triggerRef} onClick={() => setOpen((v) => !v)}>
                {trigger}
            </div>

            {open
                ? createPortal(
                      <div
                          ref={panelRef}
                          className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl p-3"
                          style={{
                              position: 'fixed',
                              top: panelStyle.top,
                              left: panelStyle.left,
                              width: panelStyle.width,
                              maxHeight: panelStyle.maxHeight,
                              overflowY: 'auto',
                              overscrollBehavior: 'contain',
                              zIndex: 9999,
                          }}
                      >
                          <div
                              className={arrowClassName}
                              style={{
                                  zIndex: 10000,
                                  top: panelStyle.placement === 'bottom' ? -8 : 'auto',
                                  bottom: panelStyle.placement === 'top' ? -8 : 'auto',
                              }}
                          />
                          {title ? (
                              <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{title}</div>
                          ) : null}
                          {typeof children === 'function' ? children({ close: () => setOpen(false) }) : children}
                      </div>,
                      document.body
                  )
                : null}
        </div>
    );
}

export function PopoverTriggerButton({ icon, label, active = false, title }) {
    return (
        <button
            type="button"
            className={`inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 dark:border-gray-700 ${active ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800'}`}
            title={title || label}
        >
            {icon || <span className="text-xs font-medium">{label}</span>}
        </button>
    );
}
