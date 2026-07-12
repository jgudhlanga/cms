import type { ApprenticeImportPreviewRow } from '@/types/apprentice-import';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export function useApprenticeImportSelection(
    rows: ComputedRef<ApprenticeImportPreviewRow[]>,
): {
    selectedRowNumbers: Ref<number[]>;
    selectAllModel: ComputedRef<boolean>;
    selectedCount: ComputedRef<number>;
    selectableRows: ComputedRef<ApprenticeImportPreviewRow[]>;
    selectedRows: ComputedRef<ApprenticeImportPreviewRow[]>;
    isRowSelected: (rowNumber: number) => boolean;
    setRowSelected: (rowNumber: number, checked: boolean) => void;
    clearSelection: () => void;
    pruneSelectionToVisibleRows: () => void;
} {
    const selectedRowNumbers = ref<number[]>([]);

    const selectableRows = computed(() => rows.value.filter((row) => row.isSelectable));

    const selectAllModel = computed({
        get() {
            const list = selectableRows.value;
            if (list.length === 0) {
                return false;
            }

            const selectedSet = new Set(selectedRowNumbers.value);

            return list.every((row) => selectedSet.has(row.rowNumber));
        },
        set(checked: boolean) {
            if (checked) {
                selectedRowNumbers.value = selectableRows.value.map((row) => row.rowNumber);
            } else {
                selectedRowNumbers.value = [];
            }
        },
    });

    const selectedCount = computed(() => selectedRowNumbers.value.length);

    const selectedRows = computed(() => {
        const selectedSet = new Set(selectedRowNumbers.value);

        return rows.value.filter((row) => selectedSet.has(row.rowNumber) && row.isSelectable);
    });

    const isRowSelected = (rowNumber: number): boolean => {
        return selectedRowNumbers.value.includes(rowNumber);
    };

    const setRowSelected = (rowNumber: number, checked: boolean): void => {
        if (checked) {
            if (!selectedRowNumbers.value.includes(rowNumber)) {
                selectedRowNumbers.value = [...selectedRowNumbers.value, rowNumber];
            }

            return;
        }

        selectedRowNumbers.value = selectedRowNumbers.value.filter((value) => value !== rowNumber);
    };

    const clearSelection = (): void => {
        selectedRowNumbers.value = [];
    };

    const pruneSelectionToVisibleRows = (): void => {
        const visibleSelectable = new Set(selectableRows.value.map((row) => row.rowNumber));
        selectedRowNumbers.value = selectedRowNumbers.value.filter((rowNumber) => visibleSelectable.has(rowNumber));
    };

    return {
        selectedRowNumbers,
        selectAllModel,
        selectedCount,
        selectableRows,
        selectedRows,
        isRowSelected,
        setRowSelected,
        clearSelection,
        pruneSelectionToVisibleRows,
    };
}
