import { useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { NAV_SECTIONS } from '@/Utils/navItems';

/**
 * Resolve the ACTIVE module from the current route, for the Top Bar breadcrumb.
 *
 * It matches the current URL against the same route patterns the sidebar uses
 * (Ziggy `route().current()`), so the breadcrumb and the highlighted sidebar item
 * can never disagree. Returns the matching section + item (and child, for a
 * nested destination), or null when nothing matches.
 *
 *   { section, item, child }  |  null
 */
export default function useBreadcrumbs() {
    // Recompute on every navigation (Inertia updates `url`).
    const { url } = usePage();

    return useMemo(() => {
        const current = (pattern) => pattern && route().current(pattern);

        for (const section of NAV_SECTIONS) {
            for (const item of section.items || []) {
                if (Array.isArray(item.children)) {
                    const child = item.children.find((c) => current(c.match || c.route));
                    if (child) {
                        return { section, item, child };
                    }
                }

                if (current(item.match || item.route)) {
                    return { section, item, child: null };
                }
            }
        }

        return null;
    }, [url]);
}
