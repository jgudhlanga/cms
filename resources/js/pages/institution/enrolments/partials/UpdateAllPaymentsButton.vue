<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { BulkUpdatePaymentStatus, Enrolment } from '@/types/enrolments';
import { computed } from 'vue';
import BaseButton from '../../../../components/core/button/BaseButton.vue';

interface Props {
    departmentId: string;
    enrolments: Enrolment[];
    params: BulkUpdatePaymentStatus;
}

const props = defineProps<Props>();
const { params, departmentId, enrolments } = props;

const { registrationFeePaymentRequired, tuitionFeePaymentRequired, bulkUpdatePaymentStatus } = useStudentApplications();

const feeType = computed(() => {
    if (registrationFeePaymentRequired(params.step as DepartmentApplicationStep)) {
        return 'registration';
    } else if (tuitionFeePaymentRequired(params.step as DepartmentApplicationStep)) {
        return 'tuition';
    }
    return null;
});

const updateAllPayments = (value: boolean) => {
    if (feeType.value === 'registration') {
        bulkUpdatePaymentStatus(departmentId, params.step!, enrolments, {
            intake_period_id: params.intake_period_id,
            department_level_id: params.department_level_id,
            mode_of_study_id: params.mode_of_study_id,
            current_step_id: params?.step?.id.toString() ?? '',
            field_to_update: feeType.value == 'registration' ? 'registration_fee_confirmed' : 'tuition_fee_confirmed',
            field_value: value,
        });
    }
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <BaseButton :variant="ColorVariant.fuchsia" :size="ButtonSize.xs" classes="rounded-full">
                {{ $t('trans.update_all_payments') }}
                <component :is="icons[IconName.dots_vertical]" size="12" />
            </BaseButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent>
            <DropdownMenuGroup>
                <DropdownMenuItem>
                    <button class="flex w-full items-center space-x-2" @click="updateAllPayments(true)">
                        <component :is="icons[IconName.check_done]" size="12" />
                        <span>{{ `${$t('trans.acknowledge')} ${$t('trans.all')} ${$t('trans.paid')}` }}</span>
                    </button>
                </DropdownMenuItem>
                <DropdownMenuItem>
                    <button class="flex w-full items-center space-x-2" @click="updateAllPayments(false)">
                        <component :is="icons[IconName.check_done]" size="12" />
                        <span>{{ `${$t('trans.reject')} ${$t('trans.all')} ${$t('trans.paid')}` }}</span>
                    </button>
                </DropdownMenuItem>
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
