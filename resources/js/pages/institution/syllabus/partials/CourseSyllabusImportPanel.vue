<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { Badge } from '@/components/ui/badge';
import { useCourseSyllabusImport } from '@/composables/institution/useCourseSyllabusImport';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import CourseSyllabusImportPreviewRow from '@/pages/institution/syllabus/partials/CourseSyllabusImportPreviewRow.vue';
import type { SyllabusImportResult } from '@/types/syllabus-import';
import { ref } from 'vue';

const props = defineProps<{
    institutionDepartmentId: string;
    syllabusImportResult?: SyllabusImportResult | null;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const fileFormKey = ref(0);

const {
    selectedFile,
    fileError,
    previewLoading,
    preview,
    previewError,
    confirmForm,
    templateUrl,
    previewRows,
    previewSummaryLabel,
    canConfirmImport,
    confirmBlockedMessage,
    cancelImport,
    onFileChange,
    runPreview,
    submitImport,
    updateRowCorrection,
    removeRow,
    getCorrection,
    actionLabel,
    actionClass,
} = useCourseSyllabusImport(props.institutionDepartmentId);

const resetFileForm = (): void => {
    fileFormKey.value++;
    fileInput.value = null;
};

const handleCancel = (): void => {
    cancelImport();
    resetFileForm();
};

const handlePreview = (): void => {
    void runPreview();
};

const handleConfirm = (): void => {
    submitImport(resetFileForm);
};
</script>

<template>
    <div class="w-full min-w-0 space-y-4">
        <div
            class="flex flex-col gap-4 rounded-lg border border-border p-3 md:flex-row md:items-end md:justify-between"
        >
            <a :href="templateUrl" class="inline-flex shrink-0" target="_blank" rel="noopener noreferrer">
                <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                    {{ $t('syllabus.import_download_template') }}
                </BaseButton>
            </a>

            <div :key="fileFormKey" class="min-w-0 flex-1 space-y-2 md:max-w-xl">
                <label class="text-xs font-bold uppercase text-muted-foreground" for="syllabus-import-file">
                    {{ $t('syllabus.import_file_label') }}
                </label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input
                        id="syllabus-import-file"
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                        class="block min-w-0 flex-1 text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium"
                        @change="onFileChange($event, fileInput)"
                    />
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.sm"
                        class="shrink-0"
                        :processing="previewLoading"
                        :disabled="!selectedFile || previewLoading || Boolean(fileError)"
                        @click="handlePreview"
                    >
                        {{ $t('syllabus.import_preview') }}
                    </BaseButton>
                </div>
                <p class="text-xs text-muted-foreground">{{ $t('syllabus.import_file_hint') }}</p>
                <p v-if="fileError" class="text-sm text-destructive">{{ fileError }}</p>
                <p v-if="previewError" class="text-sm text-destructive">{{ previewError }}</p>
            </div>
        </div>

        <div v-if="syllabusImportResult" class="rounded-lg border border-border bg-muted/30 p-4">
            <h3 class="text-sm font-semibold">{{ $t('syllabus.import_result_title') }}</h3>
            <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
                <li>{{ $t('syllabus.import_result_total', { count: syllabusImportResult.rowsTotal }) }}</li>
                <li>{{ $t('syllabus.import_result_succeeded', { count: syllabusImportResult.rowsSucceeded }) }}</li>
                <li>{{ $t('syllabus.import_result_failed', { count: syllabusImportResult.rowsFailed }) }}</li>
                <li>{{ $t('syllabus.import_result_skipped', { count: syllabusImportResult.rowsSkipped }) }}</li>
            </ul>
        </div>

        <div v-if="preview" class="space-y-4 rounded-lg border border-border p-4">
            <div class="space-y-2">
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <h3 class="text-sm font-semibold">{{ $t('syllabus.import_preview_title') }}</h3>
                    <Badge variant="outline" class="max-w-xs truncate font-normal" :title="preview.fileName">
                        {{ preview.fileName }}
                    </Badge>
                </div>

                <p v-if="previewSummaryLabel" class="text-sm text-muted-foreground">
                    {{ previewSummaryLabel }}
                </p>

                <div
                    v-if="preview.fileStats"
                    class="overflow-x-auto rounded-md border border-border bg-muted/20"
                >
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs uppercase text-muted-foreground">
                                <th class="px-3 py-2">{{ $t('syllabus.import_preview_file_stats_metric') }}</th>
                                <th class="px-3 py-2 text-right">
                                    {{ $t('syllabus.import_preview_file_stats_count') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <td class="px-3 py-2">{{ $t('syllabus.import_preview_file_stats_total_rows') }}</td>
                                <td class="px-3 py-2 text-right font-medium">{{ preview.fileStats.totalRows }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-3 py-2">
                                    {{ $t('syllabus.import_preview_file_stats_unique_course_codes') }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium">
                                    {{ preview.fileStats.uniqueCourseCodes }}
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-3 py-2">
                                    {{ $t('syllabus.import_preview_file_stats_unique_module_codes') }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium">
                                    {{ preview.fileStats.uniqueModuleCodes }}
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-3 py-2">
                                    {{ $t('syllabus.import_preview_file_stats_unique_module_records') }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium">
                                    {{ preview.fileStats.uniqueModuleRecords }}
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-3 py-2">
                                    {{ $t('syllabus.import_preview_file_stats_duplicate_groups') }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium">
                                    {{ preview.fileStats.duplicateModuleCodeGroups }}
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-3 py-2">
                                    {{ $t('syllabus.import_preview_file_stats_extra_duplicate_rows') }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium">
                                    {{ preview.fileStats.extraRowsFromDuplicateModuleCodes }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-if="preview.fileStats.duplicateModuleCodeGroups > 0"
                        class="border-t px-3 py-2 text-xs text-muted-foreground"
                    >
                        {{
                            $t('syllabus.import_preview_file_stats_duplicate_notice', {
                                groups: String(preview.fileStats.duplicateModuleCodeGroups),
                                extra: String(preview.fileStats.extraRowsFromDuplicateModuleCodes),
                            })
                        }}
                    </p>
                </div>

                <p
                    class="text-sm"
                    :class="canConfirmImport ? 'text-muted-foreground' : 'text-destructive'"
                >
                    {{ confirmBlockedMessage }}
                </p>
            </div>

            <datalist id="syllabus-import-levels">
                <option v-for="level in preview.lookups.levels" :key="level" :value="level" />
            </datalist>
            <datalist id="syllabus-import-courses">
                <option v-for="course in preview.lookups.courses" :key="course" :value="course" />
            </datalist>
            <datalist id="syllabus-import-semesters">
                <option v-for="semester in preview.lookups.semesters" :key="semester" :value="semester" />
            </datalist>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-muted-foreground">
                            <th class="px-2 py-2">#</th>
                            <th class="px-2 py-2">
                                {{ $t('syllabus.level') }} / {{ $t('syllabus.title') }} / {{ $t('syllabus.code') }}
                            </th>
                            <th class="px-2 py-2">{{ $tChoice('syllabus.semester', 1) }}</th>
                            <th class="px-2 py-2">{{ $t('syllabus.all_semesters') }}</th>
                            <th class="px-2 py-2">{{ $t('syllabus.title') }} / {{ $t('syllabus.code') }}</th>
                            <th class="px-2 py-2">{{ $t('syllabus.import_preview_syllabus') }}</th>
                            <th class="px-2 py-2">{{ $t('syllabus.import_preview_module') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <CourseSyllabusImportPreviewRow
                            v-for="row in previewRows"
                            :key="row.rowNumber"
                            :row="row"
                            :lookups="preview.lookups"
                            :correction="getCorrection(row)"
                            :action-label="actionLabel"
                            :action-class="actionClass"
                            @update:correction="updateRowCorrection(row.rowNumber, $event)"
                            @remove="removeRow(row.rowNumber)"
                        />
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap gap-2">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.sm"
                    :processing="confirmForm.processing"
                    :disabled="!canConfirmImport || confirmForm.processing"
                    @click="handleConfirm"
                >
                    {{ $t('syllabus.import_confirm') }}
                </BaseButton>
                <BaseButton
                    type="button"
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.sm"
                    :disabled="confirmForm.processing"
                    @click="handleCancel"
                >
                    {{ $t('syllabus.import_cancel') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
