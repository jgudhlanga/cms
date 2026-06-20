<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Title from '@/components/core/form/text/Title.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAddressTypes } from '@/composables/shared/useAddressTypes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { AddressType, AddressTypeParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const addressType = ref<AddressType>();
const form = useForm<AddressTypeParams>({
    title: '',
    description: '',
});

const { saveAddressType } = useAddressTypes();

const { modals } = useModalStore();

watch(modals!, () => {
    addressType.value = getModalEdit(APP_MODULE_KEYS.address_types);
    form.title = addressType.value?.attributes?.title ?? '';
    form.description = addressType.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.address_types"
        :title="`${addressType ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.address_type', 1)}`"
        :on-form-action="() => saveAddressType(form, addressType)"
        :form="form"
    >
        <template #body>
            <Title :inputAutoFocus="true" v-model="form.title" @input="clearFormErrors(form, 'title')" :error="form.errors.title" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
