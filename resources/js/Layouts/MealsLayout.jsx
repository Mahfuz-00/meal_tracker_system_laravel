import React, { useContext } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ShellContext from '@/Layouts/shellContext';
import { Link } from '@inertiajs/react';
import useCan from '@/Utils/can';
import useTerminology from '@/Utils/useTerminology';
import { useTranslation } from '@/i18n/LocaleProvider';

/**
 * Shared chrome for every Meal Management screen: page header + module tabs.
 * Tabs are permission-filtered and term-aware.
 *
 * When rendered INSIDE the persistent shell it emits ONLY its chrome (the shell
 * already exists); otherwise it wraps AuthenticatedLayout itself, preserving the
 * behaviour of pages that have not yet been migrated.
 */
const TABS = [
    { label: 'Members', termKey: 'members', route: 'meals.students.index', match: 'meals.students.*', permission: 'students.view' },
    { label: 'Groups', termKey: 'departments', route: 'meals.departments.index', match: 'meals.departments.*', permission: 'departments.view' },
    { label: 'Deposits', termKey: 'deposits', route: 'meals.deposits.index', match: 'meals.deposits.*', permission: 'meals.deposit' },
    { label: 'Subsidies', route: 'meals.subsidies.index', match: 'meals.subsidies.*', permission: 'subsidies.view' },
    { label: 'Meal Entries', route: 'meals.entries.index', match: 'meals.entries.*', permission: 'meals.entry' },
    { label: 'Expenses', route: 'meals.expenses.index', match: 'meals.expenses.*', permission: 'meals.expense' },
    { label: 'Reports', route: 'meals.reports.index', match: 'meals.reports.*', permission: 'meals.reports' },
];

export default function MealsLayout({ title, description, actions, children }) {
    const { can } = useCan();
    const { t: term } = useTerminology();
    const { t } = useTranslation();
    const inShell = useContext(ShellContext);

    const visibleTabs = TABS.filter((tab) => can(tab.permission));
    const tabLabel = (tab) => (tab.termKey ? term(tab.termKey, tab.label) : t(tab.label));

    const chrome = (
        <div className="space-y-5">
            {/* Module tabs */}
            {visibleTabs.length > 1 && (
                <nav
                    aria-label={t('Meal management sections')}
                    className="flex flex-wrap gap-1.5 overflow-x-auto border-b border-slate-200"
                >
                    {visibleTabs.map((tab) => {
                        const active = route().current(tab.match);

                        return (
                            <Link
                                key={tab.route}
                                href={route(tab.route)}
                                aria-current={active ? 'page' : undefined}
                                className={`-mb-px whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-semibold transition-colors sm:px-3.5 ${active
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'
                                    }`}
                            >
                                {tabLabel(tab)}
                            </Link>
                        );
                    })}
                </nav>
            )}

            {/* Page header */}
            {(title || actions) && (
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 className="text-lg font-bold text-slate-900">{title}</h3>
                        {description && (
                            <p className="mt-0.5 text-sm text-slate-500">{description}</p>
                        )}
                    </div>
                    {actions && <div className="flex items-center gap-2">{actions}</div>}
                </div>
            )}

            {children}
        </div>
    );

    if (inShell) {
        return chrome;
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-semibold text-xl text-slate-800 leading-tight">
                    {title || t('Meal Management')}
                </h2>
            }
        >
            {chrome}
        </AuthenticatedLayout>
    );
}
