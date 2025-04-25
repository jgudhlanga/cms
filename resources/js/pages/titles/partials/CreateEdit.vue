<script setup lang="ts">
import Name from '@/components/core/form/text/Name.vue';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { Title, TitleParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useTitles } from '@/composables/titles/useTitles';

const title = ref<Title>();
const form = useForm<TitleParams>({
	name: '',
});


const { saveTitle } = useTitles();

const { modals } = useModalStore();

watch(modals!, () => {
	title.value = getModalEdit(APP_MODULE_KEYS.titles);
	form.name = title.value?.attributes?.name ?? '';
	form.defaults();
});
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.titles"
		:title="`${title ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.title', 1)}`"
		:on-form-action="() => saveTitle(form, title)"
		:form="form"
	>
		<template #body>
			<Name
				:inputAutoFocus="true"
				v-model="form.name"
				@input="clearFormErrors(form, 'name')"
				:error="form.errors.name"
			/>
		</template>
	</BaseModal>
</template>
