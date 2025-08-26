<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartments } from '@/composables/institution/useDepartments';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Department, DepartmentParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const department = ref<Department>();
const form = useForm<DepartmentParams>({
    name: '',
    is_academic: false,
    description: '',
});

const { saveDepartment } = useDepartments();
const { isItTrue } = useUtils();
const { modals } = useModalStore();

watch(modals!, () => {
    department.value = getModalEdit(APP_MODULE_KEYS.departments);
    form.name = department.value?.attributes?.name ?? '';
    form.is_academic = isItTrue(department.value?.attributes?.isAcademic) ?? false;
    form.description = department.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.departments"
        :title="`${department ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.department', 1)}`"
        :on-form-action="() => saveDepartment(form, department)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <BaseCheckbox input-id="is_academic" v-model="form.is_academic" :label="`${$t('trans.is_academic')}`" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
