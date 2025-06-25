<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useContacts } from '@/composables/shared/useContacts';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { Contact, ContactParams } from '@/types/shared';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';
import { BaseCheckbox } from '@/components/core/form';
import Name from '@/components/core/form/text/Name.vue';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import PhoneNumber from '@/components/core/form/text/PhoneNumber.vue';
import AltEmailAddress from '@/components/core/form/text/AltEmailAddress.vue';
import AltPhoneNumber from '@/components/core/form/text/AltPhoneNumber.vue';
import { useUtils } from '@/composables/core/useUtils';

interface Props {
	postUrl: string;
}

const props = defineProps<Props>();

const {isItTrue} = useUtils();
const contact = ref<Contact>();
const form = useForm<ContactParams>({
	name: '',
	phone_number: '',
	alt_phone_number: '',
	email_address: '',
	alt_email_address: '',
	contact_is_main: false
});

const { updateContact, createContact } = useContacts();

const { modals } = useModalStore();

watch(modals!, () => {
	contact.value = getModalEdit(APP_MODULE_KEYS.contacts);
		form.name = contact.value?.attributes?.name ?? '';
		form.phone_number = contact.value?.attributes?.phoneNumber ?? '';
		form.alt_phone_number = contact.value?.attributes?.altPhoneNumber ?? '';
		form.email_address = contact.value?.attributes?.emailAddress ?? '';
		form.alt_email_address = contact.value?.attributes?.altEmailAddress ?? '';
		form.contact_is_main = isItTrue(contact.value?.attributes?.contactIsMain) ?? false;
	form.defaults();
});



const save = () => {
	if (Number(contact.value?.id?.toString()) > 0) {
		updateContact(form, contact.value);
	} else {
		createContact(form, props.postUrl);
	}
};
</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.contacts"
		:title="`${contact ? $t('trans.update') : $t('trans.create')} ${$tChoice('trans.contact', 1)}`"
		:on-form-action="() => save()"
		:form="form"
	>
		<template #body>
			<div class="grid grid-cols-2 gap-3">
				<Name
					v-model="form.name"
					@input="clearFormErrors(form, 'name')" :error="form.errors.name" />
				<PhoneNumber
					v-model="form.phone_number" @input="clearFormErrors(form, 'phone_number')"
					:error="form.errors.phone_number" />
				<AltPhoneNumber
					v-model="form.alt_phone_number" @input="clearFormErrors(form, 'alt_phone_number')"
					:error="form.errors.alt_phone_number" />
				<EmailAddress
					v-model="form.email_address" @input="clearFormErrors(form, 'email_address')"
					:error="form.errors.email_address" />
				<AltEmailAddress
					v-model="form.alt_email_address" @input="clearFormErrors(form, 'alt_email_address')"
					:error="form.errors.alt_email_address" />
				<BaseCheckbox
					input-id="contact_is_main"
					v-model="form.contact_is_main"
					:label="$t('trans.main')" />
			</div>
		</template>
	</BaseModal>
</template>
