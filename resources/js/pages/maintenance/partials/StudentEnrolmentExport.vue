<script setup lang="ts">
import GenericButton from '@/components/core/button/GenericButton.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { successAlert, warningDialog } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { AuthObject } from '@/types/data-pagination';
import type { MaintenanceExportCounts } from '@/types/maintenance-exports';
import { useDebounceFn } from '@vueuse/core';
import { useForm, usePage } from '@inertiajs/vue3';
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

const enrollmentExportLabel = computed(() =>
    trans('trans.maintenance_export_student_enrolments_with_count', {
        count: String(counts.value.studentEnrolments),
    }),
);

const applicationExportLabel = computed(() =>
    trans('trans.maintenance_export_applications_with_count', {
        count: String(counts.value.applications),
    }),
);

const faultyDataLabel = computed(() =>
    trans('trans.maintenance_faulty_data_with_count', {
        count: String(counts.value.faultyStudentIds),
    }),
);

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
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-6 rounded-lg border border-border bg-card p-6">
                <div class="space-y-2">
                    <BaseInput
                        v-model="form.recipient_emails"
                        name="recipient_emails"
                        :label="trans('trans.maintenance_export_recipient_emails_label')"
                        :placeholder="trans('trans.maintenance_export_recipient_emails_placeholder')"
                        :error="recipientEmailsError"
                    />
                    <p class="text-sm text-muted-foreground">
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

                <HeadingSmall
                    :title="trans('trans.maintenance_export_student_enrolments')"
                    :description="trans('trans.maintenance_export_student_enrolments_description')"
                />

                <GenericButton
                    :icon="IconName.export"
                    :variant="ColorVariant.primary_outline"
                    :title="enrollmentExportLabel"
                    :disabled="form.processing || isLoadingCounts"
                    @click="confirmEnrollmentExport"
                />

                <CustomSeparator classes="h-1 my-5" />

                <HeadingSmall
                    :title="trans('trans.maintenance_export_applications')"
                    :description="trans('trans.maintenance_export_applications_description')"
                />

                <GenericButton
                    :icon="IconName.export"
                    :variant="ColorVariant.primary_outline"
                    :title="applicationExportLabel"
                    :disabled="form.processing || isLoadingCounts"
                    @click="confirmApplicationExport"
                />
        </div>

        <div class="space-y-6 rounded-lg border border-border bg-card p-6">
            <HeadingSmall
                :title="trans('trans.maintenance_faulty_data')"
                :description="trans('trans.maintenance_faulty_data_description')"
            />

            <GenericButton
                :icon="IconName.edit"
                :variant="ColorVariant.primary_outline"
                :title="faultyDataLabel"
                @click="goToFaultyData"
            />
        </div>
    </div>
</template>
