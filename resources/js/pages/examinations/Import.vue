<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert, successAlert } from '@/lib/alerts';
import customAxios from '@/services/http-init';
import type { Link } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onUnmounted, ref } from 'vue';

type ImportPayload = {
    id: number;
    status: string;
    statusLabel: string;
    originalFilename: string;
    rowsTotal: number;
    rowsProcessed: number;
    rowsUpserted: number;
    rowsFailed: number;
    progressPercent: number;
    errorMessage: string | null;
};

const breadcrumbs = computed<Link[]>(() => [
    { transChoiceKey: 'examinations.title', href: route('examinations.index') },
    { transKey: 'examinations.import_title' },
]);

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const fileError = ref<string | null>(null);
const uploading = ref(false);
const uploadPercent = ref(0);
const currentImport = ref<ImportPayload | null>(null);
let pollTimer: ReturnType<typeof setInterval> | null = null;

const ACCEPTED = '.xlsx,.xls,.csv';

const onFileChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    fileError.value = null;
    currentImport.value = null;
    selectedFile.value = file;
};

const clearPoll = (): void => {
    if (pollTimer !== null) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const pollImport = (id: number): void => {
    clearPoll();
    let consecutiveFailures = 0;

    pollTimer = setInterval(async () => {
        try {
            const response = await customAxios('').get<{ import: ImportPayload }>(
                route('examinations.imports.show', id),
                { params: { json: 1 } },
            );
            consecutiveFailures = 0;
            currentImport.value = response.data.import;
            if (['completed', 'failed'].includes(response.data.import.status)) {
                clearPoll();
                if (response.data.import.status === 'completed') {
                    successAlert(trans('examinations.import_status_completed'));
                } else {
                    errorAlert(response.data.import.errorMessage ?? trans('examinations.import_status_failed'));
                }
            }
        } catch {
            consecutiveFailures += 1;
            // Keep polling through transient errors; stop after repeated failures.
            if (consecutiveFailures >= 5) {
                clearPoll();
                errorAlert(trans('examinations.import_status_failed'));
            }
        }
    }, 2000);
};

const startImport = async (): Promise<void> => {
    if (!selectedFile.value) {
        fileError.value = trans('examinations.select_file');
        return;
    }

    uploading.value = true;
    uploadPercent.value = 0;
    fileError.value = null;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await customAxios('').post<{ import: ImportPayload; message: string }>(
            route('examinations.import.store'),
            formData,
            {
                headers: { 'Content-Type': 'multipart/form-data' },
                onUploadProgress: (event) => {
                    if (!event.total) {
                        return;
                    }
                    uploadPercent.value = Math.round((event.loaded / event.total) * 100);
                },
            },
        );

        currentImport.value = response.data.import;
        successAlert(response.data.message);
        pollImport(response.data.import.id);
    } catch (error: unknown) {
        const message =
            (error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response
                ?.data?.errors?.file?.[0] ??
            (error as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            trans('examinations.import_store_failed');
        fileError.value = message;
        errorAlert(message);
    } finally {
        uploading.value = false;
    }
};

onUnmounted(() => {
    clearPoll();
});
</script>

<template>
    <Head :title="$t('examinations.import_title')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('examinations.index')">
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold uppercase">{{ $t('examinations.import_title') }}</h2>
            </div>
        </template>

        <div class="mx-auto max-w-2xl space-y-4">
            <BaseAlert :type="TypeVariant.info" :description="$t('examinations.import_description')" />

            <div class="space-y-3 rounded-md border p-4">
                <label class="block text-sm font-medium">{{ $t('examinations.select_file') }}</label>
                <input
                    ref="fileInput"
                    type="file"
                    :accept="ACCEPTED"
                    class="block w-full text-sm"
                    @change="onFileChange"
                />
                <p v-if="selectedFile" class="text-sm text-muted-foreground">{{ selectedFile.name }}</p>
                <p v-if="fileError" class="text-sm text-red-600">{{ fileError }}</p>

                <div v-if="uploading || uploadPercent > 0" class="space-y-1">
                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>{{ $t('examinations.upload_progress') }}</span>
                        <span>{{ uploadPercent }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-sm bg-muted">
                        <div class="h-2 rounded-sm bg-primary transition-all" :style="{ width: `${uploadPercent}%` }" />
                    </div>
                </div>

                <BaseButton
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.md"
                    :title="$t('examinations.start_import')"
                    :processing="uploading"
                    :disabled="uploading || !selectedFile"
                    @click="startImport"
                />
            </div>

            <div v-if="currentImport" class="space-y-3 rounded-md border p-4">
                <h3 class="font-semibold">{{ $t('examinations.import_progress') }}</h3>
                <p class="text-sm">
                    {{ currentImport.originalFilename }} — {{ currentImport.statusLabel }}
                </p>
                <div class="space-y-1">
                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>{{ currentImport.rowsProcessed }} / {{ currentImport.rowsTotal || '…' }}</span>
                        <span>{{ currentImport.progressPercent }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-sm bg-muted">
                        <div
                            class="h-2 rounded-sm bg-emerald-500 transition-all"
                            :style="{ width: `${currentImport.progressPercent}%` }"
                        />
                    </div>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ $t('examinations.rows_upserted') }}: {{ currentImport.rowsUpserted }} ·
                    {{ $t('examinations.rows_failed') }}: {{ currentImport.rowsFailed }}
                </p>
                <p v-if="currentImport.errorMessage" class="text-sm text-red-600">{{ currentImport.errorMessage }}</p>
                <BaseButton
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.sm"
                    :title="$t('examinations.import_history')"
                    @click="router.visit(route('examinations.imports.index'))"
                />
            </div>
        </div>
    </PageContainer>
</template>
