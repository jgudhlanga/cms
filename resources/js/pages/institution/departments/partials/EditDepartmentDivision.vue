<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { useModalStore } from '@/store/core/useModalStore';
import { InstitutionDepartment } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    divisionOptions: Array<{ id: number | string; name: string | null }>;
}>();

const department = ref<InstitutionDepartment>();
const form = useForm({
    division_id: null as number | null,
});
const divisionOption = ref<SelectOption | null>(null);
const { modals } = useModalStore();

const options = computed<SelectOption[]>(() =>
    (props.divisionOptions ?? []).map((division) => ({
        value: Number(division.id),
        label: division.name ?? '—',
    })),
);

watch(modals!, () => {
    department.value = getModalEdit(APP_MODULE_KEYS.institution_department_division);
    form.division_id = department.value?.attributes?.divisionId ? Number(department.value.attributes.divisionId) : null;
    divisionOption.value =
        form.division_id != null ? (options.value.find((option) => Number(option.value) === Number(form.division_id)) ?? null) : null;
    form.defaults();
});

watch(divisionOption, (option) => {
    form.division_id = option?.value != null ? Number(option.value) : null;
    clearFormErrors(form, 'division_id');
});

const save = () => {
    if (!department.value?.id) {
        return;
    }

    const id = getIdParams(department.value.id.toString());
    const success = trans('trans.item_saved', { item: trans_choice('trans.department', 1) });
    const error = trans('trans.item_save_failure', { item: trans_choice('trans.department', 1) });
    form.put(route('institution-departments.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.institution_department_division));
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.institution_department_division"
        :title="$t('trans.edit_department_division')"
        :on-form-action="save"
        :form="form"
    >
        <template #body>
            <p v-if="department" class="mb-3 text-sm text-muted-foreground">
                {{ department.attributes?.department }}
            </p>
            <BaseCombobox
                v-model="divisionOption"
                :label="$tChoice('trans.division', 1)"
                :placeholder="$t('trans.select_division')"
                :options="options"
                :error="form.errors.division_id"
            />
        </template>
    </BaseModal>
</template>
