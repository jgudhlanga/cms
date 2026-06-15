<script setup lang="ts">
import GenericButton from '@/components/core/button/GenericButton.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { Separator } from '@/components/ui/separator';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { successAlert, warningDialog } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { AuthObject } from '@/types/data-pagination';
import type { MaintenanceExportCounts } from '@/types/maintenance-exports';
import { useDebounceFn } from '@vueuse/core';
import { useForm, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    exportCounts?: MaintenanceExportCounts;
}>();

const page = usePage<{ auth: AuthObject }>();
const { navigateTo } = useUtils();
const defaultRecipientEmail = page.props.auth?.user?.attributes?.email ?? '';

const form = useForm({
    intake_year: '',
    recipient_emails: defaultRecipientEmail,
});

const counts = ref<MaintenanceExportCounts>({
    studentEnrolments: props.exportCounts?.studentEnrolments ?? 0,
    applications: props.exportCounts?.applications ?? 0,
    faultyStudentIds: props.exportCounts?.faultyStudentIds ?? 0,
});

const isLoadingCounts = ref(false);

const loadExportCounts = async (intakeYear?: string) => {
    isLoadingCounts.value = true;

    try {
        const params = intakeYear ? { intake_year: intakeYear } : undefined;
        const response = (await HttpService.get(route('maintenance.exports.counts'), { params })) as MaintenanceExportCounts;
        counts.value = response;
    } finally {
        isLoadingCounts.value = false;
    }
};

const debouncedLoadExportCounts = useDebounceFn((intakeYear: string) => {
    void loadExportCounts(intakeYear || undefined);
}, 400);

watch(
    () => form.intake_year,
    (intakeYear) => {
        void debouncedLoadExportCounts(intakeYear);
    },
);

onMounted(() => {
    if (!props.exportCounts) {
        void loadExportCounts();
    }
});

const recipientEmailsError = computed(() => {
    if (form.errors.recipient_emails) {
        return form.errors.recipient_emails;
    }

    return Object.entries(form.errors)
        .filter(([key]) => key.startsWith('recipient_emails.'))
        .map(([, message]) => message)
        .join(' ');
});

const submitEnrollmentExport = () => {
    form.post(route('maintenance.exports.student-enrollment'), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert(trans('trans.maintenance_export_queued_message'));
            form.reset('intake_year');
        },
    });
};

const submitApplicationExport = () => {
    form.post(route('maintenance.exports.application'), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert(trans('trans.maintenance_export_application_queued_message'));
            form.reset('intake_year');
        },
    });
};

const confirmEnrollmentExport = () => {
    warningDialog(
        () => {
            submitEnrollmentExport();
            return true;
        },
        trans('trans.maintenance_export_confirm_message'),
        trans('trans.warning'),
        trans('trans.maintenance_export_student_enrolments'),
    );
};

const confirmApplicationExport = () => {
    warningDialog(
        () => {
            submitApplicationExport();
            return true;
        },
        trans('trans.maintenance_export_application_confirm_message'),
        trans('trans.warning'),
        trans('trans.maintenance_export_applications'),
    );
};

const goToFaultyData = () => navigateTo(route('maintenance.faulty-student-ids'));
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1">
                <BaseInput
                    v-model="form.recipient_emails"
                    name="recipient_emails"
                    :label="trans('trans.maintenance_export_recipient_emails_label')"
                    :placeholder="trans('trans.maintenance_export_recipient_emails_placeholder')"
                    :error="recipientEmailsError"
                />
                <p class="text-xs text-muted-foreground">
                    {{ trans('trans.maintenance_export_recipient_emails_help') }}
                </p>
            </div>

            <BaseInput
                v-model="form.intake_year"
                name="intake_year"
                :label="trans('trans.maintenance_intake_year_label')"
                :placeholder="trans('trans.maintenance_intake_year_placeholder')"
                :error="form.errors.intake_year"
            />
        </div>

        <Separator />

        <div class="flex flex-wrap items-center justify-between gap-3 py-1">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-foreground">
                    {{
                        trans('trans.maintenance_export_student_enrolments_with_count', {
                            count: String(counts.studentEnrolments),
                        })
                    }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ trans('trans.maintenance_export_student_enrolments_description') }}
                </p>
            </div>
            <GenericButton
                :icon="IconName.export"
                :variant="ColorVariant.primary_outline"
                :title="trans('trans.export')"
                :disabled="form.processing || isLoadingCounts"
                @click="confirmEnrollmentExport"
            />
        </div>

        <Separator />

        <div class="flex flex-wrap items-center justify-between gap-3 py-1">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-foreground">
                    {{
                        trans('trans.maintenance_export_applications_with_count', {
                            count: String(counts.applications),
                        })
                    }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ trans('trans.maintenance_export_applications_description') }}
                </p>
            </div>
            <GenericButton
                :icon="IconName.export"
                :variant="ColorVariant.primary_outline"
                :title="trans('trans.export')"
                :disabled="form.processing || isLoadingCounts"
                @click="confirmApplicationExport"
            />
        </div>

        <Separator />

        <button
            type="button"
            class="flex w-full items-center justify-between gap-3 rounded-md py-1 text-left transition-colors hover:bg-muted/50"
            @click="goToFaultyData"
        >
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-foreground">
                        {{ trans('trans.maintenance_faulty_data') }}
                    </p>
                    <span
                        v-if="counts.faultyStudentIds > 0"
                        class="rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-medium text-destructive-foreground"
                    >
                        {{ counts.faultyStudentIds }}
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ trans('trans.maintenance_faulty_data_description') }}
                </p>
            </div>
            <ChevronRight class="h-4 w-4 shrink-0 text-muted-foreground" />
        </button>
    </div>
</template>
