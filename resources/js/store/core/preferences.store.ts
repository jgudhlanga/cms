import { defineStore } from 'pinia';

export  type IPreferenceStore = {
	locale: string | null,
	sidebarCollapsed: boolean,
	sideBarState: boolean,
	hydratedFromServer: boolean,
	preferenceId: number | null,
}

export const usePreferencesStore = defineStore('preferences', {
	state: (): IPreferenceStore => {
		return {
			locale: 'en',
			sidebarCollapsed: false,
			sideBarState: false,
			hydratedFromServer: false,
			preferenceId: null,
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
	// Only display settings survive a reload. The preference id and hydration flag belong to the signed-in
	// user and must not carry over to the next person on a shared computer.
	persist: {
		pick: ['locale', 'sideBarState', 'sidebarCollapsed'],
	},
});
