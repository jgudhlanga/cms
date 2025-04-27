import BaseDialog from '@/components/core/modal/BaseDialog.vue';
import { TypeVariant } from '@/enums/type-variants';
import { useModal } from 'vue-final-modal';

export type IDialogParams = {
	type: TypeVariant
	message: string;
	onConfirm: () => boolean;
	title?: string;
};

export interface DialogInterface {
	danger: (onConfirm: () => boolean, message: string, title?: string) => void;
	info: (message: string, title?: string) => void;
	success: (message: string, title?: string) => void;
	warning: (onConfirm: () => boolean, message: string, title?: string) => void;
}

const dialogCreate = (params: IDialogParams) => {
	const { open, destroy } = useModal({
		defaultModelValue: false,
		keepAlive: false,
		component: BaseDialog,
		attrs: {
			type: params.type,
			title: params.title,
			message: params.message,
			onConfirm: async () => {
				if (params?.onConfirm) {
					if (params.onConfirm!()) {
						destroy();
					}
				}
			}
		},
		slots: {
			default: params.message
		}
	});
	open().then();
};

class DialogService implements DialogInterface {
	info(message: string, title?: string): void {
		dialogCreate({
			type: TypeVariant.info,
			message,
			title,
			onConfirm: () => true
		});
	}

	danger(onConfirm: () => boolean, message: string, title?: string): void {
		dialogCreate({
			type: TypeVariant.danger,
			message,
			title,
			onConfirm
		});
	}

	success(message: string, title?: string): void {
		dialogCreate({
			type: TypeVariant.success,
			message,
			title,
			onConfirm: () => true
		});
	}

	warning(onConfirm: () => boolean, message: string, title?: string): void {
		dialogCreate({
			type: TypeVariant.warning,
			message,
			title,
			onConfirm
		});
	}
}

export default new DialogService();
