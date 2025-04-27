import DialogService from '@/services/dialog.service';
import ModalService, { IModalParams } from '@/services/modal.service';
import ToastService from '@/services/toast.service';
import { trans } from 'laravel-vue-i18n';

const openModal = (params: IModalParams) => {
	ModalService.open(params);
};

const closeModal = (name: string) => {
	ModalService.close(name);
};

const isModalOpen = (name: string) => {
	return ModalService.isOpen(name);
};

const getModalEdit = (name: string): any => {
	return ModalService.getEdit(name);
};
const successAlert = (message: string) => {
	ToastService.success(message);
};

const errorAlert = (message: string) => {
	ToastService.error(message);
};

const warningAlert = (message: string) => {
	ToastService.warning(message);
};

const infoAlert = (message: string) => {
	ToastService.info(message);
};

const forbiddenAlert = () => {
	ToastService.warning(trans('trans.forbidden_message'));
};

const infoDialog = (message: string, title: string) => {
	DialogService.info(message, title);
};

const successDialog = (message: string, title: string) => {
	DialogService.success(message, title);
};
const dangerDialog = (onConfirm: () => boolean, message?: string, title?: string) => {
	DialogService.danger(onConfirm, message ?? trans('trans.delete_action_description'), title ?? trans('trans.are_you_sure'));
};

const warningDialog = (onConfirm: () => boolean, message?: string, title?: string) => {
	DialogService.warning(onConfirm, trans('trans.archive_action_description'), title ?? trans('trans.are_you_sure'));
};

export {
	closeModal,
	openModal,
	isModalOpen,
	dangerDialog,
	errorAlert,
	forbiddenAlert,
	getModalEdit,
	infoAlert,
	infoDialog,
	successAlert,
	successDialog,
	warningAlert,
	warningDialog,
};
