import { useEffect, useRef, useState } from 'react';
import { useTranslation } from '@/i18n/LocaleProvider';
import { useHints } from '@/Components/Help/HintsProvider';

/**
 * The in-body help badge ("?").
 *
 * A small, themed button that opens a short explanation popover. It renders
 * FROM the content flow (inline-flex) so it can never overlap the sidebar, and
 * hides itself entirely when the user has switched hints off.
 *
 * Pass `hintKey` for a dotted translation key (hints.dashboard_pool) or `text`
 * for a raw string (already translated). The popover is anchored below the
 * badge and clamped so it never runs off-screen.
 */
export default function HelpHint({ hintKey = null, text = null, title = null, className = '' }) {
    const { t } = useTranslation();
    const { hidden } = useHints();
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    // Close on outside click / Escape.
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

    if (hidden) return null;

    const body = text ?? (hintKey ? t(hintKey) : '');

    return (
        <span ref={ref} className={`relative inline-flex align-middle ${className}`}>
            <button
                type="button"
                data-testid="help-hint"
                aria-label={title || body}
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
                className="ml-1.5 inline-flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-[10px] font-bold leading-none text-slate-500 transition-colors hover:border-[var(--accent)] hover:text-[var(--accent)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent-ring)]"
            >
                ?
            </button>

            {open && (
                <span
                    role="tooltip"
                    data-testid="help-hint-tooltip"
                    className="absolute left-0 top-full z-50 mt-2 w-56 max-w-[80vw] rounded-lg border border-slate-200 bg-white p-2.5 text-left text-xs font-normal leading-relaxed text-slate-600 shadow-lg sm:w-64"
                    style={{ backgroundColor: 'var(--surface)', color: 'var(--text-secondary)' }}
                >
                    {body}
                </span>
            )}
        </span>
    );
}
