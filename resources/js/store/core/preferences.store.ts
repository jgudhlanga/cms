import { defineStore } from 'pinia';

export  type IPreferenceStore = {
	locale: string | null,
	sidebarCollapsed: boolean,
	sideBarState: boolean,
	hydratedFromServer: boolean,
	/** True after the user toggles the sidebar this session; stops a late hydrate from undoing it. */
	sidebarStateTouched: boolean,
	preferenceId: number | null,
}

export const usePreferencesStore = defineStore('preferences', {
	state: (): IPreferenceStore => {
		return {
			locale: 'en',
			sidebarCollapsed: false,
			sideBarState: false,
			hydratedFromServer: false,
			sidebarStateTouched: false,
			preferenceId: null,
		};
	},
	actions: {
		setSideBarState(value: boolean, options: { fromServer?: boolean } = {}): void {
			this.sideBarState = value;
			this.sidebarCollapsed = !value;

			if (!options.fromServer) {
				this.sidebarStateTouched = true;
			}
		},
		setLocale(value: string | null): void {
			this.locale = value;
		},
		hydrateSidebarPreference(value: boolean, preferenceId: number | null, locale: string | null): void {
			// Keep an in-session toggle if hydrate returns after the user already changed the sidebar.
			if (!this.sidebarStateTouched) {
				this.setSideBarState(value, { fromServer: true });
			}

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
