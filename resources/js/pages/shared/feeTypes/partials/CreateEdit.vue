<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useFeeTypes } from '@/composables/shared/useFeeTypes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { FeeType, FeeTypeParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const feeType = ref<FeeType>();
const form = useForm<FeeTypeParams>({
    name: '',
    description: '',
});

const { saveFeeType } = useFeeTypes();

const { modals } = useModalStore();

watch(modals!, () => {
    feeType.value = getModalEdit(APP_MODULE_KEYS.fee_types);
    form.name = feeType.value?.attributes?.name ?? '';
    form.description = feeType.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.fee_types"
        :title="`${feeType ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.fee_type', 1)}`"
        :on-form-action="() => saveFeeType(form, feeType)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
