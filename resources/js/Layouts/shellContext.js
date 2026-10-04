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

export default ShellContext;
