<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import StudentExportFilters from '@/components/students/filters/StudentExportFilters.vue';
import { useStudents } from '@/composables/students/useStudents';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { successAlert } from '@/lib/alerts';
import { useModalStore } from '@/store/core/useModalStore';
import type { StudentFiltersState } from '@/types/students';
import { SizeVariant } from '@/enums/sizes';
import { storeToRefs } from 'pinia';
import { trans } from 'laravel-vue-i18n';
import { ref, watch } from 'vue';

const { buildStudentExportUrl } = useStudents();
const modalStore = useModalStore();
const { closeModal, getEdit, isOpen } = modalStore;
const { modals } = storeToRefs(modalStore);

const exportFilters = ref<StudentFiltersState>({});
const departmentError = ref('');

const seedFromPageFilters = (): void => {
    const seeded = (getEdit(APP_MODULE_KEYS.student_list_export) as StudentFiltersState | undefined) ?? {};
    exportFilters.value = { ...seeded };
    departmentError.value = '';
};

watch(
    () => modals.value?.[APP_MODULE_KEYS.student_list_export]?.opened,
    (opened) => {
        if (opened) {
            seedFromPageFilters();
        }
    },
);

const onExportFiltersChange = (filters: StudentFiltersState): void => {
    exportFilters.value = filters;
    departmentError.value = '';
};

const handleExport = (): void => {
    if (!exportFilters.value.department?.length) {
        departmentError.value = trans('students.export_department_required');
        return;
    }

    window.open(buildStudentExportUrl(exportFilters.value), '_blank', 'noopener,noreferrer');
    successAlert(trans('students.export_success'));
    closeModal(APP_MODULE_KEYS.student_list_export);
};
</script>

<template>
    <BaseModal
        :title="$t('students.export_modal_title')"
        :name="APP_MODULE_KEYS.student_list_export"
        action-btn-text="trans.export"
        cancel-btn-text="trans.close"
        :on-form-action="handleExport"
        :size="SizeVariant.lg"
    >
        <template #body>
            <div class="space-y-4">
                <p class="text-sm text-foreground">
                    {{ $t('students.export_description') }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ $t('students.export_department_required_note') }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ $t('students.export_columns_note') }}
                </p>
                <p v-if="departmentError" class="text-sm text-destructive">
                    {{ departmentError }}
                </p>
                <StudentExportFilters
                    v-if="isOpen(APP_MODULE_KEYS.student_list_export)"
                    :filters="exportFilters"
                    @change="onExportFiltersChange"
                />
            </div>
        </template>
    </BaseModal>
</template>
