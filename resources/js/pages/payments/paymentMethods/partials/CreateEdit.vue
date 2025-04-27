<script setup lang="ts">
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import Title from '@/components/core/form/text/Title.vue';
import { usePaymentMethods } from '@/composables/payments/usePaymentMethods';
import { PaymentMethod, PaymentMethodParams } from '@/types/payments';

const { savePaymentMethod } = usePaymentMethods();
const paymentMethod = ref<PaymentMethod>();
const form = useForm<PaymentMethodParams>({
	title: ''
});

const { modals } = useModalStore();

watch(modals!, () => {
	paymentMethod.value = getModalEdit(APP_MODULE_KEYS.payment_methods);
	form.title = paymentMethod.value?.attributes?.title ?? '';
	form.defaults();
});
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.payment_methods"
		:title="`${paymentMethod ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.payment_method', 1)}`"
		:on-form-action="() => savePaymentMethod(form, paymentMethod)"
		:form="form"
	>
		<template #body>
			<Title
				:inputAutoFocus="true"
				v-model="form.title"
				@input="clearFormErrors(form, 'title')"
				:error="form.errors.title"
			/>
		</template>
	</BaseModal>
</template>
