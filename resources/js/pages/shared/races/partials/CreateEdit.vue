<script setup lang="ts">
import Title from '@/components/core/form/text/Title.vue';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { Race, RaceParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useRaces } from '@/composables/shared/useRaces';

const race = ref<Race>();
const form = useForm<RaceParams>({
	title: '',
});


const { saveRace } = useRaces();

const { modals } = useModalStore();

watch(modals!, () => {
	race.value = getModalEdit(APP_MODULE_KEYS.races);
	form.title = race.value?.attributes?.title ?? '';
	form.defaults();
});
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.races"
		:title="`${race ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.race', 1)}`"
		:on-form-action="() => saveRace(form, race)"
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
