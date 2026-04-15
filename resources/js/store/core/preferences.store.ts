import { defineStore } from 'pinia';

export  type IPreferenceStore = {
	locale: string | null,
	sidebarCollapsed: boolean,
	sideBarState: boolean,
	hydratedFromServer: boolean,
	preferenceId: number | null,
	theme: string | null
}

export const usePreferencesStore = defineStore('preferences', {
	state: (): IPreferenceStore => {
		return {
			locale: null,
			sidebarCollapsed: false,
			sideBarState: false,
			hydratedFromServer: false,
			preferenceId: null,
			theme: localStorage.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
		};
	},
	actions: {
		setSideBarState(value: boolean): void {
			this.sideBarState = value;
			this.sidebarCollapsed = !value;
		},
		hydrateSidebarPreference(value: boolean, preferenceId: number | null): void {
			this.setSideBarState(value);
			this.preferenceId = preferenceId;
			this.hydratedFromServer = true;
		},
		markHydrated(): void {
			this.hydratedFromServer = true;
		},
	},
	persist: true
});
