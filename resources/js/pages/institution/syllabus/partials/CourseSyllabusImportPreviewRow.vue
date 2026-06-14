<script setup lang="ts">
import {
    activeModuleErrors,
    activeSyllabusErrors,
    effectiveModuleAction,
    effectiveSyllabusAction,
    getEffectiveCorrection,
    resolvedField,
} from '@/composables/institution/syllabus-import/syllabusImportRowHelpers';
import type {
    SyllabusImportPreviewAction,
    SyllabusImportPreviewLookups,
    SyllabusImportPreviewRow,
    SyllabusImportRowCorrection,
} from '@/types/syllabus-import';
import { Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    row: SyllabusImportPreviewRow;
    lookups: SyllabusImportPreviewLookups;
    correction: SyllabusImportRowCorrection;
    actionLabel: (action: SyllabusImportPreviewAction) => string;
    actionClass: (action: SyllabusImportPreviewAction) => string;
}>();

const emit = defineEmits<{
    'update:correction': [SyllabusImportRowCorrection];
    remove: [];
}>();

const rowCorrections = computed((): Record<number, SyllabusImportRowCorrection> => ({
    [props.row.rowNumber]: props.correction,
}));

const syllabusAction = computed(() =>
    effectiveSyllabusAction(props.row, rowCorrections.value, props.lookups),
);
const moduleAction = computed(() =>
    effectiveModuleAction(props.row, rowCorrections.value, props.lookups),
);
const syllabusErrors = computed(() =>
    activeSyllabusErrors(props.row, rowCorrections.value, props.lookups),
);
const moduleErrors = computed(() =>
    activeModuleErrors(props.row, rowCorrections.value, props.lookups),
);

const fieldInputClass = (field: keyof SyllabusImportRowCorrection, hasError: boolean): string => {
    const corrected = props.correction[field] !== undefined;
    const resolved = resolvedField(props.row, rowCorrections.value, field);

    if (corrected && resolved !== '' && !hasError) {
        return 'border-green-500 bg-green-50';
    }

    if (hasError) {
        return 'border-destructive bg-destructive/5';
    }

    return 'border-border';
};

const updateCorrection = (patch: SyllabusImportRowCorrection): void => {
    emit('update:correction', {
        ...getEffectiveCorrection(rowCorrections.value, props.row),
        ...patch,
    });
};

const onFieldInput = (field: keyof SyllabusImportRowCorrection, event: Event): void => {
    updateCorrection({ [field]: (event.target as HTMLInputElement).value });
};
</script>

<template>
    <tr class="border-b align-top">
        <td class="px-2 py-2">
            <div class="flex items-center gap-2">
                <span>{{ row.rowNumber }}</span>
                <button
                    type="button"
                    class="text-destructive hover:text-destructive/80"
                    :title="$t('syllabus.import_preview_remove_row')"
                    @click="emit('remove')"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </td>
        <td class="px-2 py-2">
            <input
                :value="resolvedField(row, rowCorrections, 'level')"
                list="syllabus-import-levels"
                class="mb-1 w-full min-w-32 rounded-md border px-2 py-1 text-sm"
                :class="fieldInputClass('level', syllabusErrors.length > 0)"
                @input="onFieldInput('level', $event)"
            />
            <input
                :value="resolvedField(row, rowCorrections, 'courseTitle')"
                list="syllabus-import-courses"
                class="mb-1 w-full min-w-32 rounded-md border px-2 py-1 text-sm"
                :class="fieldInputClass('courseTitle', syllabusErrors.length > 0)"
                @input="onFieldInput('courseTitle', $event)"
            />
            <input
                :value="resolvedField(row, rowCorrections, 'courseCode')"
                class="w-full min-w-32 rounded-md border px-2 py-1 text-sm"
                :class="fieldInputClass('courseCode', syllabusErrors.length > 0)"
                @input="onFieldInput('courseCode', $event)"
            />
        </td>
        <td class="px-2 py-2">
            <input
                :value="resolvedField(row, rowCorrections, 'semester')"
                list="syllabus-import-semesters"
                class="w-full min-w-32 rounded-md border px-2 py-1 text-sm"
                :class="fieldInputClass('semester', moduleErrors.length > 0)"
                @input="onFieldInput('semester', $event)"
            />
        </td>
        <td class="px-2 py-2">
            <input
                :value="resolvedField(row, rowCorrections, 'moduleTitle')"
                class="mb-1 w-full min-w-32 rounded-md border px-2 py-1 text-sm"
                :class="fieldInputClass('moduleTitle', moduleErrors.length > 0)"
                @input="onFieldInput('moduleTitle', $event)"
            />
            <input
                :value="resolvedField(row, rowCorrections, 'moduleCode')"
                class="w-full min-w-32 rounded-md border px-2 py-1 text-sm"
                :class="fieldInputClass('moduleCode', moduleErrors.length > 0)"
                @input="onFieldInput('moduleCode', $event)"
            />
            <p
                v-if="row.moduleCodeRepeatedInFile"
                class="mt-1 text-xs text-amber-700 dark:text-amber-400"
            >
                {{
                    $t('syllabus.import_preview_module_code_repeated', {
                        count: String(row.moduleCodeOccurrencesInFile),
                    })
                }}
            </p>
        </td>
        <td class="px-2 py-2">
            <span :class="actionClass(syllabusAction)">{{ actionLabel(syllabusAction) }}</span>
            <ul v-if="syllabusErrors.length" class="mt-1 list-none space-y-1 text-xs text-destructive">
                <li v-for="(error, index) in syllabusErrors" :key="`syllabus-error-${index}`">{{ error }}</li>
            </ul>
        </td>
        <td class="px-2 py-2">
            <span :class="actionClass(moduleAction)">{{ actionLabel(moduleAction) }}</span>
            <ul v-if="moduleErrors.length" class="mt-1 list-none space-y-1 text-xs text-destructive">
                <li v-for="(error, index) in moduleErrors" :key="`module-error-${index}`">{{ error }}</li>
            </ul>
        </td>
    </tr>
</template>
