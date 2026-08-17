<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import Empty from '@/components/core/util/Empty.vue';
import { useHostelOccupantImport } from '@/composables/hms/useHostelOccupantImport';
import { useHostelOccupantImportSelection } from '@/composables/hms/useHostelOccupantImportSelection';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { HostelOccupantImportFilter } from '@/types/hostel-occupant-import';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    hostelId: number;
    hostelName: string;
    canConfirmPayments: boolean;
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
    activeFilter,
    templateUrl,
    filteredPreviewRows,
    previewSummaryLabel,
    canRunPreview,
    cancelImport,
    onFileChange,
    runPreview,
    confirmImport,
    paymentLabel,
    paymentClass,
    statusLabel,
    statusClass,
    checkboxSkipTitle,
} = useHostelOccupantImport(props.hostelId, props.canConfirmPayments);

const {
    selectAllModel,
    selectedCount,
    selectedRows,
    isRowSelected,
    setRowSelected,
    selectRowNumbers,
    clearSelection,
} = useHostelOccupantImportSelection(filteredPreviewRows);

watch(preview, (value) => {
    if (value === null) {
        clearSelection();

        return;
    }

    selectRowNumbers(
        value.rows.filter((row) => row.isSelectable).map((row) => row.rowNumber),
    );
});

const importButtonLabel = computed(() =>
    trans('hms.import_occupants_confirm', {
        count: String(selectedCount.value),
    }),
);

const filters: Array<{ id: HostelOccupantImportFilter; labelKey: string }> = [
    { id: 'all', labelKey: 'hms.import_occupants_filter_all' },
    { id: 'ready', labelKey: 'hms.import_occupants_filter_ready' },
    { id: 'assumed_paid', labelKey: 'hms.import_occupants_filter_assumed_paid' },
    { id: 'errors', labelKey: 'hms.import_occupants_filter_errors' },
];

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

const handleImport = (): void => {
    confirmImport(selectedRows.value);
};

const onSelectAllChange = (value: boolean): void => {
    selectAllModel.value = value;
};

const onRowSelectChange = (rowNumber: number, value: boolean): void => {
    setRowSelected(rowNumber, value);
};

const displayValue = (value: string | number | null | undefined): string => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
};

const fundingLabel = (row: { isSponsored: boolean; isApprentice: boolean }): string => {
    if (row.isSponsored) {
        return trans('hms.import_occupants_funding_sponsored');
    }

    if (row.isApprentice) {
        return trans('hms.import_occupants_funding_apprentice');
    }

    return '—';
};
</script>

<template>
    <div class="w-full min-w-0 space-y-4">
        <BaseAlert
            :type="TypeVariant.info"
            :description="$t('hms.import_occupants_description')"
        />

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
                    {{ $t('hms.import_occupants_download_template') }}
                </BaseButton>
            </a>

            <div :key="fileFormKey" class="min-w-0 flex-1 space-y-2 md:max-w-xl">
                <label class="text-xs font-bold uppercase text-muted-foreground" for="hostel-occupant-import-file">
                    {{ $t('hms.import_occupants_select_file') }}
                </label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input
                        id="hostel-occupant-import-file"
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
                        {{ $t('hms.import_occupants_preview') }}
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
                    <h3 class="text-sm font-semibold">{{ $t('hms.import_occupants_preview') }}</h3>
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
                            :disabled="processLoading || !canConfirmPayments"
                            :title="canConfirmPayments ? undefined : $t('hms.cannot_confirm_hostel_payments')"
                            @click="handleImport"
                        >
                            {{ importButtonLabel }}
                        </BaseButton>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.secondary"
                            :size="ButtonSize.sm"
                            :disabled="processLoading"
                            @click="clearSelection"
                        >
                            {{ $t('trans.clear_selection') }}
                        </BaseButton>
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

            <p v-if="!canConfirmPayments" class="text-sm text-muted-foreground">
                {{ $t('hms.cannot_confirm_hostel_payments') }}
            </p>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in filters"
                    :key="filter.id"
                    type="button"
                    class="rounded-full border px-3 py-1 text-xs font-semibold"
                    :class="
                        activeFilter === filter.id
                            ? 'border-foreground bg-foreground text-background'
                            : 'border-border bg-card text-muted-foreground'
                    "
                    @click="activeFilter = filter.id"
                >
                    {{ $t(filter.labelKey) }}
                </button>
            </div>

            <Empty
                v-if="filteredPreviewRows.length === 0"
                :description="$t('hms.import_occupants_no_rows')"
            />

            <div v-else class="overflow-x-auto rounded-md border border-border">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/30 text-left text-xs uppercase text-muted-foreground">
                            <th class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <BaseCheckbox
                                        input-id="hostel-occupant-import-select-all"
                                        :aria-label="$t('hms.import_occupants_column_select')"
                                        :model-value="selectAllModel"
                                        :disabled="filteredPreviewRows.every((row) => !row.isSelectable) || processLoading"
                                        @update:model-value="onSelectAllChange"
                                    />
                                    <span>{{ $t('hms.import_occupants_column_select') }}</span>
                                </div>
                            </th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_student_number') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_id_number') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_passport_number') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_student') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_disability') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_hostel') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_floor') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_room') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_section') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_funding') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_payment') }}</th>
                            <th class="px-3 py-2">{{ $t('hms.import_occupants_column_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in filteredPreviewRows"
                            :key="row.rowNumber"
                            class="border-b align-top"
                            :class="{ 'bg-muted/20': !row.isSelectable }"
                        >
                            <td class="px-3 py-2">
                                <BaseCheckbox
                                    :input-id="`hostel-occupant-import-row-${row.rowNumber}`"
                                    :aria-label="$t('hms.import_occupants_column_select')"
                                    :model-value="isRowSelected(row.rowNumber)"
                                    :disabled="!row.isSelectable || processLoading"
                                    :title="checkboxSkipTitle(row)"
                                    @update:model-value="(value) => onRowSelectChange(row.rowNumber, value)"
                                />
                            </td>
                            <td class="px-3 py-2 font-mono">{{ displayValue(row.storedStudentNumber ?? row.studentNumber) }}</td>
                            <td class="px-3 py-2 font-mono">{{ displayValue(row.storedIdNumber ?? row.idNumber) }}</td>
                            <td class="px-3 py-2 font-mono">{{ displayValue(row.storedPassportNumber ?? row.passportNumber) }}</td>
                            <td class="px-3 py-2">{{ displayValue(row.studentName) }}</td>
                            <td class="px-3 py-2 capitalize">{{ displayValue(row.disability) }}</td>
                            <td class="px-3 py-2">{{ displayValue(row.hostel) }}</td>
                            <td class="px-3 py-2">{{ displayValue(row.resolvedFloor ?? row.floor) }}</td>
                            <td class="px-3 py-2">{{ displayValue(row.resolvedRoom ?? row.room) }}</td>
                            <td class="px-3 py-2">{{ displayValue(row.resolvedSection ?? row.section) }}</td>
                            <td class="px-3 py-2">
                                <p
                                    class="font-medium"
                                    :class="row.isSponsored || row.isApprentice ? 'text-violet-700' : 'text-muted-foreground'"
                                >
                                    {{ fundingLabel(row) }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                <p class="font-medium" :class="paymentClass(row.paymentSource)">
                                    {{ paymentLabel(row.paymentSource) }}
                                </p>
                                <p
                                    v-for="(warning, index) in row.warnings"
                                    :key="`${row.rowNumber}-warning-${index}`"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ warning }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                <p class="font-medium" :class="statusClass(row)">
                                    {{ statusLabel(row) }}
                                </p>
                                <p
                                    v-for="(error, index) in row.errors"
                                    :key="`${row.rowNumber}-error-${index}`"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ error }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
