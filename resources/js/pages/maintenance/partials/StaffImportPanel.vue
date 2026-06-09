<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type { StaffImportPreview, StaffImportResult } from '@/types/staff-import';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

export type { StaffImportResult };

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];
const IMPORT_RESULT_AUTO_DISMISS_MS = 10_000;

const props = defineProps<{
    staffImportResult?: StaffImportResult | null;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const fileError = ref<string | null>(null);
const previewLoading = ref(false);
const preview = ref<StaffImportPreview | null>(null);
const previewError = ref<string | null>(null);
const importResultDismissed = ref(false);
let importResultDismissTimer: ReturnType<typeof setTimeout> | null = null;

const confirmForm = useForm<{ preview_token: string }>({
    preview_token: '',
});

const templateUrl = route('maintenance.staff-import.template');
const previewUrl = route('maintenance.staff-import.preview');
const processUrl = route('maintenance.staff-import.process');

const clearImportResultDismissTimer = (): void => {
    if (importResultDismissTimer !== null) {
        clearTimeout(importResultDismissTimer);
        importResultDismissTimer = null;
    }
};

const scheduleImportResultAutoDismiss = (): void => {
    clearImportResultDismissTimer();
    importResultDismissTimer = setTimeout(() => {
        importResultDismissed.value = true;
        importResultDismissTimer = null;
    }, IMPORT_RESULT_AUTO_DISMISS_MS);
};

onMounted(() => {
    if (props.staffImportResult != null) {
        importResultDismissed.value = false;
        scheduleImportResultAutoDismiss();
    }
});

watch(
    () => props.staffImportResult,
    (result) => {
        if (result != null) {
            importResultDismissed.value = false;
            scheduleImportResultAutoDismiss();
        } else {
            clearImportResultDismissTimer();
        }
    },
);

onUnmounted(() => {
    clearImportResultDismissTimer();
});

const hasImportResult = computed(
    (): boolean => props.staffImportResult != null && !importResultDismissed.value,
);

const dismissImportResult = (): void => {
    clearImportResultDismissTimer();
    importResultDismissed.value = true;
};

const canConfirmImport = computed((): boolean => {
    if (!preview.value) {
        return false;
    }

    const { summary } = preview.value;

    return summary.failed === 0 && summary.creates + summary.updates > 0;
});

const confirmBlockedMessage = computed((): string => {
    if (!preview.value) {
        return '';
    }

    const { summary } = preview.value;

    if (summary.failed > 0) {
        return trans('trans.maintenance_staff_import_preview_cannot_confirm');
    }

    if (summary.creates + summary.updates === 0) {
        return trans('trans.maintenance_staff_import_preview_no_rows');
    }

    return trans('trans.maintenance_staff_import_preview_can_confirm');
});

const previewSummaryLabel = computed((): string | null => {
    if (!preview.value) {
        return null;
    }

    const { summary } = preview.value;

    return trans('trans.maintenance_staff_import_preview_summary', {
        creates: String(summary.creates),
        updates: String(summary.updates),
        skipped: String(summary.skipped),
        failed: String(summary.failed),
    });
});

const actionLabel = (action: string): string => {
    const keys: Record<string, string> = {
        create: 'trans.maintenance_staff_import_preview_action_create',
        update: 'trans.maintenance_staff_import_preview_action_update',
        skip_empty: 'trans.maintenance_staff_import_preview_action_skip_empty',
        fail: 'trans.maintenance_staff_import_preview_action_fail',
    };

    return trans(keys[action] ?? keys.fail);
};

const actionClass = (action: string): string => {
    if (action === 'create' || action === 'update') {
        return 'text-green-700';
    }

    if (action === 'skip_empty') {
        return 'text-muted-foreground';
    }

    return 'text-destructive';
};

const isAcceptedFile = (file: File): boolean => {
    const name = file.name.toLowerCase();

    return ACCEPTED_EXTENSIONS.some((extension) => name.endsWith(extension));
};

const resetPreview = (): void => {
    preview.value = null;
    previewError.value = null;
    confirmForm.preview_token = '';
};

const cancelImport = (): void => {
    selectedFile.value = null;
    fileError.value = null;
    previewError.value = null;
    resetPreview();

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const onFileChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    selectedFile.value = file;
    fileError.value = null;
    resetPreview();

    if (file !== null && !isAcceptedFile(file)) {
        fileError.value = trans('trans.maintenance_staff_import_invalid_file_type');
        selectedFile.value = null;

        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
};

const runPreview = async (): Promise<void> => {
    if (!selectedFile.value || fileError.value) {
        return;
    }

    previewLoading.value = true;
    previewError.value = null;
    resetPreview();

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await customAxios('').post<StaffImportPreview>(previewUrl, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        preview.value = response.data;
        confirmForm.preview_token = response.data.previewToken;
    } catch (caught) {
        const responseData = (caught as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
            .response?.data;

        const message =
            responseData?.errors?.file?.[0] ??
            responseData?.message ??
            trans('trans.maintenance_staff_import_preview_failed');

        previewError.value = message;
        errorAlert(message);
    } finally {
        previewLoading.value = false;
    }
};

const submitImport = (): void => {
    if (!canConfirmImport.value || !confirmForm.preview_token) {
        return;
    }

    confirmForm.post(processUrl, {
        preserveScroll: true,
        onSuccess: () => {
            selectedFile.value = null;
            resetPreview();

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const formatPreviewErrors = (errors: Record<string, string[]> | null | undefined): string => {
    if (!errors) {
        return '';
    }

    return Object.values(errors).flat().join(' ');
};
</script>

<template>
    <div class="max-w-4xl space-y-6">
        <div class="space-y-2">
            <HeadingSmall
                :title="$t('trans.maintenance_staff_import_title')"
                :description="$t('trans.maintenance_staff_import_description')"
            />
        </div>

        <div class="flex flex-wrap gap-2">
            <a :href="templateUrl" class="inline-flex" target="_blank" rel="noopener noreferrer">
                <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                    {{ $t('trans.maintenance_staff_import_download_template') }}
                </BaseButton>
            </a>
        </div>

        <div class="space-y-4 rounded-lg border border-border p-4">
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase text-muted-foreground" for="staff-import-file">
                    {{ $t('trans.maintenance_staff_import_file_label') }}
                </label>
                <input
                    id="staff-import-file"
                    ref="fileInput"
                    type="file"
                    accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                    class="block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium"
                    @change="onFileChange"
                />
                <p class="text-xs text-muted-foreground">
                    {{ $t('trans.maintenance_staff_import_file_hint') }}
                </p>
                <p v-if="fileError" class="text-sm text-destructive">{{ fileError }}</p>
                <p v-if="previewError" class="text-sm text-destructive">{{ previewError }}</p>
            </div>

            <BaseButton
                type="button"
                :variant="ColorVariant.primary_outline"
                :size="ButtonSize.sm"
                :processing="previewLoading"
                :disabled="!selectedFile || previewLoading || Boolean(fileError)"
                @click="runPreview"
            >
                {{ $t('trans.maintenance_staff_import_preview') }}
            </BaseButton>
        </div>

        <div v-if="preview" class="space-y-4 rounded-lg border border-border p-4">
            <div>
                <h3 class="font-semibold">{{ $t('trans.maintenance_staff_import_preview_title') }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">{{ preview.fileName }}</p>
                <p v-if="previewSummaryLabel" class="mt-2 text-sm">{{ previewSummaryLabel }}</p>
                <p
                    class="mt-2 text-sm"
                    :class="canConfirmImport ? 'text-muted-foreground' : 'text-destructive'"
                >
                    {{ confirmBlockedMessage }}
                </p>
            </div>

            <div class="overflow-x-auto rounded-lg border border-border">
                <table class="j-table min-w-full">
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th class="j-th text-left">#</th>
                            <th class="j-th text-left">{{ $t('trans.employee_number') }}</th>
                            <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                            <th class="j-th text-left">{{ $t('trans.email') }}</th>
                            <th class="j-th text-left">{{ $tChoice('trans.department', 1) }}</th>
                            <th class="j-th text-left">{{ $t('trans.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <tr v-for="row in preview.rows" :key="row.rowNumber" class="j-tr">
                            <td class="j-td">{{ row.rowNumber }}</td>
                            <td class="j-td font-mono text-xs">{{ row.employeeNumber ?? '—' }}</td>
                            <td class="j-td">{{ row.fullName ?? '—' }}</td>
                            <td class="j-td text-sm">{{ row.email ?? '—' }}</td>
                            <td class="j-td text-sm">{{ row.department ?? '—' }}</td>
                            <td class="j-td align-top">
                                <span class="text-xs font-medium" :class="actionClass(row.action)">
                                    {{ actionLabel(row.action) }}
                                </span>
                                <p v-if="row.errors" class="mt-1 text-xs text-destructive">
                                    {{ formatPreviewErrors(row.errors) }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap gap-2">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.warning"
                    :size="ButtonSize.sm"
                    :disabled="confirmForm.processing"
                    @click="cancelImport"
                >
                    {{ $t('trans.cancel') }}
                </BaseButton>
                <BaseButton
                    type="button"
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.sm"
                    :processing="confirmForm.processing"
                    :disabled="!canConfirmImport || confirmForm.processing"
                    @click="submitImport"
                >
                    {{ $t('trans.maintenance_staff_import_confirm') }}
                </BaseButton>
            </div>
        </div>

        <div
            v-if="hasImportResult && staffImportResult"
            class="rounded-lg border border-border bg-muted/30 p-4 text-sm"
        >
            <div class="flex items-start justify-between gap-3">
                <h3 class="font-semibold">{{ $t('trans.maintenance_staff_import_result_title') }}</h3>
                <BaseButton
                    type="button"
                    :variant="ColorVariant.warning_outline"
                    :size="ButtonSize.sm"
                    @click="dismissImportResult"
                >
                    {{ $t('trans.close') }}
                </BaseButton>
            </div>
            <ul class="mt-2 space-y-1 text-muted-foreground">
                <li>
                    {{ $t('trans.maintenance_staff_import_result_total', { count: String(staffImportResult.rowsTotal) }) }}
                </li>
                <li>
                    {{ $t('trans.maintenance_staff_import_result_succeeded', { count: String(staffImportResult.rowsSucceeded) }) }}
                </li>
                <li>
                    {{ $t('trans.maintenance_staff_import_result_failed', { count: String(staffImportResult.rowsFailed) }) }}
                </li>
                <li>
                    {{ $t('trans.maintenance_staff_import_result_skipped', { count: String(staffImportResult.rowsSkipped) }) }}
                </li>
            </ul>
        </div>
    </div>
</template>
