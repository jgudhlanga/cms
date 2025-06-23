<script setup lang="ts">
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import Title from '@/components/core/form/text/Title.vue';
import { usePaymentDays } from '@/composables/shared/usePaymentDays';
import { PaymentDay, PaymentDayParams } from '@/types/payments';

const { savePaymentDay } = usePaymentDays();
const paymentDay = ref<PaymentDay>();
const form = useForm<PaymentDayParams>({
	title: ''
});

const { modals } = useModalStore();

watch(modals!, () => {
	paymentDay.value = getModalEdit(APP_MODULE_KEYS.payment_days);
	form.title = paymentDay.value?.attributes?.title ?? '';
	form.defaults();
});
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.payment_days"
		:title="`${paymentDay ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.payment_day', 1)}`"
		:on-form-action="() => savePaymentDay(form, paymentDay)"
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
