<script setup lang="ts">
import EclipseButton from '@/components/core/button/EclipseButton.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import PaymentStatusButton from '@/pages/institution/enrolments/partials/PaymentStatusButton.vue';
import UpdateAllPaymentsButton from '@/pages/institution/enrolments/partials/UpdateAllPaymentsButton.vue';
import { DepartmentApplicationStep, DepartmentLevel } from '@/types/department-meta-data';
import { BulkUpdatePaymentStatus, Enrolment } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    level: DepartmentLevel;
    departmentId: string;
    enrolments: Enrolment[];
    steps: DepartmentApplicationStep[];
    step: DepartmentApplicationStep;
    updatePaymentStatusParams: BulkUpdatePaymentStatus;
}

const props = defineProps<Props>();

const { level } = props;
const { formatDate } = useUtils();
const { approveApplication, registrationFeePaymentRequired, tuitionFeePaymentRequired, canApproveWorkflowStepApplications } =
    useStudentApplications();

const levelRequirements = computed(() => {
    return level?.relationships?.requirement;
});

const buttonOptions = (enrolment: Enrolment) => {
    const choices = [];
    for (const option of props.steps) {
        choices.push({
            key: option.id,
            id: option.id,
            title: option?.attributes?.workflowStep,
            action: () => approveApplication(enrolment, option.id?.toString() ?? '', props.step),
        });
    }
    return choices;
};
</script>

<template>
    <table class="j-table">
        <thead class="j-thead">
            <tr class="j-th">
                <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                <th class="j-th text-left">{{ $tChoice('trans.tracking_number', 1) }}</th>
                <template v-if="registrationFeePaymentRequired(step)">
                    <th class="j-th text-center">{{ $t('trans.application_fee') }}</th>
                </template>
                <template v-if="tuitionFeePaymentRequired(step)">
                    <th class="j-th text-left">{{ $t('trans.tuition_fee') }}</th>
                </template>
                <th class="j-th text-left">{{ $t('trans.application_date') }}</th>
                <th class="j-th text-center">{{ $tChoice('trans.action', 2) }}</th>
            </tr>
        </thead>
        <tbody class="j-tbody">
            <template v-if="registrationFeePaymentRequired(step)">
                <tr class="j-tr">
                    <td class="j-td">{{ $t('trans.action_all') }}</td>
                    <td class="j-td"></td>
                    <td class="j-td text-center">
                        <UpdateAllPaymentsButton :department-id="departmentId" :enrolments="enrolments" :params="updatePaymentStatusParams" />
                    </td>
                </tr>
            </template>
            <tr class="j-tr" v-for="enrolment in enrolments" :key="enrolment.id">
                <td class="j-td">
                    <TextLink :href="''" :title="enrolment?.attributes?.studentName" />
                </td>
                <td class="j-td">{{ enrolment?.attributes?.applicationTrackingNumber }}</td>
                <template v-if="registrationFeePaymentRequired(step)">
                    <td class="j-td text-center">
                        <PaymentStatusButton :enrolment="enrolment" :step="step" type="registration" />
                    </td>
                </template>
                <template v-if="tuitionFeePaymentRequired(step)">
                    <td class="j-td text-center">
                        <PaymentStatusButton :enrolment="enrolment" :step="step" type="tuition" />
                    </td>
                </template>
                <td class="j-td">{{ formatDate(enrolment?.attributes?.createdAt, 'L') }}</td>
                <td class="j-td text-center">
                    <EclipseButton :disabled="!canApproveWorkflowStepApplications(step)" :options="buttonOptions(enrolment)" :show-only-icon="true" />
                </td>
            </tr>
        </tbody>
    </table>
</template>
