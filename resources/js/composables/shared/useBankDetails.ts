import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { BankDetail } from '@/types/shared';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { ZodObject } from 'zod';
import { useBankDetailFormStore } from '@/store/shared/useBankDetailsFormStore';

export const useBankDetails = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore, checkStatusIcon } = useDataTables();
	const createBankDetailColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.bank', 1), accessorKey: 'attributes.bank' },
			{ header: trans_choice('trans.branch', 1), accessorKey: 'attributes.bankBranch' },
			{ header: trans_choice('trans.branch_code', 1), accessorKey: 'attributes.bankBranchCode' },
			{ header: trans_choice('trans.account_type', 1), accessorKey: 'attributes.bankAccountType' },
			{ header: trans('trans.account_holder_name'), accessorKey: 'attributes.bankAccountHolder' },
			{ header: trans_choice('trans.account_number', 1), accessorKey: 'attributes.bankAccountNumber' },
			{
				header: trans('trans.main'),
				accessorKey: 'isMain',
				meta: { align: 'center' },
				cell: ({ row }: {
					row: { original: BankDetail }
				}) => checkStatusIcon(row.original?.attributes?.bankAccountIsMain)
			},
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: BankDetail } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans('trans.bank_details');
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:bank-details'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:bank-details'], route('bank-details.destroy', id), name)
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:bank-details'], route('bank-details.restore', id), name)
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:bank-details'], route('bank-details.force-delete', id), name)
						}
					]);
				}
			}
		];
	};

	const onOpenModal = (can: boolean, bankDetail?: BankDetail) => {
		if(bankDetail) {
			bankDetail.attributes.bankAccountNumber = bankDetail?.attributes?.bankAccountNumberDecrypted
		}
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.bank_details, edit: bankDetail });
	};

	const successMessage = () =>  trans('trans.item_saved', { item: trans('trans.bank_details') });
	const errorMessage = () =>  trans('trans.item_save_failure', { item: trans('trans.bank_details') });
	const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

	function validateForm(form: any) {
		mergeValidationSchema(schemaFields)(
			['bankAccountHolderSchema', 'bankAccountNumberSchema', 'bankAccountTypeSchema', 'bankBranchSchema'],
			schemaFields['bankSchema']()
		).parse(form);
	}

	const updateBankDetails = (form: InertiaForm<any>, bankDetail?: BankDetail) => {
		try {
			validateForm(form);
			const id = getIdParams(bankDetail?.id?.toString() ?? '');
			form.put(route('bank-details.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.bank_details));
			clearBankDetailFormStore();
		} catch (error: any) {
			form.setError(error.format());
		}
	};

	function clearBankDetailFormStore() {
		const store = useBankDetailFormStore();
		store.$reset();
		store.$dispose();
	}

	const createBankDetails = (form: InertiaForm<any>, postUrl: string) => {
		try {
			validateForm(form);
			form.post(postUrl, buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.bank_details));
			clearBankDetailFormStore();
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	return {
		createBankDetailColumns,
		onOpenModal,
		updateBankDetails,
		createBankDetails
	};
};
