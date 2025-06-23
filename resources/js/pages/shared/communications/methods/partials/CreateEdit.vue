<script setup lang="ts">
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import Title from '@/components/core/form/text/Title.vue';
import { CommunicationMethod, CommunicationMethodParams } from '@/types/communications';
import { useCommunicationMethods } from '@/composables/shared/useCommunicationMethods';

const { saveCommunicationMethod } = useCommunicationMethods();
const communicationMethod = ref<CommunicationMethod>();
const form = useForm<CommunicationMethodParams>({
	title: ''
});

const { modals } = useModalStore();

watch(modals!, () => {
	communicationMethod.value = getModalEdit(APP_MODULE_KEYS.communication_methods);
	form.title = communicationMethod.value?.attributes?.title ?? '';
	form.defaults();
});
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.communication_methods"
		:title="`${communicationMethod ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.communication_mode', 1)}`"
		:on-form-action="() => saveCommunicationMethod(form, communicationMethod)"
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
