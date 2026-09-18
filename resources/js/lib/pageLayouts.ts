export type PageLayoutName = 'guest' | 'portal-registration' | 'plain' | 'app';

/**
 * The layout that wraps an Inertia page, chosen by page name (its path under resources/js/pages).
 */
export function layoutNameForPage(name: string): PageLayoutName {
    if (name.startsWith('auth/')) {
        return 'guest';
    }

    // Guest registration uses in-page toggles (brand header / guide); disable PublicShell fixed toggle.
    if (name.startsWith('portal/guest') || name.startsWith('portal/registration')) {
        return 'portal-registration';
    }

    if (name.startsWith('site/') || name.startsWith('portal/application') || name.startsWith('integrations/payments')) {
        return 'plain';
    }

    return 'app';
}
