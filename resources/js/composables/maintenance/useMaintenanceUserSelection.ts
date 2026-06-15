import type { NonEnrolledStudentUser } from '@/types/maintenance-users';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export function useMaintenanceUserSelection(
    users: ComputedRef<NonEnrolledStudentUser[]>,
    isSelectable: (user: NonEnrolledStudentUser) => boolean = () => true,
): {
    selectedUserIds: Ref<number[]>;
    selectAllModel: ComputedRef<boolean>;
    selectedCount: ComputedRef<number>;
    clearSelection: () => void;
    pruneSelectionToVisibleUsers: () => void;
} {
    const selectedUserIds = ref<number[]>([]);

    const selectableUsers = computed(() => users.value.filter(isSelectable));

    const selectAllModel = computed({
        get() {
            const list = selectableUsers.value;
            if (list.length === 0) {
                return false;
            }

            const selectedSet = new Set(selectedUserIds.value);

            return list.every((user) => selectedSet.has(user.id));
        },
        set(checked: boolean) {
            if (checked) {
                selectedUserIds.value = selectableUsers.value.map((user) => user.id);
            } else {
                selectedUserIds.value = [];
            }
        },
    });

    const selectedCount = computed(() => selectedUserIds.value.length);

    const clearSelection = (): void => {
        selectedUserIds.value = [];
    };

    const pruneSelectionToVisibleUsers = (): void => {
        const visibleSelectableIds = new Set(selectableUsers.value.map((user) => user.id));
        selectedUserIds.value = selectedUserIds.value.filter((id) => visibleSelectableIds.has(id));
    };

    return {
        selectedUserIds,
        selectAllModel,
        selectedCount,
        clearSelection,
        pruneSelectionToVisibleUsers,
    };
};
