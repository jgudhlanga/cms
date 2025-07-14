<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
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
                    placeholder="enter next of kin"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'name')"
                    :error="form.errors.name"
                />
                <BaseInput
                    input-id="phone_number"
                    :label="$t('trans.phone_number')"
                    v-model="form.phone_number"
                    placeholder="enter phone number"
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
            <div class="grid-col-1 grid gap-3 md:grid-cols-4">
                <BaseInput
                    input-id="address_1"
                    :label="$t('trans.address_1')"
                    v-model="form.address_1"
                    placeholder="enter address line 1 "
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'address_1')"
                    :error="form.errors.address_1"
                    :is-required="true"
                />
                <BaseInput
                    input-id="address_2"
                    :label="$t('trans.address_2')"
                    v-model="form.address_2"
                    placeholder="enter address line 2"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'address_2')"
                    :error="form.errors.address_2"
                    :is-required="true"
                />
                <BaseInput
                    input-id="address_3"
                    :label="$t('trans.address_3')"
                    v-model="form.address_3"
                    placeholder="enter address line 3"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'address_3')"
                    :error="form.errors.address_3"
                    :is-required="true"
                />
                <BaseInput
                    input-id="address_4"
                    :label="$t('trans.address_4')"
                    v-model="form.address_4"
                    placeholder="enter address line 4"
                    :label-uppercase="true"
                    :error="form.errors.address_4"
                />
            </div>
        </template>
    </BaseModal>
</template>
