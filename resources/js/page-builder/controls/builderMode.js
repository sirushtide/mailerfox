import { createContext } from 'react';

export const BuilderModeContext = createContext('page');

// Provides the active canvas viewport so controls (e.g. responsive typography)
// can switch the preview and reflect the current breakpoint.
export const BuilderViewportContext = createContext(null);
