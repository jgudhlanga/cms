<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import Address1 from '@/components/core/form/text/Address1.vue';
import Address2 from '@/components/core/form/text/Address2.vue';
import Address3 from '@/components/core/form/text/Address3.vue';
import Address4 from '@/components/core/form/text/Address4.vue';
import RelationshipComboSelect from '@/components/core/form/combobox/RelationshipComboSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useNextOfKin } from '@/composables/shared/useNextOfKin';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { NextOfKin, NextOfKinParams } from '@/types/next-of-kin';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    postUrl: string;
}

const props = defineProps<Props>();

const nextOfKin = ref<NextOfKin>();
const form = useForm<NextOfKinParams>({
    address_1: '',
    address_2: '',
    address_3: '',
    address_4: '',
    relationship: null,
    relationship_id: null,
    name: '',
    phone_number: '',
});

const { createNextOfKin, updateNextOfKin } = useNextOfKin();

const { modals } = useModalStore();

watch(modals!, () => {
    nextOfKin.value = getModalEdit(APP_MODULE_KEYS.next_of_kin);
    form.name = nextOfKin.value?.attributes?.name ?? '';
    form.phone_number = nextOfKin.value?.attributes?.phoneNumber ?? '';
    form.address_1 = nextOfKin.value?.attributes?.address1 ?? '';
    form.address_2 = nextOfKin.value?.attributes?.address2 ?? '';
    form.address_3 = nextOfKin.value?.attributes?.address3 ?? '';
    form.address_4 = nextOfKin.value?.attributes?.address4 ?? '';
    form.relationship = {
        value: nextOfKin.value?.attributes?.relationshipId ?? '',
        label: nextOfKin.value?.attributes?.relationship ?? '',
    };
    form.relationship_id = nextOfKin.value?.attributes?.relationshipId ?? null;
    form.defaults();
});

const save = () => {
    form.relationship_id = form.relationship?.value;
    if (Number(nextOfKin.value?.id?.toString()) > 0) {
        updateNextOfKin(form, nextOfKin.value);
    } else {
        createNextOfKin(form, props.postUrl);
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.next_of_kin"
        :title="`${nextOfKin ? $t('trans.update') : $t('trans.create')} ${$t('trans.next_of_kin')}`"
        :on-form-action="() => save()"
        :form="form"
        :size="SizeVariant.lg"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <BaseInput
                    input-id="name"
                    :label="$tChoice('trans.name', 1)"
                    v-model="form.name"
                    :placeholder="$t('trans.ui_enter_next_of_kin')"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'name')"
                    :error="form.errors.name"
                />
                <BaseInput
                    input-id="phone_number"
                    :label="$t('trans.phone_number')"
                    v-model="form.phone_number"
                    :placeholder="$t('trans.ui_enter_phone_number')"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'phone_number')"
                    :error="form.errors.phone_number"
                />
                <RelationshipComboSelect
                    :form="form"
                    v-model="form.relationship"
                    :error="form.errors.relationship"
                    :label-uppercase="true"
                    :is-required="true"
                />
            </div>
            <div class="flex flex-col">
                <HeadingSmall :title="$t('trans.residential_address')" :description="$t('trans.residential_address_description')" class="my-5" />
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <Address1
                    v-model="form.address_1"
                    @input="clearFormErrors(form, 'address_1')"
                    :error="form.errors.address_1"
                    :is-required="true"
                />
                <Address2
                    v-model="form.address_2"
                    @input="clearFormErrors(form, 'address_2')"
                    :error="form.errors.address_2"
                    :is-required="true"
                />
                <Address3
                    v-model="form.address_3"
                    @input="clearFormErrors(form, 'address_3')"
                    :error="form.errors.address_3"
                    :is-required="true"
                />
                <Address4
                    v-model="form.address_4"
                    @input="clearFormErrors(form, 'address_4')"
                    :error="form.errors.address_4"
                />
            </div>
        </template>
    </BaseModal>
</template>
