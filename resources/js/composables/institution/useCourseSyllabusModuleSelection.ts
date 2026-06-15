import type { CourseSyllabusModule } from '@/types/institution';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export function useCourseSyllabusModuleSelection(modulesList: ComputedRef<CourseSyllabusModule[]>): {
    selectedModuleIds: Ref<number[]>;
    selectAllMoveModel: ComputedRef<boolean>;
    toggleSelectAllMoveFromRow: () => void;
    onSelectAllRowKeydown: (event: KeyboardEvent) => void;
} {
    const selectedModuleIds = ref<number[]>([]);

    const selectAllMoveModel = computed({
        get() {
            const list = modulesList.value;
            if (list.length === 0) {
                return false;
            }

            return selectedModuleIds.value.length === list.length;
        },
        set(checked: boolean) {
            if (checked) {
                selectedModuleIds.value = modulesList.value
                    .map((m) => Number(m.id))
                    .filter((id) => !Number.isNaN(id));
            } else {
                selectedModuleIds.value = [];
            }
        },
    });

    const toggleSelectAllMoveFromRow = (): void => {
        const list = modulesList.value;
        if (list.length === 0) {
            return;
        }
        if (selectedModuleIds.value.length === list.length) {
            selectedModuleIds.value = [];
        } else {
            selectedModuleIds.value = list
                .map((m) => Number(m.id))
                .filter((id) => !Number.isNaN(id));
        }
    };

    const onSelectAllRowKeydown = (event: KeyboardEvent): void => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggleSelectAllMoveFromRow();
        }
    };

    return {
        selectedModuleIds,
        selectAllMoveModel,
        toggleSelectAllMoveFromRow,
        onSelectAllRowKeydown,
    };
}
