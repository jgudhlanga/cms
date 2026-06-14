<script setup lang="ts">
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import BaseContextField from '@/components/core/layout/BaseContextField.vue';
import { InstitutionDepartment } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    department: InstitutionDepartment;
    form: InertiaForm<{ department: null }>;
    modelValue: SelectOption;
    showSwitcher?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showSwitcher: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: SelectOption];
}>();

const selectedDepartment = computed({
    get: () => props.modelValue,
    set: (value: SelectOption) => emit('update:modelValue', value),
});

const departmentName = computed(() => props.department.attributes?.department ?? '');
const departmentCode = computed(() => props.department.attributes?.departmentCode);
</script>

<template>
    <div class="py-2">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 space-y-1">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {{ $tChoice('trans.department', 1) }}
                </p>
                <h1 class="text-xl font-semibold tracking-tight text-foreground md:text-2xl">
                    {{ departmentName }}
                </h1>
                <p v-if="departmentCode" class="text-sm text-muted-foreground">
                    {{ departmentCode }}
                </p>
            </div>

            <BaseContextField
                v-if="showSwitcher"
                :label="$t('trans.ui_switch_department')"
                icon="company"
                class="w-full lg:max-w-sm"
            >
                <InstitutionDepartmentComboSelect
                    :form="form"
                    v-model="selectedDepartment"
                    label=""
                    width-class="w-full"
                />
            </BaseContextField>
        </div>
    </div>
</template>
