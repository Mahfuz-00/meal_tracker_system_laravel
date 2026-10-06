import React from 'react';

/**
 * The login introduction-panel animation.
 *
 * A smooth, soft, looping vector scene built from plain SVG + CSS keyframes - no
 * Lottie runtime, no Framer Motion, no extra dependency. It mimics the gentle,
 * continuous "Lottie" feel: everything eases in and out, nothing snaps, and the
 * whole loop runs forever.
 *
 * Motion is:
 *   - compositor-friendly (only `transform`, `opacity` and `stroke-dashoffset`),
 *   - fully disabled under `prefers-reduced-motion`,
 *   - decorative only (`aria-hidden`), so it never blocks interaction.
 *
 * Class names are prefixed `lh-` and the keyframes are scoped inside this
 * component's <style>, so it cannot collide with anything else.
 */
export default function LoginHeroAnimation() {
    return (
        <div className="lh-hero relative w-full max-w-md" aria-hidden="true">
            <style>{`
                .lh-hero svg { width: 100%; height: auto; display: block; overflow: visible; }
                @keyframes lh-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
                @keyframes lh-float-slow { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(8px); } }
                @keyframes lh-bar { 0%, 100% { transform: scaleY(0.62); } 50% { transform: scaleY(1); } }
                @keyframes lh-draw { 0% { stroke-dashoffset: 340; } 55%, 100% { stroke-dashoffset: 0; } }
                @keyframes lh-pulse { 0%, 100% { transform: scale(1); opacity: 0.9; } 50% { transform: scale(1.08); opacity: 1; } }
                @keyframes lh-orbit { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                @keyframes lh-sheen { 0% { transform: translateX(-40%); opacity: 0; } 45% { opacity: 0.4; } 100% { transform: translateX(140%); opacity: 0; } }
                @keyframes lh-drift { 0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; } 50% { transform: translate(6px, -6px) scale(1.06); opacity: 0.72; } }
                .lh-float { animation: lh-float 4.6s ease-in-out infinite; }
                .lh-float-slow { animation: lh-float-slow 6.2s ease-in-out infinite; }
                .lh-bar { transform-box: fill-box; transform-origin: bottom center; animation: lh-bar 3.2s ease-in-out infinite; }
                .lh-bar-2 { animation-delay: 0.35s; }
                .lh-bar-3 { animation-delay: 0.7s; }
                .lh-line { stroke-dasharray: 340; animation: lh-draw 5.2s ease-in-out infinite; }
                .lh-pulse { transform-box: fill-box; transform-origin: center; animation: lh-pulse 3.6s ease-in-out infinite; }
                .lh-orbit { transform-box: fill-box; transform-origin: 210px 200px; animation: lh-orbit 16s linear infinite; }
                .lh-sheen { animation: lh-sheen 6s ease-in-out infinite; }
                .lh-drift { transform-box: fill-box; transform-origin: center; animation: lh-drift 9s ease-in-out infinite; }
                .lh-drift-2 { animation-delay: 1.8s; }
                @media (prefers-reduced-motion: reduce) {
                    .lh-float, .lh-float-slow, .lh-bar, .lh-line, .lh-pulse, .lh-orbit, .lh-sheen, .lh-drift { animation: none !important; }
                }
            `}</style>

            <svg viewBox="0 0 420 320" role="presentation">
                <defs>
                    <linearGradient id="lh-accent" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stopColor="#6366f1" />
                        <stop offset="100%" stopColor="#8b5cf6" />
                    </linearGradient>
                    <linearGradient id="lh-accent-2" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#818cf8" />
                        <stop offset="100%" stopColor="#6366f1" />
                    </linearGradient>
                    <linearGradient id="lh-card" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#ffffff" />
                        <stop offset="100%" stopColor="#f8fafc" />
                    </linearGradient>
                    <linearGradient id="lh-green" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stopColor="#34d399" />
                        <stop offset="100%" stopColor="#10b981" />
                    </linearGradient>
                </defs>

                {/* Soft drifting orbs behind the card */}
                <circle className="lh-drift" cx="70" cy="70" r="46" fill="#c7d2fe" opacity="0.5" />
                <circle className="lh-drift lh-drift-2" cx="366" cy="250" r="52" fill="#bae6fd" opacity="0.45" />

                {/* The dashboard card */}
                <g className="lh-float">
                    <rect x="52" y="62" width="316" height="196" rx="22" fill="url(#lh-card)" stroke="#e2e8f0" strokeWidth="1.5" />
                    <rect x="74" y="84" width="118" height="10" rx="5" fill="#e2e8f0" />
                    <rect x="74" y="102" width="72" height="8" rx="4" fill="#eef2f7" />

                    {/* Bars */}
                    <rect className="lh-bar" x="92" y="150" width="26" height="82" rx="7" fill="url(#lh-accent-2)" />
                    <rect className="lh-bar lh-bar-2" x="132" y="126" width="26" height="106" rx="7" fill="url(#lh-accent)" />
                    <rect className="lh-bar lh-bar-3" x="172" y="164" width="26" height="68" rx="7" fill="#a5b4fc" />

                    {/* Trend line that draws itself, then holds */}
                    <path
                        className="lh-line"
                        d="M232 206 C258 176 280 196 302 150 S338 128 350 118"
                        fill="none"
                        stroke="url(#lh-accent)"
                        strokeWidth="3"
                        strokeLinecap="round"
                    />
                    <circle cx="302" cy="150" r="3.5" fill="#6366f1" />
                    <circle cx="350" cy="118" r="3.5" fill="#6366f1" />
                </g>

                {/* A drawn, pulsing success badge */}
                <g className="lh-pulse">
                    <circle cx="352" cy="92" r="21" fill="url(#lh-green)" />
                    <path d="M343 92 l6 6 l12 -12" fill="none" stroke="#ffffff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                </g>

                {/* A gently bobbing coin */}
                <g className="lh-float-slow">
                    <circle cx="92" cy="126" r="15" fill="#fbbf24" stroke="#f59e0b" strokeWidth="1.5" />
                    <circle cx="92" cy="126" r="7" fill="none" stroke="#fde68a" strokeWidth="2" />
                </g>

                {/* A slow orbiting dot for a touch of life */}
                <g className="lh-orbit">
                    <circle cx="210" cy="44" r="4.5" fill="#818cf8" />
                </g>

                {/* A soft sheen sweeping the card */}
                <rect className="lh-sheen" x="70" y="62" width="60" height="196" rx="22" fill="#ffffff" opacity="0" />
            </svg>
        </div>
    );
}
