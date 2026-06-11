import { STAFF_IMPORT_RESULT_AUTO_DISMISS_MS } from '@/composables/maintenance/staff-import/constants';
import type { StaffImportFailedRow, StaffImportResult } from '@/types/staff-import';
import { computed, onMounted, onUnmounted, ref, watch, type Ref } from 'vue';

export const useStaffImportResult = (staffImportResult: Ref<StaffImportResult | null | undefined>) => {
    const importResultDismissed = ref(false);
    const persistedFailedRows = ref<StaffImportFailedRow[]>([]);
    let importResultDismissTimer: ReturnType<typeof setTimeout> | null = null;

    const clearImportResultDismissTimer = (): void => {
        if (importResultDismissTimer !== null) {
            clearTimeout(importResultDismissTimer);
            importResultDismissTimer = null;
        }
    };

    const syncPersistedFailedRows = (result: StaffImportResult | null | undefined): void => {
        if (result?.failedRows?.length) {
            persistedFailedRows.value = result.failedRows;
            return;
        }

        if (result != null && result.rowsFailed === 0) {
            persistedFailedRows.value = [];
        }
    };

    const scheduleImportResultAutoDismiss = (result: StaffImportResult): void => {
        if (result.rowsFailed > 0) {
            return;
        }

        clearImportResultDismissTimer();
        importResultDismissTimer = setTimeout(() => {
            importResultDismissed.value = true;
            importResultDismissTimer = null;
        }, STAFF_IMPORT_RESULT_AUTO_DISMISS_MS);
    };

    const clearPersistedFailedRows = (): void => {
        persistedFailedRows.value = [];
    };

    const dismissImportResult = (): void => {
        clearImportResultDismissTimer();
        importResultDismissed.value = true;
    };

    const hasImportResult = computed(
        (): boolean => staffImportResult.value != null && !importResultDismissed.value,
    );

    onMounted(() => {
        if (staffImportResult.value != null) {
            importResultDismissed.value = false;
            syncPersistedFailedRows(staffImportResult.value);
            scheduleImportResultAutoDismiss(staffImportResult.value);
        }
    });

    watch(staffImportResult, (result) => {
        if (result != null) {
            importResultDismissed.value = false;
            syncPersistedFailedRows(result);
            scheduleImportResultAutoDismiss(result);
        } else {
            clearImportResultDismissTimer();
        }
    });

    onUnmounted(() => {
        clearImportResultDismissTimer();
    });

    return {
        persistedFailedRows,
        hasImportResult,
        dismissImportResult,
        clearPersistedFailedRows,
    };
};
