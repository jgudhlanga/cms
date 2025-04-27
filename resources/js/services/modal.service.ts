import { useModalStore } from '@/store/core/useModalStore';

export type IModalParams = {
	name: string,
	edit?: any,
};

export interface IModal {
	open: (params: IModalParams) => void;
	close: (name: string) => void;
	getEdit: (name: string) => any;
	isOpen: (name: string) => boolean;
}

class ModalService implements IModal {
	isOpen (name: string): boolean {
		const {isOpen} = useModalStore();
		return isOpen(name) ?? false;
	}

	open(params: IModalParams) {
		const { openModal } = useModalStore();
		openModal(params.name, params.edit);
	}

	close(name: string): void {
		const { closeModal } = useModalStore();
		closeModal(name);
	}

	getEdit(name: string): any {
		const { getEdit } = useModalStore();
		return getEdit(name);
	}
}


export default new ModalService();
