import type { StudentIdCardImportPreviewRow } from '@/types/student-id-card-import';
import type { ComputedRef } from 'vue';
import { computed, ref } from 'vue';

export function useStudentIdCardImportSelection(
    rows: ComputedRef<StudentIdCardImportPreviewRow[]>,
): {
    selectAllModel: ComputedRef<boolean>;
    selectedCount: ComputedRef<number>;
    selectedRows: ComputedRef<StudentIdCardImportPreviewRow[]>;
    isRowSelected: (rowNumber: number) => boolean;
    setRowSelected: (rowNumber: number, checked: boolean) => void;
    selectRowNumbers: (rowNumbers: number[]) => void;
    clearSelection: () => void;
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
            selectedRowNumbers.value = checked
                ? selectableRows.value.map((row) => row.rowNumber)
                : [];
        },
    });

    const selectedCount = computed(() => selectedRowNumbers.value.length);

    const selectedRows = computed(() => {
        const selectedSet = new Set(selectedRowNumbers.value);

        return rows.value.filter((row) => selectedSet.has(row.rowNumber) && row.isSelectable);
    });

    const isRowSelected = (rowNumber: number): boolean => selectedRowNumbers.value.includes(rowNumber);

    const setRowSelected = (rowNumber: number, checked: boolean): void => {
        if (checked) {
            if (!selectedRowNumbers.value.includes(rowNumber)) {
                selectedRowNumbers.value = [...selectedRowNumbers.value, rowNumber];
            }

            return;
        }

        selectedRowNumbers.value = selectedRowNumbers.value.filter((value) => value !== rowNumber);
    };

    const selectRowNumbers = (rowNumbers: number[]): void => {
        selectedRowNumbers.value = [...rowNumbers];
    };

    const clearSelection = (): void => {
        selectedRowNumbers.value = [];
    };

    return {
        selectAllModel,
        selectedCount,
        selectedRows,
        isRowSelected,
        setRowSelected,
        selectRowNumbers,
        clearSelection,
    };
}
