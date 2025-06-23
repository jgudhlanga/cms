<script setup lang="ts">
import Name from '@/components/core/form/text/Name.vue';
import { useCountries } from '@/composables/countries/useCountries';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { Country, CountryParams } from '@/types/countries';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import BaseModal from '@/components/core/modal/BaseModal.vue';

const country = ref<Country>();
const form = useForm<CountryParams>({
	name: '',
	flag: ''
});


const { saveCountry } = useCountries();

const { modals } = useModalStore();

watch(modals!, () => {
	country.value = getModalEdit(APP_MODULE_KEYS.countries);
	form.name = country.value?.attributes?.name ?? '';
	form.defaults();
});
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.countries"
		:title="`${country ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.country', 1)}`"
		:on-form-action="() => saveCountry(form, country)"
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
