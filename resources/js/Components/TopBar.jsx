import { useEffect, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import NotificationBell from '@/Components/NotificationBell';
import usePlatformBranding from '@/Utils/usePlatformBranding';
import { useTranslation } from '@/i18n/LocaleProvider';

/**
 * The application TOP BAR.
 *
 * Sits above the main content body, in the main column only - so it never
 * affects the persistent sidebar. It carries, left to right:
 *   - the mobile drawer trigger (the docked sidebar has no trigger on desktop),
 *   - the page CONTEXT: the workspace/platform name, plus the current page's own
 *     header portaled in from the page (see TopBarSlotContext),
 *   - the notification bell and the user profile menu.
 *
 * It is rendered ONCE by the persistent shell, so it does not remount between
 * modules any more than the sidebar does.
 */
function UserMenu({ user }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return undefined;

        const onClick = (event) => {
            if (ref.current && !ref.current.contains(event.target)) setOpen(false);
        };
        const onKey = (event) => {
            if (event.key === 'Escape') setOpen(false);
        };

        document.addEventListener('mousedown', onClick);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    if (!user) return null;

    const initials = (user.name || 'U')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                data-testid="topbar-profile"
                aria-haspopup="menu"
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
                className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 transition-colors hover:bg-slate-50"
            >
                {user.avatar_url ? (
                    <img src={user.avatar_url} alt={user.name} className="h-7 w-7 rounded-lg object-cover" />
                ) : (
                    <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-[var(--accent)] text-[11px] font-bold text-white">
                        {initials}
                    </span>
                )}
                <span className="hidden text-left sm:block">
                    <span className="block max-w-[10rem] truncate text-xs font-bold text-slate-800">{user.name}</span>
                    <span className="block max-w-[10rem] truncate text-[10px] font-medium text-slate-400">
                        {user.designation || user.email}
                    </span>
                </span>
                <svg className="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <div
                    role="menu"
                    data-testid="topbar-profile-menu"
                    className="absolute right-0 top-12 z-50 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl animate-rise"
                >
                    <div className="border-b border-slate-100 px-4 py-2.5">
                        <p className="truncate text-sm font-bold text-slate-800">{user.name}</p>
                        <p className="truncate text-xs text-slate-400">{user.email}</p>
                    </div>
                    <Link
                        href={route('profile.edit')}
                        onClick={() => setOpen(false)}
                        className="block px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                    >
                        {t('Profile')}
                    </Link>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="block w-full px-4 py-2 text-left text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50"
                    >
                        {t('Log out')}
                    </Link>
                </div>
            )}
        </div>
    );
}

export default function TopBar({ slotRef = null, onMenuClick = null, menuOpen = false }) {
    const { institution, tenant, auth } = usePage().props;
    const { controlCenter } = usePlatformBranding();
    const { t } = useTranslation();

    const isSuper = !!auth?.user?.is_super_admin;
    const contextName = isSuper && !tenant?.switched ? controlCenter : (institution?.name || controlCenter);

    return (
        <div
            data-testid="app-topbar"
            className="sticky top-0 z-20 flex items-center gap-3 border-b px-4 py-2.5 backdrop-blur"
            style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--border-color)' }}
        >
            <button
                type="button"
                onClick={onMenuClick}
                aria-label={t('Open navigation')}
                aria-expanded={menuOpen}
                className="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition-colors hover:bg-slate-50 lg:hidden"
            >
                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            {/* Context: workspace name (overline) + the page's own header, portaled in. */}
            <div className="min-w-0 flex-1">
                <p className="truncate text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    {contextName}
                </p>
                <div ref={slotRef} data-testid="topbar-context" className="min-w-0" />
            </div>

            <div className="flex flex-shrink-0 items-center gap-2">
                <NotificationBell />
                <UserMenu user={auth?.user} />
            </div>
        </div>
    );
}
