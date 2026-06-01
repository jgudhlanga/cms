<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
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
const { isItTrue } = useUtils();
const verificationMode = isItTrue(import.meta.env.VITE_VERIFICATION_MODE);
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-border bg-card text-card-foreground shadow">
        <div class="border-b border-border bg-muted/30 px-4 py-2">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold text-foreground uppercase">
                    {{ result?.attributes?.subject }}
                </h3>
                <div class="flex space-x-2" v-if="!verificationMode">
                    <IconButton :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="onCreateOrEdit(result)" />
                    <IconButton :icon="IconName.trash" :variant="ColorVariant.danger_outline" @click="handleDelete" />
                </div>
            </div>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3 sm:gap-4">
                <GridLabelValue :label="$tChoice('trans.year', 1)" :value="result?.attributes?.examYear" />
                <GridLabelValue :label="$tChoice('trans.sitting', 1)" :value="sittingLabel" />
                <GridLabelValue :label="$tChoice('trans.grade', 1)" :value="result.attributes?.grade" />
            </div>
        </div>
    </div>
</template>
