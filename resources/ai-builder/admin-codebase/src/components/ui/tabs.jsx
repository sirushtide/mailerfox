import * as React from 'react';
import { createContext, useContext, useState } from 'react';
import { cn } from '../../lib/utils.js';

const TabsContext = createContext(null);

function Tabs({ defaultValue, value: valueProp, onValueChange, className, children, ...props }) {
    const [valueState, setValueState] = useState(defaultValue);
    const value = valueProp !== undefined ? valueProp : valueState;

    const setValue = (next) => {
        if (valueProp === undefined) {
            setValueState(next);
        }
        onValueChange?.(next);
    };

    return (
        <TabsContext.Provider value={{ value, setValue }}>
            <div className={cn('w-full', className)} {...props}>
                {children}
            </div>
        </TabsContext.Provider>
    );
}

function TabsList({ className, ...props }) {
    return (
        <div
            role="tablist"
            className={cn('inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground', className)}
            {...props}
        />
    );
}

function TabsTrigger({ value, className, children, ...props }) {
    const { value: activeValue, setValue } = useContext(TabsContext);
    const isActive = activeValue === value;

    return (
        <button
            type="button"
            role="tab"
            aria-selected={isActive}
            data-state={isActive ? 'active' : 'inactive'}
            onClick={() => setValue(value)}
            className={cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                isActive ? 'bg-background text-foreground shadow-sm' : 'hover:text-foreground',
                className
            )}
            {...props}
        >
            {children}
        </button>
    );
}

function TabsContent({ value, className, children, ...props }) {
    const { value: activeValue } = useContext(TabsContext);

    if (activeValue !== value) {
        return null;
    }

    return (
        <div role="tabpanel" data-state="active" className={cn('mt-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring', className)} {...props}>
            {children}
        </div>
    );
}

export { Tabs, TabsList, TabsTrigger, TabsContent };
