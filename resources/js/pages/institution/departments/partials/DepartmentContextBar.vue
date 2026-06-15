<script setup lang="ts">
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import BaseContextField from '@/components/core/layout/BaseContextField.vue';
import { InstitutionDepartment } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import {IconName, icons} from "@/lib/icons"

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
    <div class="flex items-center">
        <div class="flex space-x-2 w-[200px] items-center">
            <component :is="icons[IconName.company]" class="h-4 w-4 shrink-0 text-muted-foreground" />
            <span class="text-sm font-medium text-foreground uppercase">{{ $t('trans.ui_switch_department') }}</span>
        </div>
        <div class="flex w-1/3">
            <InstitutionDepartmentComboSelect
                :form="form"
                v-model="selectedDepartment"
                label=""
                width-class="w-full"
            />
        </div>
    </div>
</template>
