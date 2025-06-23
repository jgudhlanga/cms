<script setup lang="ts">
import Title from '@/components/core/form/text/Title.vue';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { Gender, GenderParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useGenders } from '@/composables/genders/useGenders';

const gender = ref<Gender>();
const form = useForm<GenderParams>({
	title: '',
});


const { saveGender } = useGenders();

const { modals } = useModalStore();

watch(modals!, () => {
	gender.value = getModalEdit(APP_MODULE_KEYS.genders);
	form.title = gender.value?.attributes?.title ?? '';
	form.defaults();
});
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.genders"
		:title="`${gender ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.gender', 1)}`"
		:on-form-action="() => saveGender(form, gender)"
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
