<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton, IconButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useSponsoredStudentImport } from '@/composables/maintenance/useSponsoredStudentImport';
import { useSponsoredStudentImportSelection } from '@/composables/maintenance/useSponsoredStudentImportSelection';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { IconName, icons } from '@/lib/icons';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    calendarYear: number;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const fileFormKey = ref(0);

const {
    fileError,
    previewLoading,
    preview,
    previewError,
    processLoading,
    processError,
    templateUrl,
    previewRows,
    previewSummaryLabel,
    canRunPreview,
    cancelImport,
    onFileChange,
    runPreview,
    removePreviewRow,
    confirmMoveToFinalClass,
    checkboxSkipTitle,
    statusLabel,
    statusClass,
    classListStatusLabel,
    classListStatusClass,
    actionLabel,
    actionClass,
} = useSponsoredStudentImport(props.calendarYear);

const {
    selectAllModel,
    selectedCount,
    selectedRows,
    isRowSelected,
    setRowSelected,
    clearSelection,
    pruneSelectionToVisibleRows,
} = useSponsoredStudentImportSelection(previewRows);

watch(previewRows, () => {
    pruneSelectionToVisibleRows();
});

const moveButtonLabel = computed(() =>
    trans('trans.maintenance_sponsored_students_import_move_to_final_class', {
        count: String(selectedCount.value),
    }),
);

const onSelectAllChange = (value: boolean): void => {
    selectAllModel.value = value;
};

const onRowSelectChange = (rowNumber: number, value: boolean): void => {
    setRowSelected(rowNumber, value);
};

const resetFileForm = (): void => {
    fileFormKey.value++;
    fileInput.value = null;
};

const handleCancel = (): void => {
    clearSelection();
    cancelImport();
    resetFileForm();
};

const handlePreview = (): void => {
    clearSelection();
    void runPreview();
};

const handleMoveToFinalClass = (): void => {
    confirmMoveToFinalClass(selectedRows.value, clearSelection);
};
</script>

<template>
    <div class="w-full min-w-0 space-y-4">
        <BaseAlert
            :type="TypeVariant.info"
            :description="$t('trans.maintenance_sponsored_students_page_description')"
        />

        <div class="max-w-xs">
            <BaseInput
                :model-value="String(calendarYear)"
                name="calendar_year"
                :label="$t('trans.maintenance_sponsored_students_import_calendar_year')"
                readonly
                disabled
            />
        </div>

        <div
            class="flex flex-col gap-4 rounded-lg border border-border p-3 md:flex-row md:items-end md:justify-between"
        >
            <a
                :href="templateUrl"
                class="inline-flex shrink-0"
                target="_blank"
                rel="noopener noreferrer"
            >
                <BaseButton
                    type="button"
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.sm"
                >
                    {{ $t('trans.maintenance_sponsored_students_import_download_template') }}
                </BaseButton>
            </a>

            <div :key="fileFormKey" class="min-w-0 flex-1 space-y-2 md:max-w-xl">
                <label class="text-xs font-bold uppercase text-muted-foreground" for="sponsored-students-import-file">
                    {{ $t('trans.maintenance_sponsored_students_import_select_file') }}
                </label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input
                        id="sponsored-students-import-file"
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                        class="block min-w-0 flex-1 text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium"
                        :disabled="processLoading"
                        @change="onFileChange($event, fileInput)"
                    />
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.sm"
                        class="shrink-0"
                        :processing="previewLoading"
                        :disabled="!canRunPreview"
                        @click="handlePreview"
                    >
                        {{ $t('trans.maintenance_sponsored_students_import_preview') }}
                    </BaseButton>
                </div>
                <p v-if="fileError" class="text-sm text-destructive">{{ fileError }}</p>
                <p v-if="previewError" class="text-sm text-destructive">{{ previewError }}</p>
                <p v-if="processError" class="text-sm text-destructive">{{ processError }}</p>
            </div>
        </div>

        <div v-if="preview" class="space-y-4 rounded-lg border border-border p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="space-y-1">
                    <h3 class="text-sm font-semibold">{{ $t('trans.maintenance_sponsored_students_import_preview') }}</h3>
                    <p v-if="previewSummaryLabel" class="text-sm text-muted-foreground">
                        {{ previewSummaryLabel }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <template v-if="selectedCount > 0">
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary"
                            :size="ButtonSize.sm"
                            :processing="processLoading"
                            :disabled="processLoading"
                            @click="handleMoveToFinalClass"
                        >
                            {{ moveButtonLabel }}
                        </BaseButton>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.secondary"
                            :size="ButtonSize.sm"
                            :disabled="processLoading"
                            :title="trans('trans.clear_selection')"
                            @click="clearSelection"
                        />
                    </template>
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.shade_outline"
                        :size="ButtonSize.sm"
                        :disabled="processLoading"
                        @click="handleCancel"
                    >
                        {{ $t('trans.cancel') }}
                    </BaseButton>
                </div>
            </div>

            <Empty
                v-if="previewRows.length === 0"
                :description="$t('trans.maintenance_sponsored_students_import_no_rows')"
            />

            <div v-else class="overflow-x-auto rounded-md border border-border">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/30 text-left text-xs uppercase text-muted-foreground">
                            <th class="px-3 py-2">
                                <BaseCheckbox
                                    input-id="sponsored-students-import-select-all"
                                    label=""
                                    :model-value="selectAllModel"
                                    :disabled="previewRows.every((row) => !row.isSelectable) || processLoading"
                                    @update:model-value="onSelectAllChange"
                                />
                            </th>
                            <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_student_number') }}</th>
                            <th class="px-3 py-2">{{ $tChoice('trans.name', 1) }}</th>
                            <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_identity') }}</th>
                            <th class="px-3 py-2">{{ $tChoice('trans.department', 1) }}</th>
                            <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_level') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_course') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.class_list') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_sponsor') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_match_status') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.maintenance_sponsored_students_import_column_action') }}</th>
                            <th class="px-3 py-2 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in previewRows"
                            :key="row.rowNumber"
                            class="border-b align-top"
                            :class="{ 'bg-muted/20': !row.isSelectable }"
                        >
                            <td class="px-3 py-2">
                                <BaseCheckbox
                                    :input-id="`sponsored-students-import-row-${row.rowNumber}`"
                                    label=""
                                    :model-value="isRowSelected(row.rowNumber)"
                                    :disabled="!row.isSelectable || processLoading"
                                    :title="checkboxSkipTitle(row)"
                                    @update:model-value="(value: boolean) => onRowSelectChange(row.rowNumber, value)"
                                />
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono">{{ row.studentNumber ?? '—' }}</span>
                                    <component
                                        v-if="row.status === 'found' && row.matchedBy === 'student_number'"
                                        :is="icons[IconName.check]"
                                        class="h-4 w-4 shrink-0 text-green-600"
                                        aria-hidden="true"
                                    />
                                </div>
                            </td>
                            <td class="px-3 py-2">{{ row.studentName ?? '—' }}</td>
                            <td class="px-3 py-2 font-mono">{{ row.identityNumber ?? '—' }}</td>
                            <td class="px-3 py-2">{{ row.departmentCode ?? '—' }}</td>
                            <td class="px-3 py-2">{{ row.level ?? '—' }}</td>
                            <td class="px-3 py-2">{{ row.course ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span :class="classListStatusClass(row.classListStatus)">
                                    {{ classListStatusLabel(row.classListStatus) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div>{{ row.sponsor ?? '—' }}</div>
                                <p
                                    v-if="row.isAlreadySponsored && row.existingSponsor"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ $t('trans.maintenance_sponsored_students_import_column_existing_sponsor') }}:
                                    {{ row.existingSponsor }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                <span :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span>
                                <p
                                    v-for="(error, index) in row.errors"
                                    :key="`${row.rowNumber}-error-${index}`"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ error }}
                                </p>
                                <p
                                    v-for="(reason, index) in row.skipReasons"
                                    :key="`${row.rowNumber}-skip-${index}`"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ reason }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                <span :class="actionClass(row)">{{ actionLabel(row) }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <IconButton
                                    :icon="IconName.trash"
                                    tone="header-danger"
                                    :aria-label="trans('trans.maintenance_sponsored_students_import_remove_row')"
                                    :disabled="processLoading"
                                    @click="removePreviewRow(row.rowNumber)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
