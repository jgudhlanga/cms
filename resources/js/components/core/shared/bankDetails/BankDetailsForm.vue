<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useBankDetails } from '@/composables/shared/useBankDetails';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { BankDetail, BankDetailParams } from '@/types/shared';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BankAccountNumber from '@/components/core/form/text/BankAccountNumber.vue';
import BankBranchCode from '@/components/core/form/text/BankBranchCode.vue';
import BankComboSelect from '@/components/core/form/combobox/BankComboSelect.vue';
import BankAccountHolder from '@/components/core/form/text/BankAccountHolder.vue';
import BankBranchComboSelect from '@/components/core/form/combobox/BankBranchComboSelect.vue';
import BankAccountTypeComboSelect from '@/components/core/form/combobox/BankAccountTypeComboSelect.vue';
import { storeToRefs } from 'pinia';
import { useBankDetailFormStore } from '@/store/shared/useBankDetailsFormStore';
import BaseCheckbox from '../../form/radio/BaseCheckbox.vue';

interface Props {
	postUrl: string;
}

const props = defineProps<Props>();

const bankDetails = ref<BankDetail>();
const {
	bankAccountType,
	bankBranchCode,
	bankBranch,
	bank,
	bank_account_holder,
	bank_account_is_main,
	bank_account_number,
	bank_id,
	bank_branch_id,
	bank_account_type_id
} = storeToRefs(useBankDetailFormStore());
const form = useForm<BankDetailParams>({
	bank: null,
	bankAccountType: null,
	bankBranch: null,
	bankBranchCode: '',
	bank_account_holder: '',
	bank_account_is_main: false,
	bank_account_number: '',
	bank_account_type_id: '',
	bank_branch_id: '',
	bank_id: ''
});

const { updateBankDetails, createBankDetails } = useBankDetails();

const { modals } = useModalStore();
const { isItTrue } = useUtils();
const triggerBranchSearch = ref(true);
const updateBankBranchMeta = ref(true);

watch(modals!, () => {
	bankDetails.value = getModalEdit(APP_MODULE_KEYS.bank_details);
	updateBankBranchMeta.value = false;
	bankAccountType.value = {
		value: bankDetails.value?.attributes?.bankAccountTypeId ?? '',
		label: bankDetails.value?.attributes?.bankAccountType ?? ''
	};
	bankBranchCode.value = bankDetails.value?.attributes?.bankBranchCode ?? '';
	bankBranch.value = {
		value: bankDetails.value?.attributes?.bankBranchId ?? '',
		label: bankDetails.value?.attributes?.bankBranch ?? ''
	};
	bank.value = {
		value: bankDetails.value?.attributes?.bankId ?? '',
		label: bankDetails.value?.attributes?.bank ?? ''
	};
	bank_account_holder.value = bankDetails.value?.attributes?.bankAccountHolder ?? '';
	bank_account_is_main.value = isItTrue(bankDetails.value?.attributes?.bankAccountIsMain) ?? false;
	bank_account_number.value = bankDetails.value?.attributes?.bankAccountNumber ?? '';
	bank_account_type_id.value = bankDetails.value?.attributes?.bankAccountTypeId ?? '';
	bank_branch_id.value = bankDetails.value?.attributes?.bankAccountNumber ?? '';
	bank_id.value = bankDetails.value?.attributes?.bankId ?? '';
	form.defaults();
});
watch(bank, (newBank) => {
	if (updateBankBranchMeta.value) {
		bankBranchCode.value = newBank?.auxLabel ?? '';
		bankBranch.value = { value: newBank?.relationshipOneValue ?? '', label: newBank?.relationshipOneLabel ?? '' };
	}
	updateBankBranchMeta.value = true;
});

const updateForm = () => {
	triggerBranchSearch.value = false;
	form.bankAccountType = bankAccountType?.value ?? null;
	form.bankBranchCode = bankBranchCode?.value ?? '';
	form.bankBranch = bankBranch?.value ?? null;
	form.bank = bank?.value ?? null;
	form.bank_account_holder = bank_account_holder?.value ?? '';
	form.bank_account_is_main = bank_account_is_main?.value ?? false;
	form.bank_account_number = bank_account_number?.value ?? 'd;';
	form.bank_account_type_id = bankAccountType?.value?.value ?? '';
	form.bank_branch_id = bankBranch?.value?.value ?? '';
	form.bank_id = bank?.value?.value ?? '';
};

const save = () => {
	updateForm();
	if (Number(bankDetails.value?.id?.toString()) > 0) {
		updateBankDetails(form, bankDetails.value);
	} else {
		createBankDetails(form, props.postUrl);
	}
};
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.bank_details"
		:title="`${bankDetails ? $t('trans.update') : $t('trans.create')} ${$t('trans.bank_details')}`"
		:on-form-action="() => save()"
		:form="form"
	>
		<template #body>
			<div class="grid grid-cols-2 gap-3">
				<BankAccountHolder
					:inputAutoFocus="true"
					v-model="bank_account_holder"
					@input="clearFormErrors(form, 'bank_account_holder')"
					:error="form.errors.bank_account_holder"
				/>
				<BankComboSelect :form="form" v-model="bank" :error="form.errors.bank" />
				<BankBranchComboSelect
					:bank-id="bank?.value.toString() ?? ''"
					:trigger-search="triggerBranchSearch"
					:form="form"
					v-model="bankBranch"
					:error="form.errors.bankBranch" />
				<BankBranchCode
					v-model="bankBranchCode"
					@input="clearFormErrors(form, 'bank_branch_code')" />
				<BankAccountNumber
					v-model="bank_account_number"
					@input="clearFormErrors(form, 'bank_account_number')"
					:error="form.errors.bank_account_number"
				/>
				<BankAccountTypeComboSelect
					:form="form" v-model="bankAccountType" :error="form.errors.bankAccountType" />
				<BaseCheckbox
					input-id="bank_account_is_main"
					v-model="bank_account_is_main"
					:label="$t('trans.main')" />
			</div>
		</template>
	</BaseModal>
</template>
