<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { OLevelSubjectResult } from '@/types/enrolments';
import { computed } from 'vue';

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

const handleDelete = async (event: Event) => {
    event.stopPropagation();
    try {
        if (props.deleteCallback) {
            props.deleteCallback();
            return;
        }
        await onDeleteResult(resultRecordId.value, async () => emit('deleted'));
    } finally {
    }
};

const handleEdit = (event?: Event) => {
    event?.stopPropagation();
    onCreateOrEdit(props.result);
};

const { isItTrue } = useUtils();
const verificationMode = isItTrue(import.meta.env.VITE_VERIFICATION_MODE);

const metaLine = computed(() => {
    const year = props.result?.attributes?.examYear ?? '—';
    const sitting = props.sittingLabel || '—';
    return `${year} · ${sitting}`;
});
</script>

<template>
    <div
        class="overflow-hidden rounded-lg border border-border bg-card text-card-foreground shadow transition-colors"
        :class="!verificationMode ? 'cursor-pointer hover:border-primary/40' : ''"
        @click="!verificationMode ? handleEdit() : undefined"
    >
        <div class="border-b border-border bg-muted/30 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-xs font-semibold text-foreground uppercase">
                        {{ result?.attributes?.subject }}
                    </h3>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <span>{{ metaLine }}</span>
                        <span
                            v-if="result.attributes?.grade"
                            class="inline-flex min-h-7 min-w-7 items-center justify-center rounded-md bg-primary/10 px-2 text-sm font-semibold text-primary"
                        >
                            {{ result.attributes.grade }}
                        </span>
                    </div>
                </div>
                <div v-if="!verificationMode" class="flex shrink-0 gap-0.5" @click.stop>
                    <div class="flex min-h-11 min-w-11 items-center justify-center">
                        <IconButton :icon="IconName.edit" tone="header-primary" @click="handleEdit" />
                    </div>
                    <div class="flex min-h-11 min-w-11 items-center justify-center">
                        <IconButton :icon="IconName.trash" tone="header-danger" @click="handleDelete" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
