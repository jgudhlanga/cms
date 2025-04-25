import { defineStore } from 'pinia';

export  type IPreferenceStore = {
	locale: string | null,
	sidebarCollapsed: boolean,
	theme: string | null
}

export const usePreferencesStore = defineStore('preferences', {
	state: (): IPreferenceStore => {
		return {
			locale: null,
			sidebarCollapsed: false,
			theme: localStorage.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
		};
	},
	persist: true
});
