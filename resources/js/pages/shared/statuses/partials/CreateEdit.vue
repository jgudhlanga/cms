<script setup lang="ts">
import Title from '@/components/core/form/text/Title.vue';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { Status, StatusParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useStatuses } from '@/composables/shared/useStatuses';

const status = ref<Status>();
const form = useForm<StatusParams>({
	title: '',
});


const { saveStatus } = useStatuses();

const { modals } = useModalStore();

watch(modals!, () => {
	status.value = getModalEdit(APP_MODULE_KEYS.statuses);
	form.title = status.value?.attributes?.title ?? '';
	form.defaults();
});
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.statuses"
		:title="`${status ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.status', 1)}`"
		:on-form-action="() => saveStatus(form, status)"
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
