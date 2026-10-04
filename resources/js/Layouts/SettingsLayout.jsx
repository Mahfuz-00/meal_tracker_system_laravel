import React, { useContext } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ShellContext from '@/Layouts/shellContext';
import { useTranslation } from '@/i18n/LocaleProvider';

/**
 * Settings content chrome. Renders INSIDE the persistent shell when one is
 * present (the common case), otherwise wraps AuthenticatedLayout for pages that
 * have not yet been migrated.
 */
export default function SettingsLayout({ children, title = null }) {
    const { t } = useTranslation();
    const inShell = useContext(ShellContext);

    const content = (
        <div className="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>{children}</div>
        </div>
    );

    if (inShell) {
        return content;
    }

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">{title || t('Settings')}</h2>}>
            {content}
        </AuthenticatedLayout>
    );
}
