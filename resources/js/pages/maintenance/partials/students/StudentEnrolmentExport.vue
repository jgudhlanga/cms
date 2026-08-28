<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { useUtils } from '@/composables/core/useUtils';
import HttpService from '@/services/http.service';
import type { MaintenanceExportCounts } from '@/types/maintenance-exports';
import {
    AlertTriangle,
    Award,
    ChevronRight,
    FileSpreadsheet,
    FileText,
    ListChecks,
    Mail,
    Workflow,
} from 'lucide-vue-next';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    exportCounts?: MaintenanceExportCounts;
}>();

const { navigateTo } = useUtils();

const counts = ref<MaintenanceExportCounts>(
    props.exportCounts ?? {
        studentEnrolments: 0,
        applications: 0,
        faultyStudentIds: 0,
        faultyApplications: 0,
    },
);

const loadExportCounts = async (): Promise<void> => {
    counts.value = (await HttpService.get(route('maintenance.exports.counts'))) as MaintenanceExportCounts;
};

onMounted(() => {
    if (!props.exportCounts) {
        void loadExportCounts();
    }
});

const exports = computed(() => [
    {
        key: 'student-enrolments',
        icon: FileSpreadsheet,
        title: trans('trans.maintenance_export_student_enrolments'),
        description: trans('trans.maintenance_export_student_enrolments_description'),
        href: route('maintenance.exports.student-enrollment.preview'),
    },
    {
        key: 'applications',
        icon: FileText,
        title: trans('trans.maintenance_export_applications'),
        description: trans('trans.maintenance_export_applications_description'),
        href: route('maintenance.exports.application.preview'),
    },
]);

const dataManagementItems = computed(() => [
    {
        key: 'verified-students',
        icon: ListChecks,
        title: trans('trans.maintenance_verified_students_final_enrolment'),
        description: trans('trans.maintenance_verified_students_final_enrolment_description'),
        href: route('maintenance.verified-students-final-enrolment'),
        count: 0,
    },
    {
        key: 'apprentices',
        icon: Workflow,
        title: trans('trans.maintenance_apprentice_management'),
        description: trans('trans.maintenance_apprentice_management_description'),
        href: route('maintenance.apprentice-management'),
        count: 0,
    },
    {
        key: 'sponsored-students',
        icon: Award,
        title: trans('trans.maintenance_sponsored_students'),
        description: trans('trans.maintenance_sponsored_students_description'),
        href: route('maintenance.sponsored-students'),
        count: 0,
    },
    {
        key: 'faulty-ids',
        icon: AlertTriangle,
        title: trans('trans.maintenance_faulty_data'),
        description: trans('trans.maintenance_faulty_data_description'),
        href: route('maintenance.faulty-student-ids'),
        count: counts.value.faultyStudentIds,
    },
    {
        key: 'faulty-applications',
        icon: AlertTriangle,
        title: trans('trans.maintenance_faulty_applications'),
        description: trans('trans.maintenance_faulty_applications_description'),
        href: route('maintenance.faulty-applications'),
        count: counts.value.faultyApplications,
    },
]);

const sectionLabelClass = 'text-[0.63rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground';
</script>

<template>
    <div class="w-full min-w-0 space-y-6">
        <section class="space-y-2">
            <h2 :class="sectionLabelClass">{{ trans('trans.maintenance_bulk_exports') }}</h2>

            <div class="divide-y divide-border rounded-xl border border-border bg-card">
                <div
                    v-for="exportItem in exports"
                    :key="exportItem.key"
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <component :is="exportItem.icon" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-foreground">{{ exportItem.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ exportItem.description }}</p>
                        </div>
                    </div>

                    <BaseButton
                        type="button"
                        class="shrink-0"
                        :size="ButtonSize.sm"
                        :variant="ColorVariant.shade"
                        @click="navigateTo(exportItem.href)"
                    >
                        <Mail class="mr-2 h-4 w-4" />
                        {{ trans('trans.maintenance_email_export') }}
                    </BaseButton>
                </div>
            </div>
        </section>

        <section class="space-y-2">
            <h2 :class="sectionLabelClass">{{ trans('trans.maintenance_data_management') }}</h2>

            <div class="divide-y divide-border rounded-xl border border-border bg-card">
                <button
                    v-for="item in dataManagementItems"
                    :key="item.key"
                    type="button"
                    class="flex w-full cursor-pointer items-center justify-between gap-3 p-4 text-left transition-colors hover:bg-muted/50"
                    @click="navigateTo(item.href)"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                            <component :is="item.icon" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-foreground">{{ item.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ item.description }}</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <span
                            v-if="item.count > 0"
                            class="rounded-full bg-destructive/10 px-2 py-0.5 text-[11px] font-medium text-destructive"
                        >
                            {{ trans('trans.maintenance_records_count', { count: String(item.count) }) }}
                        </span>
                        <ChevronRight class="h-4 w-4 text-muted-foreground" />
                    </div>
                </button>
            </div>
        </section>
    </div>
</template>
