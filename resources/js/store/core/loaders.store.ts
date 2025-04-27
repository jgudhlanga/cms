import { defineStore } from 'pinia';

export  type ILoaderStore = {
	formProcessing: boolean,
}

export const useLoadersStore = defineStore('loaders', {
	state: (): ILoaderStore => {
		return {
			formProcessing: false
		};
	},
	persist: true
});
