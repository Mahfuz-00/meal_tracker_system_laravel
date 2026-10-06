import { createContext } from 'react';

/**
 * Is this subtree already rendered INSIDE the persistent app shell?
 *
 * `AuthenticatedLayout` provides `true` around its children. Nested layout
 * components (MealsLayout, SettingsLayout, and an AuthenticatedLayout a page
 * still renders itself) read this and, when already inside the shell, render
 * only their content chrome instead of wrapping a SECOND shell.
 *
 * This is what lets the sidebar stay mounted across navigations while pages can
 * still declare their own chrome: the outer shell is the single persistent
 * instance, and every inner "layout" collapses to content.
 */
const ShellContext = createContext(false);

/**
 * The DOM node inside the TopBar that a page's own header is portaled into.
 *
 * The shell renders the TopBar above the content; a page (or its layout) that
 * declares a `header` "publishes" it upward through this slot, so the page
 * context (title + subtitle) appears IN the top bar without the page having to
 * know where the top bar lives - and without a second shell being mounted.
 */
export const TopBarSlotContext = createContext(null);

export default ShellContext;
