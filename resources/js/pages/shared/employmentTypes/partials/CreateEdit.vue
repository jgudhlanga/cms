<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useEmploymentTypes } from '@/composables/shared/useEmploymentTypes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { EmploymentType, EmploymentTypeParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const employmentType = ref<EmploymentType>();
const form = useForm<EmploymentTypeParams>({
    name: '',
    description: '',
});

const { saveEmploymentType } = useEmploymentTypes();

const { modals } = useModalStore();

watch(modals!, () => {
    employmentType.value = getModalEdit(APP_MODULE_KEYS.employment_types);
    form.name = employmentType.value?.attributes?.name ?? '';
    form.description = employmentType.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.employment_types"
        :title="`${employmentType ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.employment_type', 1)}`"
        :on-form-action="() => saveEmploymentType(form, employmentType)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
