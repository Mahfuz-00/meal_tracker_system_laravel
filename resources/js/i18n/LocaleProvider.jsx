import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * FRONTEND i18n.
 *
 * The translation catalogue already exists server-side (`lang/<code>/*.php`)
 * and is shared to every page as the `locale` Inertia prop, so there is a single
 * source of truth for wording on both sides of the wire.
 *
 * WHY IT TAKES A PROP AND DOES NOT CALL usePage()
 * -----------------------------------------------
 * This provider is mounted ABOVE Inertia's `<App>` in app.jsx so it can wrap the
 * whole tree (including the onboarding/loading siblings). Inertia's context is
 * provided BY `<App>`, so calling usePage() here would throw and take the tree
 * down. Instead the initial catalogue arrives as a prop, and later visits are
 * followed via the global router - plain JS that works anywhere.
 *
 * LOOKUP ORDER
 *   dotted key (nav.dashboard)  ->  phrase book ("Save")  ->  fallback  ->  key
 * The phrase-book step lets a page-body literal be translated by passing the
 * ENGLISH STRING as the key, so not every sentence needs an invented key.
 */
const LocaleContext = createContext(null);

export function LocaleProvider({ locale, children }) {
    const [payload, setPayload] = useState(locale);

    // Follow every successful Inertia visit so a language change repaints the
    // whole tree (including components outside <App>) with no reload.
    useEffect(() => {
        setPayload(locale);
    }, [locale]);

    useEffect(() => {
        const off = router.on('success', (event) => {
            const next = event?.detail?.page?.props?.locale;
            if (next) setPayload(next);
        });

        return () => {
            if (typeof off === 'function') off();
        };
    }, []);

    const current = payload?.current ?? 'en';
    const messages = payload?.messages ?? {};
    const phrases = payload?.phrases ?? {};
    const supported = payload?.supported ?? [];
    const rtl = Boolean(payload?.rtl);

    // Keep the document language/direction in step (drives screen readers and
    // native controls). Bengali is LTR today, but the config supports RTL.
    useEffect(() => {
        if (typeof document === 'undefined') return;
        document.documentElement.setAttribute('lang', current);
        document.documentElement.setAttribute('dir', rtl ? 'rtl' : 'ltr');
    }, [current, rtl]);

    /**
     * Translate a dotted key or an English phrase.
     * `replacements` interpolates `:name` placeholders (matching Laravel's
     * `__('...', ['name' => ...])` syntax).
     */
    const t = useCallback(
        (key, replacements = null, fallback = null) => {
            if (key === undefined || key === null) return fallback ?? '';

            let value = messages[key];

            if (value === undefined) value = phrases[key];

            if (value === undefined || value === null) {
                return fallback ?? key;
            }

            if (replacements && typeof value === 'string') {
                Object.entries(replacements).forEach(([name, replacement]) => {
                    value = value.split(`:${name}`).join(String(replacement));
                });
            }

            return value;
        },
        [messages, phrases]
    );

    const value = useMemo(
        () => ({ t, locale: current, rtl, supported, isCurrent: (code) => code === current }),
        [t, current, rtl, supported]
    );

    return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>;
}

/**
 * The translation hook. Degrades to English (returns the key) outside the
 * provider rather than crashing, so a component can be unit-rendered in
 * isolation.
 */
export function useTranslation() {
    const ctx = useContext(LocaleContext);

    return (
        ctx || {
            t: (key, _replacements, fallback) => fallback ?? key,
            locale: 'en',
            rtl: false,
            supported: [],
            isCurrent: () => false,
        }
    );
}

export default useTranslation;
