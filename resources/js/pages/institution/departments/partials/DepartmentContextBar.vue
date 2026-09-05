<script setup lang="ts">
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import { IconName, icons } from '@/lib/icons';
import { InstitutionDepartment } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    department: InstitutionDepartment;
    form: InertiaForm<{ department: null }>;
    modelValue: SelectOption;
    showSwitcher?: boolean;
    isAcademic?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showSwitcher: true,
    isAcademic: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: SelectOption];
}>();

const selectedDepartment = computed({
    get: () => props.modelValue,
    set: (value: SelectOption) => emit('update:modelValue', value),
});
</script>

<template>
    <div class="flex h-8 shrink-0 items-center gap-2">
        <div class="flex shrink-0 items-center gap-1.5">
            <component :is="icons[IconName.company]" class="text-muted-foreground h-3.5 w-3.5 shrink-0" />
            <span class="text-muted-foreground hidden text-[11px] leading-none font-medium tracking-wide uppercase sm:inline">
                {{ $t('trans.ui_switch_department') }}
            </span>
        </div>
        <div class="flex h-8 w-56 min-w-0 items-center sm:w-72">
            <InstitutionDepartmentComboSelect
                :form="form"
                v-model="selectedDepartment"
                label=""
                :vertical-layout="false"
                width-class="w-full"
                :is-academic="isAcademic"
            />
        </div>
    </div>
</template>
