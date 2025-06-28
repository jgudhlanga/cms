<script setup lang="ts">
import SponsorTypeComboSelect from '@/components/core/form/combobox/SponsorTypeComboSelect.vue';
import Address1 from '@/components/core/form/text/Address1.vue';
import Address2 from '@/components/core/form/text/Address2.vue';
import Address3 from '@/components/core/form/text/Address3.vue';
import Address4 from '@/components/core/form/text/Address4.vue';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import Name from '@/components/core/form/text/Name.vue';
import PhoneNumber from '@/components/core/form/text/PhoneNumber.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useSponsors } from '@/composables/students/useSponsors';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Sponsor, SponsorParams } from '@/types/students';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const sponsor = ref<Sponsor>();
const form = useForm<SponsorParams>({
    name: '',
    sponsor_type_id: null,
    sponsorType: null,
    phone_number: '',
    email: '',
    address_1: '',
    address_2: '',
    address_3: '',
    address_4: '',
});

const { updateSponsor, createSponsor } = useSponsors();

const { modals } = useModalStore();

watch(modals!, () => {
    sponsor.value = getModalEdit(APP_MODULE_KEYS.sponsors);
    form.name = sponsor.value?.attributes?.name ?? '';
    form.sponsor_type_id = sponsor.value?.attributes?.sponsorTypeId ?? null;
    form.sponsorType = {
        value: Number(sponsor.value?.attributes?.sponsorTypeId?.toString() ?? ''),
        label: sponsor.value?.attributes?.sponsorType ?? '',
    };
    form.phone_number = sponsor.value?.attributes?.phoneNumber ?? '';
    form.email = sponsor.value?.attributes?.email ?? '';
    form.address_1 = sponsor.value?.attributes?.address1 ?? '';
    form.address_2 = sponsor.value?.attributes?.address2 ?? '';
    form.address_3 = sponsor.value?.attributes?.address3 ?? '';
    form.address_4 = sponsor.value?.attributes?.address4 ?? '';
    form.defaults();
});

const save = () => {
    form.sponsor_type_id = form.sponsorType?.value ?? null;
    if (Number(sponsor.value?.id?.toString()) > 0) {
        updateSponsor(form, sponsor.value);
    } else {
        createSponsor(form);
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.sponsors"
        :title="`${sponsor ? $t('trans.update') : $t('trans.create')} ${$tChoice('trans.sponsor', 1)}`"
        :on-form-action="() => save()"
        :size="SizeVariant.lg"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-2 gap-3">
                <Name
                    v-model="form.name"
                    @input="clearFormErrors(form, 'name')"
                    :error="form.errors.name"
                    :is-required="true"
                />
                <SponsorTypeComboSelect
                    :form="form"
                    v-model="form.sponsorType"
                    :error="form.errors.sponsorType"
                />
                <PhoneNumber
                    v-model="form.phone_number"
                    @input="clearFormErrors(form, 'phone_number')"
                    :error="form.errors.phone_number"
                    :is-required="true"
                />
                <EmailAddress
                    v-model="form.email"
                    @input="clearFormErrors(form, 'email_address')"
                    :error="form.errors.email"
                />
                <Address1
                    v-model="form.address_1"
                    @input="clearFormErrors(form, 'address_1')"
                    :error="form.errors.address_1"
                />
                <Address2
                    v-model="form.address_2"
                    @input="clearFormErrors(form, 'address_2')"
                    :error="form.errors.address_2" />
                <Address3 v-model="form.address_3" @input="clearFormErrors(form, 'address_3')" :error="form.errors.address_3" />
                <Address4 v-model="form.address_4" @input="clearFormErrors(form, 'address_4')" :error="form.errors.address_4" />
            </div>
        </template>
    </BaseModal>
</template>
