import { defineStore } from 'pinia';

export  type IDepartmentTabsStore = {
	activeTab: string,

}

export const useDepartmentTabsStore = defineStore('department-tabs', {
	state: (): IDepartmentTabsStore => {
		return {
			activeTab: 'about_us'
		};
	},
	persist: true
});
