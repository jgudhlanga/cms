<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import Empty from '@/components/core/util/Empty.vue';
import { useStudentIdCardImport } from '@/composables/students/useStudentIdCardImport';
import { useStudentIdCardImportSelection } from '@/composables/students/useStudentIdCardImportSelection';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { StudentIdCardImportFilter } from '@/types/student-id-card-import';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const fileInput = ref<HTMLInputElement | null>(null);
const fileFormKey = ref(0);

const {
    fileError,
    previewLoading,
    preview,
    previewError,
    processLoading,
    processError,
    activeFilter,
    templateUrl,
    filteredPreviewRows,
    previewSummaryLabel,
    canRunPreview,
    cancelImport,
    onFileChange,
    runPreview,
    confirmImport,
    statusLabel,
    statusClass,
    checkboxSkipTitle,
} = useStudentIdCardImport();

const {
    selectAllModel,
    selectedCount,
    selectedRows,
    isRowSelected,
    setRowSelected,
    selectRowNumbers,
    clearSelection,
} = useStudentIdCardImportSelection(filteredPreviewRows);

watch(preview, (value) => {
    if (value === null) {
        clearSelection();

        return;
    }

    selectRowNumbers(value.rows.filter((row) => row.isSelectable).map((row) => row.rowNumber));
});

const importButtonLabel = computed(() =>
    trans('trans.student_id_card_import_confirm', {
        count: String(selectedCount.value),
    }),
);

const filters: Array<{ id: StudentIdCardImportFilter; labelKey: string }> = [
    { id: 'all', labelKey: 'trans.student_id_card_import_filter_all' },
    { id: 'ready', labelKey: 'trans.student_id_card_import_filter_ready' },
    { id: 'errors', labelKey: 'trans.student_id_card_import_filter_errors' },
];

const displayValue = (value: string | number | null | undefined): string => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
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

const onSelectAllChange = (value: boolean): void => {
    selectAllModel.value = value;
};

const onRowSelectChange = (rowNumber: number, value: boolean): void => {
    setRowSelected(rowNumber, value);
};
</script>

<template>
    <div class="w-full min-w-0 space-y-4">
        <ol class="flex flex-wrap gap-3 text-sm">
            <li
                class="rounded-full px-3 py-1 font-medium"
                :class="preview ? 'bg-muted text-muted-foreground' : 'bg-primary/10 text-primary'"
            >
                1. {{ $t('trans.student_id_card_import_step_upload') }}
            </li>
            <li
                class="rounded-full px-3 py-1 font-medium"
                :class="preview ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
            >
                2. {{ $t('trans.student_id_card_import_step_preview') }}
            </li>
        </ol>

        <BaseAlert
            :type="TypeVariant.info"
            :description="$t('trans.student_id_card_import_description')"
        />

        <ul class="list-decimal space-y-1 pl-5 text-sm text-muted-foreground">
            <li>{{ $t('trans.student_id_card_import_rule_1') }}</li>
            <li>{{ $t('trans.student_id_card_import_rule_2') }}</li>
            <li>{{ $t('trans.student_id_card_import_rule_3') }}</li>
        </ul>

        <div
            v-if="!preview"
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
                    :title="$t('trans.student_id_card_import_download_template')"
                />
            </a>

            <div :key="fileFormKey" class="min-w-0 flex-1 space-y-2 md:max-w-xl">
                <label class="text-xs font-bold uppercase text-muted-foreground" for="id-card-import-file">
                    {{ $t('trans.student_id_card_import_select_file') }}
                </label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input
                        id="id-card-import-file"
                        ref="fileInput"
                        type="file"
                        accept=".csv,text/csv"
                        class="block min-w-0 flex-1 text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium"
                        :disabled="processLoading"
                        @change="onFileChange($event, fileInput)"
                    >
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.sm"
                        class="shrink-0"
                        :processing="previewLoading"
                        :disabled="!canRunPreview"
                        :title="$t('trans.student_id_card_import_preview')"
                        @click="handlePreview"
                    />
                </div>
                <p v-if="fileError" class="text-sm text-destructive">{{ fileError }}</p>
                <p v-if="previewError" class="text-sm text-destructive">{{ previewError }}</p>
            </div>
        </div>

        <div v-if="preview" class="space-y-4 rounded-lg border border-border p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-muted-foreground">{{ previewSummaryLabel }}</p>
                <div class="flex flex-wrap gap-2">
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.primary"
                        :size="ButtonSize.sm"
                        :processing="processLoading"
                        :disabled="selectedCount === 0 || processLoading"
                        :title="importButtonLabel"
                        @click="confirmImport(selectedRows)"
                    />
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.sm"
                        :title="$t('trans.student_id_card_import_clear')"
                        @click="handleCancel"
                    />
                </div>
            </div>
            <p v-if="processError" class="text-sm text-destructive">{{ processError }}</p>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in filters"
                    :key="filter.id"
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="activeFilter === filter.id ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                    @click="activeFilter = filter.id"
                >
                    {{ $t(filter.labelKey) }}
                </button>
            </div>

            <Empty
                v-if="filteredPreviewRows.length === 0"
                :message="$t('trans.student_id_card_import_filter_all')"
            />
            <div v-else class="overflow-x-auto rounded-md border border-border">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2">
                                <BaseCheckbox
                                    :model-value="selectAllModel"
                                    @update:model-value="onSelectAllChange"
                                />
                            </th>
                            <th class="px-3 py-2">#</th>
                            <th class="px-3 py-2">{{ $t('trans.student_number') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.id_number') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.passport_number') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.name') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.student_id_card_photo') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.student_id_card_status') }}</th>
                            <th class="px-3 py-2">{{ $t('trans.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in filteredPreviewRows"
                            :key="row.rowNumber"
                            :class="{ 'bg-muted/20': !row.isSelectable }"
                        >
                            <td class="px-3 py-2">
                                <BaseCheckbox
                                    :model-value="isRowSelected(row.rowNumber)"
                                    :disabled="!row.isSelectable"
                                    :title="checkboxSkipTitle(row)"
                                    @update:model-value="(value) => onRowSelectChange(row.rowNumber, value)"
                                />
                            </td>
                            <td class="px-3 py-2">{{ row.rowNumber }}</td>
                            <td class="px-3 py-2">{{ displayValue(row.storedStudentNumber ?? row.studentNumber) }}</td>
                            <td class="px-3 py-2">{{ displayValue(row.storedIdNumber ?? row.idNumber) }}</td>
                            <td class="px-3 py-2">{{ displayValue(row.storedPassportNumber ?? row.passportNumber) }}</td>
                            <td class="px-3 py-2">
                                <p>{{ displayValue(row.studentName) }}</p>
                                <p v-if="row.identityType" class="text-xs text-muted-foreground">{{ row.identityType }}</p>
                            </td>
                            <td class="px-3 py-2">
                                <img
                                    v-if="row.photoThumbUrl"
                                    :src="row.photoThumbUrl"
                                    alt=""
                                    class="size-10 rounded object-cover"
                                >
                                <span v-else>
                                    {{ row.hasPhoto ? $t('trans.student_id_card_import_photo_yes') : $t('trans.student_id_card_import_photo_no') }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ displayValue(row.existingRequestStatus) }}</td>
                            <td class="px-3 py-2">
                                <p class="font-medium" :class="statusClass(row)">{{ statusLabel(row) }}</p>
                                <p
                                    v-for="(error, index) in row.errors"
                                    :key="`e-${index}`"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ error }}
                                </p>
                                <p
                                    v-for="(warning, index) in row.warnings"
                                    :key="`w-${index}`"
                                    class="mt-1 text-xs text-amber-700"
                                >
                                    {{ warning }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
