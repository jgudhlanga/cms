<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseCard from '@/components/core/card/BaseCard.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert, successAlert, warningDialog } from '@/lib/alerts';
import type {
    StudentAccountMergeApplication,
    StudentAccountMergePreview,
    StudentAccountMergeSummary,
} from '@/types/faulty-student-ids';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { RadioGroupOption } from '@/types/forms';
import { Head, router, useForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const props = defineProps<{
    preview: StudentAccountMergePreview;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transKey: 'trans.maintenance', href: route('maintenance.index') },
    { transKey: 'trans.maintenance_faulty_data', href: route('maintenance.faulty-student-ids') },
    { transKey: 'trans.maintenance_faulty_data_merge_title' },
];

const form = useForm({
    source_student_id: props.preview.source.studentId,
    target_student_id: props.preview.target.studentId,
    survivor_student_id: String(props.preview.target.studentId),
    id_number: props.preview.proposedIdNumber,
});

const rejectingApplicationIds = ref<Set<number>>(new Set());

const profiles = computed(() => [props.preview.source, props.preview.target]);

const survivorOptions = computed<RadioGroupOption[]>(() => [
    {
        inputId: 'survivor_source',
        label: props.preview.source.name ?? trans('trans.maintenance_faulty_data_merge_faulty_account'),
        value: String(props.preview.source.studentId),
    },
    {
        inputId: 'survivor_target',
        label: props.preview.target.name ?? trans('trans.maintenance_faulty_data_merge_existing_account'),
        value: String(props.preview.target.studentId),
    },
]);

const profileTitle = (profile: StudentAccountMergeSummary): string => {
    const accountLabel = profile.isFaultySource
        ? trans('trans.maintenance_faulty_data_merge_faulty_account')
        : trans('trans.maintenance_faulty_data_merge_existing_account');

    return `${profile.name ?? accountLabel} · ${accountLabel}`;
};

const profileCardVariant = (profile: StudentAccountMergeSummary): string =>
    form.survivor_student_id === String(profile.studentId) ? 'green-600' : 'amber-500';

const summaryFields = (summary: StudentAccountMergeSummary) => [
    { label: trans('trans.email_address'), value: summary.email ?? undefined },
    { label: trans('trans.phone_number'), value: summary.phoneNumber ?? undefined },
    { label: trans_choice('trans.student_number', 1), value: summary.studentNumber ?? undefined },
    { label: trans('trans.id_number'), value: summary.idNumber ?? undefined, valueClasses: 'font-mono' },
    { label: trans('trans.maintenance_faulty_data_merge_programmes'), value: String(summary.programmesCount) },
    { label: trans('trans.maintenance_faulty_data_merge_enrolments'), value: String(summary.enrolmentsCount) },
    { label: trans('trans.maintenance_faulty_data_merge_receipts'), value: String(summary.paidReceiptsCount) },
    { label: trans('trans.maintenance_faulty_data_merge_contacts'), value: String(summary.contactsCount) },
    { label: trans('trans.maintenance_faulty_data_merge_addresses'), value: String(summary.addressesCount) },
    { label: trans('trans.maintenance_faulty_data_merge_academic_results'), value: String(summary.academicResultsCount) },
    { label: trans('trans.maintenance_faulty_data_merge_hostel_applications'), value: String(summary.hostelApplicationsCount) },
];

const applicationStatusLabel = (application: StudentAccountMergeApplication): string => {
    const status = application.applicationStatus ?? '---';

    if (!application.classListType) {
        return status;
    }

    return `${status} · ${application.classListType}`;
};

const isRejectingApplication = (applicationId: number): boolean => rejectingApplicationIds.value.has(applicationId);

const rejectApplication = async (application: StudentAccountMergeApplication) => {
    const confirmed = await useCustomConfirmDialog().open({
        title: trans('trans.maintenance_faulty_data_merge_reject_application'),
        message: trans('trans.maintenance_faulty_data_merge_reject_confirm'),
        confirmText: trans('trans.maintenance_faulty_data_merge_reject_application'),
    });

    if (!confirmed) {
        return;
    }

    rejectingApplicationIds.value.add(application.id);

    router.patch(
        route('maintenance.faulty-student-ids.merge.reject-application', application.id),
        {
            source_student_id: props.preview.source.studentId,
            target_student_id: props.preview.target.studentId,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                successAlert(trans('trans.maintenance_faulty_data_merge_reject_success'));
            },
            onError: (errors: Record<string, string | string[]>) => {
                if (Object.keys(errors).length) {
                    errorAlert(Object.values(errors).flat().join('\n'));
                }
            },
            onFinish: () => {
                rejectingApplicationIds.value.delete(application.id);
            },
        },
    );
};

const executeMerge = () => {
    warningDialog(
        () => {
            form.post(route('maintenance.faulty-student-ids.merge.execute'), {
                preserveScroll: true,
            });
            return true;
        },
        trans('trans.maintenance_faulty_data_merge_confirm'),
        trans('trans.warning'),
        trans('trans.maintenance_faulty_data_merge_title'),
    );
};
</script>

<template>
    <Head :title="trans('trans.maintenance_faulty_data_merge_title')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <BaseAlert
                :type="TypeVariant.warning"
                :description="trans('trans.maintenance_faulty_data_merge_description')"
            />

            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
                <span class="text-muted-foreground">{{ trans('trans.maintenance_faulty_data_merge_proposed_id') }}</span>
                <span class="font-mono font-semibold">{{ preview.proposedIdNumber }}</span>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <BaseCard
                    v-for="profile in profiles"
                    :key="profile.studentId"
                    :title="profileTitle(profile)"
                    :color-variant="profileCardVariant(profile)"
                    class="space-y-2! p-3!"
                >
                    <div class="grid grid-cols-2 gap-3">
                        <LabelValue
                            v-for="field in summaryFields(profile)"
                            :key="field.label"
                            :label="field.label"
                            :value="field.value"
                            :value-classes="field.valueClasses"
                        />
                    </div>

                    <div class="space-y-1 border-t border-border pt-2">
                        <p class="text-xs font-medium">{{ trans('trans.maintenance_faulty_data_merge_applications') }}</p>
                        <p
                            v-if="profile.applications.length === 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ trans('trans.maintenance_faulty_data_merge_no_applications') }}
                        </p>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b border-border text-left text-muted-foreground">
                                        <th class="pb-1 pr-2 font-medium">{{ trans_choice('trans.code', 1) }}</th>
                                        <th class="pb-1 pr-2 font-medium">{{ trans_choice('trans.course', 1) }}</th>
                                        <th class="pb-1 pr-2 font-medium">{{ trans_choice('trans.level', 1) }}</th>
                                        <th class="pb-1 pr-2 font-medium">{{ trans_choice('trans.intake_period', 1) }}</th>
                                        <th class="pb-1 pr-2 font-medium">{{ trans_choice('trans.application', 1) }} {{ trans_choice('trans.status', 1) }}</th>
                                        <th class="pb-1 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="application in profile.applications"
                                        :key="application.id"
                                        class="border-b border-border/60"
                                    >
                                        <td class="py-1 pr-2 font-mono">{{ application.departmentCode ?? '---' }}</td>
                                        <td class="max-w-32 truncate py-1 pr-2" :title="application.course ?? undefined">{{ application.course ?? '---' }}</td>
                                        <td class="py-1 pr-2">{{ application.level ?? '---' }}</td>
                                        <td class="max-w-24 truncate py-1 pr-2" :title="application.intakePeriod ?? undefined">{{ application.intakePeriod ?? '---' }}</td>
                                        <td class="py-1 pr-2">{{ applicationStatusLabel(application) }}</td>
                                        <td class="py-1 text-right">
                                            <BaseButton
                                                v-if="application.canReject"
                                                :title="trans('trans.maintenance_faulty_data_merge_reject_application')"
                                                :variant="ColorVariant.danger"
                                                :size="ButtonSize.xs"
                                                type="button"
                                                :disabled="isRejectingApplication(application.id)"
                                                @click="rejectApplication(application)"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </BaseCard>
            </div>

            <div class="space-y-1">
                <p class="text-sm font-medium">{{ trans('trans.maintenance_faulty_data_keep_account') }}</p>
                <BaseRadioGroup
                    v-model="form.survivor_student_id"
                    name="survivor_student_id"
                    :options="survivorOptions"
                />
            </div>

            <BaseButton
                :title="trans('trans.maintenance_faulty_data_merge_title')"
                :variant="ColorVariant.primary"
                :size="ButtonSize.sm"
                type="button"
                :disabled="form.processing"
                @click="executeMerge"
            />
        </div>
    </PageContainer>
</template>
