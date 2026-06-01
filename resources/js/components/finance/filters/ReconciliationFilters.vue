<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

export interface ReconciliationFiltersState {
    student?: string;
    reference?: string;
    status?: string;
}

interface Props {
    filters: ReconciliationFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: ReconciliationFiltersState): void;
}>();

const student = ref(props.filters.student ?? '');
const reference = ref(props.filters.reference ?? '');
const statusSelection = ref<SelectOption | null>(
    props.filters.status
        ? { value: props.filters.status, label: String(props.filters.status) }
        : null
);

const statusOptions = computed<SelectOption[]>(() => [
    { value: 'submitted', label: 'Submitted' },
    { value: 'under_review', label: 'Under review' },
    { value: 'needs_info', label: 'Needs info' },
    { value: 'reconciled', label: 'Reconciled' },
    { value: 'declined', label: 'Declined' },
]);

const emitFilters = useDebounceFn(() => {
    emit('change', {
        student: student.value.trim() || undefined,
        reference: reference.value.trim() || undefined,
        status: statusSelection.value?.value ? String(statusSelection.value.value) : undefined,
    });
}, 300);

watch([student, reference, statusSelection], emitFilters, { deep: true });

const resetFilters = () => {
    student.value = '';
    reference.value = '';
    statusSelection.value = null;
    emit('change', {});
};
</script>

<template>
    <div class="flex w-full flex-col gap-3">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <BaseInput
                v-model="student"
                input-id="reconciliation_filter_student"
                :placeholder="$t('finance.search_by_student_name')"
            />
            <BaseInput
                v-model="reference"
                input-id="reconciliation_filter_reference"
                :placeholder="$t('finance.search_by_reference')"
            />
            <BaseCombobox
                v-model="statusSelection"
                :options="statusOptions"
                :placeholder="$tChoice('trans.status', 1)"
                class="rounded-md"
            />
            <div>
                <ResetButton @click="resetFilters" />
            </div>
        </div>
    </div>
</template>
