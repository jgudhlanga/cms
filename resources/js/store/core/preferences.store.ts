import { defineStore } from 'pinia';

export type IPreferenceStore = {
    locale: string | null;
    sidebarCollapsed: boolean;
    sideBarState: boolean;
    hydratedFromServer: boolean;
    preferenceId: number | null;
    theme: string | null;
};

export const usePreferencesStore = defineStore('preferences', {
    state: (): IPreferenceStore => {
        return {
            locale: 'en',
            sidebarCollapsed: false,
            sideBarState: false,
            hydratedFromServer: false,
            preferenceId: null,
            theme: localStorage.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
        };
    },
    actions: {
        setSideBarState(value: boolean): void {
            this.sideBarState = value;
            this.sidebarCollapsed = !value;
        },
        setLocale(value: string | null): void {
            this.locale = value;
        },
        hydrateSidebarPreference(value: boolean, preferenceId: number | null, locale: string | null): void {
            this.setSideBarState(value);
            this.setLocale(locale);
            this.preferenceId = preferenceId;
            this.hydratedFromServer = true;
        },
        markHydrated(): void {
            this.hydratedFromServer = true;
        },
    },
    persist: true,
});
