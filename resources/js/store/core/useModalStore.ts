import { defineStore } from 'pinia';

type ModalItem = {
	opened?: boolean,
	edit?: any,
}

interface IModalStore {
	modals?: Record<string, ModalItem>;
}

export const useModalStore = defineStore('modal', {
	state: (): IModalStore => ({
		modals: {}
	}),
	actions: {
		openModal(name: string, edit?: any) {
			this.modals![name] = { opened: true, edit: edit };
		},
		closeModal(name: string) {
			delete this.modals![name];
		},
		isOpen(name: string) {
			return this.modals![name]?.opened;
		},
		getEdit(name: string) {
			return this.modals![name]?.edit;
		}
	}
});
