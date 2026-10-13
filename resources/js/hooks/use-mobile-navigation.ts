import { useCallback } from 'react';

export type CleanupFn = () => void;

export function useMobileNavigation(): CleanupFn {
    return useCallback(() => {
        // Rétablir les clics après la navigation.
        document.body.style.removeProperty('pointer-events');
    }, []);
}
