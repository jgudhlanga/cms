<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { Link } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

type ImportPayload = {
    id: number;
    sourceLabel: string;
    status: string;
    statusLabel: string;
    originalFilename: string;
    rowsTotal: number;
    rowsProcessed: number;
    rowsUpserted: number;
    rowsFailed: number;
    progressPercent: number;
    errorMessage: string | null;
    startedAt: string | null;
    completedAt: string | null;
};

const props = defineProps<{
    examinationImport: ImportPayload;
}>();

const breadcrumbs = computed<Link[]>(() => [
    { transChoiceKey: 'examinations.title', href: route('examinations.index') },
    { transKey: 'examinations.import_history', href: route('examinations.imports.index') },
    { title: props.examinationImport.originalFilename },
]);
</script>

<template>
    <Head :title="examinationImport.originalFilename" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('examinations.imports.index')">
        <div class="mx-auto max-w-2xl space-y-4">
            <BaseAlert
                v-if="examinationImport.errorMessage"
                :type="TypeVariant.danger"
                :description="examinationImport.errorMessage"
            />

            <div class="space-y-2 rounded-md border p-4 text-sm">
                <p>
                    <span class="font-medium">{{ $t('examinations.import_file') }}:</span>
                    {{ examinationImport.originalFilename }}
                </p>
                <p>
                    <span class="font-medium">{{ $t('examinations.import_source') }}:</span>
                    {{ examinationImport.sourceLabel }}
                </p>
                <p>
                    <span class="font-medium">{{ $t('examinations.import_status') }}:</span>
                    {{ examinationImport.statusLabel }}
                </p>
                <p>
                    <span class="font-medium">{{ $t('examinations.rows_total') }}:</span>
                    {{ examinationImport.rowsTotal }}
                </p>
                <p>
                    <span class="font-medium">{{ $t('examinations.rows_processed') }}:</span>
                    {{ examinationImport.rowsProcessed }}
                </p>
                <p>
                    <span class="font-medium">{{ $t('examinations.rows_upserted') }}:</span>
                    {{ examinationImport.rowsUpserted }}
                </p>
                <p>
                    <span class="font-medium">{{ $t('examinations.rows_failed') }}:</span>
                    {{ examinationImport.rowsFailed }}
                </p>

                <div class="space-y-1 pt-2">
                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>{{ $t('examinations.import_progress') }}</span>
                        <span>{{ examinationImport.progressPercent }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-sm bg-muted">
                        <div
                            class="h-2 rounded-sm bg-emerald-500"
                            :style="{ width: `${examinationImport.progressPercent}%` }"
                        />
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <BaseButton
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.sm"
                    :title="$tChoice('examinations.results', 2)"
                    @click="router.visit(route('examinations.index'))"
                />
                <BaseButton
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.sm"
                    :title="$t('examinations.import_title')"
                    @click="router.visit(route('examinations.import'))"
                />
            </div>
        </div>
    </PageContainer>
</template>
