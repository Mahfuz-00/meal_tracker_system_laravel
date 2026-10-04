import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * THE GLOBAL HINT SWITCH.
 *
 * One context answers the only question a hint badge asks - "are hints on?" -
 * so the preference applies platform-wide with no prop threading. The value
 * comes from the shared `hints` prop (the user's `user_settings` preference)
 * and is followed across visits via the router.
 *
 * Mounted ABOVE `<App>` in app.jsx, so it must not call usePage() - hence the
 * prop + router subscription, exactly like LocaleProvider.
 */
const HintsContext = createContext(null);

export function HintsProvider({ hints = null, children }) {
    const serverEnabled = hints?.enabled ?? true;
    const [enabled, setEnabled] = useState(serverEnabled);

    // Re-sync whenever the server sends a new value.
    useEffect(() => {
        setEnabled(serverEnabled);
    }, [serverEnabled]);

    // Follow every successful visit: the Settings toggle redirects back with the
    // new `hints.enabled`, and this applies it without a manual reload.
    useEffect(() => {
        const off = router.on('success', (event) => {
            const next = event?.detail?.page?.props?.hints?.enabled;
            if (typeof next === 'boolean') setEnabled(next);
        });

        return () => {
            if (typeof off === 'function') off();
        };
    }, []);

    const value = useMemo(() => ({ enabled, hidden: !enabled }), [enabled]);

    return <HintsContext.Provider value={value}>{children}</HintsContext.Provider>;
}

/** Read the global hint preference. Hints are ON when no provider is mounted. */
export function useHints() {
    return useContext(HintsContext) ?? { enabled: true, hidden: false };
}

export default HintsProvider;
