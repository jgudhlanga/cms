<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { computed } from 'vue';

export type ClassListExportClassPickerClass = {
    academicCalendarClassId: number | null;
    name: string;
    studentCount: number;
};

const props = withDefaults(
    defineProps<{
        classes: ClassListExportClassPickerClass[];
        locked?: boolean;
    }>(),
    {
        locked: false,
    },
);

const selectedClassIds = defineModel<number[]>('selectedClassIds', { required: true });

const exportableClasses = computed(() =>
    props.classes.filter((classOption) => classOption.academicCalendarClassId != null),
);

const allSelected = computed({
    get: () => {
        const ids = exportableClasses.value.map((classOption) => classOption.academicCalendarClassId as number);
        return ids.length > 0 && ids.every((id) => selectedClassIds.value.includes(id));
    },
    set: (checked: boolean) => {
        if (props.locked) {
            return;
        }

        selectedClassIds.value = checked
            ? exportableClasses.value.map((classOption) => classOption.academicCalendarClassId as number)
            : [];
    },
});

const toggleClass = (classId: number, checked: boolean): void => {
    if (props.locked) {
        return;
    }

    if (checked) {
        if (!selectedClassIds.value.includes(classId)) {
            selectedClassIds.value = [...selectedClassIds.value, classId];
        }

        return;
    }

    selectedClassIds.value = selectedClassIds.value.filter((id) => id !== classId);
};

const isClassSelected = (classId: number): boolean => selectedClassIds.value.includes(classId);
</script>

<template>
    <div class="space-y-3">
        <div v-if="!locked && exportableClasses.length > 1" class="flex items-center gap-2">
            <Checkbox
                id="class_list_export_select_all"
                :checked="allSelected"
                @update:checked="(value: boolean) => (allSelected = value)"
            />
            <label for="class_list_export_select_all" class="text-sm font-medium text-foreground">
                {{ $t('trans.select_all') }}
            </label>
        </div>
        <div class="max-h-64 space-y-2 overflow-y-auto rounded-md border border-border p-3">
            <div
                v-for="classOption in exportableClasses"
                :key="classOption.academicCalendarClassId ?? classOption.name"
                class="flex items-start gap-2"
            >
                <Checkbox
                    :id="`class_list_export_${classOption.academicCalendarClassId}`"
                    :checked="isClassSelected(classOption.academicCalendarClassId as number)"
                    :disabled="locked"
                    @update:checked="(value: boolean) => toggleClass(classOption.academicCalendarClassId as number, value)"
                />
                <label
                    :for="`class_list_export_${classOption.academicCalendarClassId}`"
                    class="flex-1 cursor-pointer text-sm text-foreground"
                >
                    <span class="font-medium">{{ classOption.name }}</span>
                    <span class="text-muted-foreground">
                        ({{ classOption.studentCount }} {{ $tChoice('trans.student', classOption.studentCount) }})
                    </span>
                </label>
            </div>
        </div>
    </div>
</template>
