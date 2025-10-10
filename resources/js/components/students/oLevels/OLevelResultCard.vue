<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { OLevelSubjectResult } from '@/types/enrolments';
import { defineEmits } from 'vue';

interface Props {
    result: OLevelSubjectResult;
    sittingLabel: string;
}

const props = defineProps<Props>();
const emit = defineEmits(['deleted']);
const { onCreateOrEdit, onDeleteResult } = useOLevelResults();

const handleDelete = async () => {
    await onDeleteResult(String(props.result.attributes?.resultId), async () => {
        emit('deleted');
    });
};
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
        <!-- Card Header -->
        <div class="bg-card border-b border-gray-100 px-4 py-2">
            <div class="flex items-center justify-between">
                <h3 class="text-accent-foreground text-xs font-semibold uppercase">
                    {{ result?.attributes?.subject }}
                </h3>
                <div class="flex space-x-2">
                    <IconButton :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="onCreateOrEdit(result)" />
                    <IconButton
                        :icon="IconName.trash"
                        :variant="ColorVariant.danger_outline"
                        @click="handleDelete"
                    />
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="p-4">
            <div class="grid grid-cols-3 gap-4 text-sm text-gray-900">
                <div>
                    <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Year</p>
                    <p class="mt-1">
                        {{ result?.attributes?.examYear ?? '---' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Sitting</p>
                    <p class="mt-1">
                        {{ sittingLabel ?? '---' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Grade</p>
                    <div class="mt-1">{{ result.attributes?.grade ?? '---' }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
