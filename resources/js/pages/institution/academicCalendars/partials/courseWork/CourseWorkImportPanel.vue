<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import Empty from '@/components/core/util/Empty.vue';
import { useCourseWorkClassMarksheet } from '@/composables/academicCalendars/useCourseWorkClassMarksheet';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type { CourseWorkImportPreview } from '@/types/course-work';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

export interface CourseWorkImportResult {
    ingestRunId: number;
    importLogId: number;
    rowsTotal: number;
    rowsSucceeded: number;
    rowsFailed: number;
    rowsSkipped: number;
}

const ACCEPTED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];

const props = defineProps<{
    classConfigId: number;
    classConfigQuery: Record<string, string>;
    departmentId: number;
    calendarYear: string;
    canImportCourseWork: boolean;
    courseWorkImportTemplateUrl: (moduleId: number) => string;
    courseWorkImportPreviewUrl: string;
    courseWorkImportProcessUrl: string;
    courseWorkImportResult?: CourseWorkImportResult | null;
}>();

const {
    selectedModuleId,
    moduleOptions,
    loading,
    error,
    loadTree,
} = useCourseWorkClassMarksheet({ classConfigId: props.classConfigId });

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const fileError = ref<string | null>(null);
const previewLoading = ref(false);
const preview = ref<CourseWorkImportPreview | null>(null);
const previewError = ref<string | null>(null);

const confirmForm = useForm<{
    module: number | null;
    preview_token: string;
}>({
    module: null,
    preview_token: '',
});

watch(selectedModuleId, (value) => {
    confirmForm.module = value;
    resetPreview();
});

const templateUrl = computed((): string | null =>
    selectedModuleId.value ? props.courseWorkImportTemplateUrl(selectedModuleId.value) : null,
);

const hasImportResult = computed((): boolean => props.courseWorkImportResult != null);

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
        return trans('academic_calendar.course_work_import_preview_cannot_confirm');
    }

    if (summary.creates + summary.updates === 0) {
        return trans('academic_calendar.course_work_import_preview_no_marks');
    }

    return trans('academic_calendar.course_work_import_preview_can_confirm');
});

const previewSummaryLabel = computed((): string | null => {
    if (!preview.value) {
        return null;
    }

    const { summary } = preview.value;

    return trans('academic_calendar.course_work_import_preview_summary', {
        creates: String(summary.creates),
        updates: String(summary.updates),
        skipped: String(summary.skipped),
        failed: String(summary.failed),
    });
});

const actionLabel = (action: string): string => {
    const keys: Record<string, string> = {
        create: 'academic_calendar.course_work_import_preview_action_create',
        update: 'academic_calendar.course_work_import_preview_action_update',
        skip_empty: 'academic_calendar.course_work_import_preview_action_skip_empty',
        skip_duplicate: 'academic_calendar.course_work_import_preview_action_skip_duplicate',
        fail: 'academic_calendar.course_work_import_preview_action_fail',
    };

    return trans(keys[action] ?? keys.fail);
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
        fileError.value = trans('academic_calendar.course_work_import_invalid_file_type');
        selectedFile.value = null;

        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
};

const runPreview = async (): Promise<void> => {
    if (!selectedModuleId.value || !selectedFile.value || fileError.value) {
        return;
    }

    previewLoading.value = true;
    previewError.value = null;
    resetPreview();

    const formData = new FormData();
    formData.append('module', String(selectedModuleId.value));
    formData.append('file', selectedFile.value);

    try {
        const response = await customAxios('').post<CourseWorkImportPreview>(
            props.courseWorkImportPreviewUrl,
            formData,
            {
                headers: { 'Content-Type': 'multipart/form-data' },
            },
        );

        preview.value = response.data;
        confirmForm.preview_token = response.data.previewToken;
        confirmForm.module = selectedModuleId.value;
    } catch (caught) {
        const responseData = (caught as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
            .response?.data;

        const message =
            responseData?.errors?.file?.[0] ??
            responseData?.message ??
            trans('academic_calendar.course_work_import_preview_failed');

        previewError.value = message;
        errorAlert(message);
    } finally {
        previewLoading.value = false;
    }
};

const submitImport = (): void => {
    if (!canConfirmImport.value || !confirmForm.preview_token || !confirmForm.module) {
        return;
    }

    confirmForm.post(props.courseWorkImportProcessUrl, {
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

onMounted(() => {
    void loadTree();
});
</script>

<template>
    <section class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">{{ $t('academic_calendar.course_work_import_title') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ $t('academic_calendar.course_work_import_description') }}
            </p>
        </div>

        <p v-if="loading" class="text-sm text-muted-foreground">{{ $t('academic_calendar.course_work_loading') }}</p>
        <p v-else-if="error" class="text-sm text-destructive">{{ $t(error) }}</p>

        <Empty
            v-else-if="moduleOptions.length === 0"
            :message="$t('academic_calendar.course_work_no_modules')"
        />

        <template v-else>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:gap-4">
                <div class="min-w-0 flex-1 space-y-1">
                    <label class="text-xs font-bold uppercase text-muted-foreground" for="course-work-import-module">
                        {{ $t('academic_calendar.course_work_marksheet_module') }}
                    </label>
                    <select
                        id="course-work-import-module"
                        v-model.number="selectedModuleId"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option v-for="option in moduleOptions" :key="option.moduleId" :value="option.moduleId">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div v-if="canImportCourseWork && selectedModuleId" class="flex shrink-0 flex-wrap gap-2">
                    <a
                        v-if="templateUrl"
                        :href="templateUrl"
                        class="inline-flex"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                            {{ $t('academic_calendar.course_work_import_download_template') }}
                        </BaseButton>
                    </a>
                </div>
            </div>

            <div
                v-if="canImportCourseWork && selectedModuleId"
                class="space-y-4 rounded-lg border border-border p-4"
            >
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase text-muted-foreground" for="course-work-import-file">
                        {{ $t('academic_calendar.course_work_import_file_label') }}
                    </label>
                    <input
                        id="course-work-import-file"
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                        class="block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium"
                        @change="onFileChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t('academic_calendar.course_work_import_file_hint') }}
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
                    {{ $t('academic_calendar.course_work_import_preview') }}
                </BaseButton>
            </div>

            <div v-if="preview" class="space-y-4 rounded-lg border border-border p-4">
                <div>
                    <h3 class="font-semibold">{{ $t('academic_calendar.course_work_import_preview_title') }}</h3>
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
                                <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                                <th class="j-th text-left">{{ $tChoice('students.student_number', 1) }}</th>
                                <th class="j-th text-left">{{ $t('academic_calendar.course_work_mark') }}</th>
                                <th class="j-th text-left">{{ $t('academic_calendar.course_work_remark') }}</th>
                                <th class="j-th text-left">{{ $t('trans.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="j-tbody">
                            <tr v-for="row in preview.rows" :key="row.rowNumber" class="j-tr">
                                <td class="j-td">{{ row.rowNumber }}</td>
                                <td class="j-td">{{ row.studentName ?? '—' }}</td>
                                <td class="j-td font-mono text-xs">{{ row.studentNumber ?? '—' }}</td>
                                <td class="j-td">{{ row.mark ?? '—' }}</td>
                                <td class="j-td text-sm text-muted-foreground">{{ row.remark ?? '—' }}</td>
                                <td class="j-td">
                                    <span
                                        class="text-xs font-medium"
                                        :class="{
                                            'text-green-700': row.action === 'create' || row.action === 'update',
                                            'text-muted-foreground':
                                                row.action === 'skip_empty' || row.action === 'skip_duplicate',
                                            'text-destructive': row.action === 'fail',
                                        }"
                                    >
                                        {{ actionLabel(row.action) }}
                                    </span>
                                    <p
                                        v-if="row.errors"
                                        class="mt-1 text-xs text-destructive"
                                    >
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
                        {{ $t('academic_calendar.course_work_import_confirm') }}
                    </BaseButton>
                </div>
            </div>

            <div
                v-if="hasImportResult && courseWorkImportResult"
                class="rounded-lg border border-border bg-muted/30 p-4 text-sm"
            >
                <h3 class="font-semibold">{{ $t('academic_calendar.course_work_import_result_title') }}</h3>
                <ul class="mt-2 space-y-1 text-muted-foreground">
                    <li>
                        {{ $t('academic_calendar.course_work_import_result_total', { count: String(courseWorkImportResult.rowsTotal) }) }}
                    </li>
                    <li>
                        {{ $t('academic_calendar.course_work_import_result_succeeded', { count: String(courseWorkImportResult.rowsSucceeded) }) }}
                    </li>
                    <li>
                        {{ $t('academic_calendar.course_work_import_result_failed', { count: String(courseWorkImportResult.rowsFailed) }) }}
                    </li>
                    <li>
                        {{ $t('academic_calendar.course_work_import_result_skipped', { count: String(courseWorkImportResult.rowsSkipped) }) }}
                    </li>
                </ul>
            </div>
        </template>
    </section>
</template>
