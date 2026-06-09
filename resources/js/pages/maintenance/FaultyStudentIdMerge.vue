<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { warningDialog } from '@/lib/alerts';
import type { StudentAccountMergePreview } from '@/types/faulty-student-ids';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { RadioGroupOption } from '@/types/forms';
import { Head, useForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

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

const summaryFields = (summary: StudentAccountMergePreview['source']) => [
    { label: trans('trans.email_address'), value: summary.email ?? '---' },
    { label: trans('trans.phone_number'), value: summary.phoneNumber ?? '---' },
    { label: trans_choice('trans.student_number', 1), value: summary.studentNumber ?? '---' },
    { label: trans('trans.id_number'), value: summary.idNumber ?? '---' },
    { label: trans('trans.maintenance_faulty_data_merge_programmes'), value: String(summary.programmesCount) },
    { label: trans('trans.maintenance_faulty_data_merge_enrolments'), value: String(summary.enrolmentsCount) },
    { label: trans('trans.maintenance_faulty_data_merge_receipts'), value: String(summary.paidReceiptsCount) },
    { label: trans('trans.maintenance_faulty_data_merge_contacts'), value: String(summary.contactsCount) },
    { label: trans('trans.maintenance_faulty_data_merge_addresses'), value: String(summary.addressesCount) },
    { label: trans('trans.maintenance_faulty_data_merge_academic_results'), value: String(summary.academicResultsCount) },
    { label: trans('trans.maintenance_faulty_data_merge_hostel_applications'), value: String(summary.hostelApplicationsCount) },
];

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
        <div class="space-y-6">
            <BaseAlert
                :type="TypeVariant.warning"
                :description="trans('trans.maintenance_faulty_data_merge_description')"
            />

            <div class="rounded-lg border border-border bg-muted/30 px-4 py-3">
                <p class="text-sm text-muted-foreground">{{ trans('trans.maintenance_faulty_data_merge_proposed_id') }}</p>
                <p class="font-mono text-lg font-semibold">{{ preview.proposedIdNumber }}</p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div
                    class="space-y-3 rounded-lg border p-4"
                    :class="form.survivor_student_id === String(preview.source.studentId) ? 'border-primary ring-1 ring-primary' : 'border-border'"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-semibold">{{ preview.source.name }}</h3>
                        <span class="text-xs uppercase text-destructive">{{ trans('trans.maintenance_faulty_data_merge_faulty_account') }}</span>
                    </div>
                    <dl class="grid grid-cols-1 gap-2 text-sm">
                        <div v-for="field in summaryFields(preview.source)" :key="field.label" class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">{{ field.label }}</dt>
                            <dd class="text-right font-medium">{{ field.value }}</dd>
                        </div>
                    </dl>
                </div>

                <div
                    class="space-y-3 rounded-lg border p-4"
                    :class="form.survivor_student_id === String(preview.target.studentId) ? 'border-primary ring-1 ring-primary' : 'border-border'"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-semibold">{{ preview.target.name }}</h3>
                        <span class="text-xs uppercase text-primary">{{ trans('trans.maintenance_faulty_data_merge_existing_account') }}</span>
                    </div>
                    <dl class="grid grid-cols-1 gap-2 text-sm">
                        <div v-for="field in summaryFields(preview.target)" :key="field.label" class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">{{ field.label }}</dt>
                            <dd class="text-right font-medium">{{ field.value }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="space-y-2">
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
