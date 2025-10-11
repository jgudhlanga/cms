<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { OLevelSubjectResult } from '@/types/enrolments';
import { computed, defineEmits } from 'vue';

interface Props {
    result: OLevelSubjectResult;
    sittingLabel: string;
    deleteCallback?: () => void;
}

const props = defineProps<Props>();
const emit = defineEmits(['deleted']);
const { onCreateOrEdit, onDeleteResult } = useOLevelResults();

const resultRecordId = computed(() => {
    const id = props.result?.attributes?.resultId;
    return id && String(id) !== '--' ? String(id) : '';
});

const handleDelete = async () => {
    try {
        if (props.deleteCallback) {
            props.deleteCallback();
            return;
        }
        await onDeleteResult(resultRecordId.value, async () => emit('deleted'));
    } finally {
    }
};
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
        <div class="bg-card border-b border-gray-100 px-4 py-2">
            <div class="flex items-center justify-between">
                <h3 class="text-accent-foreground text-xs font-semibold uppercase">
                    {{ result?.attributes?.subject }}
                </h3>
                <div class="flex space-x-2">
                    <IconButton :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="onCreateOrEdit(result)" />
                    <IconButton :icon="IconName.trash" :variant="ColorVariant.danger_outline" @click="handleDelete" />
                </div>
            </div>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-3 gap-4 text-sm">
                <GridLabelValue label="Year" :value="result?.attributes?.examYear" />
                <GridLabelValue label="Sitting" :value="sittingLabel" />
                <GridLabelValue label="Grade" :value="result.attributes?.grade" />
            </div>
        </div>
    </div>
</template>
