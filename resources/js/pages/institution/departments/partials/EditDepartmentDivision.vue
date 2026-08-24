<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import DepartmentColorSwatch from '@/components/institution/DepartmentColorSwatch.vue';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { resolveDepartmentColor } from '@/lib/departmentColor';
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
    color_code: '#2563EB',
});
const divisionOption = ref<SelectOption | null>(null);
const { modals } = useModalStore();

const options = computed<SelectOption[]>(() =>
    (props.divisionOptions ?? []).map((division) => ({
        value: Number(division.id),
        label: division.name ?? '—',
    })),
);

const previewColor = computed(() => resolveDepartmentColor(form.color_code, department.value?.attributes?.department, 1));

watch(modals!, () => {
    department.value = getModalEdit(APP_MODULE_KEYS.institution_department_division);
    form.division_id = department.value?.attributes?.divisionId ? Number(department.value.attributes.divisionId) : null;
    form.color_code = department.value?.attributes?.colorCode ?? '#2563EB';
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
            <p v-if="department" class="mb-3 flex items-center gap-2 text-sm text-muted-foreground">
                <DepartmentColorSwatch :color-code="previewColor" :department-name="department.attributes?.department" size-class="h-3 w-3" />
                {{ department.attributes?.department }}
            </p>
            <BaseCombobox
                v-model="divisionOption"
                :label="$tChoice('trans.division', 1)"
                :placeholder="$t('trans.select_division')"
                :options="options"
                :error="form.errors.division_id"
            />
            <div class="mt-4 space-y-2">
                <label class="text-sm font-medium text-foreground" for="department_color_code">
                    {{ $t('trans.department_color') }}
                </label>
                <div class="flex items-center gap-3">
                    <input
                        id="department_color_code"
                        v-model="form.color_code"
                        type="color"
                        class="h-10 w-14 cursor-pointer rounded border border-border bg-transparent p-1"
                    />
                    <input
                        v-model="form.color_code"
                        type="text"
                        maxlength="7"
                        class="h-10 flex-1 rounded-md border border-border bg-background px-3 text-sm uppercase"
                    />
                </div>
                <p v-if="form.errors.color_code" class="text-xs text-destructive">{{ form.errors.color_code }}</p>
            </div>
        </template>
    </BaseModal>
</template>
