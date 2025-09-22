<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { cn } from '@/lib/utils';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { Enrolment } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    enrolment: Enrolment;
    step: DepartmentApplicationStep;
    type: 'registration' | 'tuition';
}
const props = defineProps<Props>();

const { isItTrue, formatCurrency } = useUtils();
const { confirmRegistrationFeeAsPaid, confirmTuitionFeeAsPaid } = useStudentApplications();
const { enrolment } = props;

const isPaid = computed(() => {
    if (props.type === 'registration') {
        return Number(enrolment?.relationships?.registrationReceipt?.attributes?.amount) > 0;
    } else {
        return Number(enrolment?.relationships?.tuitionReceipt?.attributes?.amount) > 0;
    }
});
const field = props.type === 'registration' ? 'registrationFeeConfirmed' : 'tuitionFeeConfirmed';
const buttonTitle = computed(() => (isItTrue(props.enrolment.attributes[field]) ? 'reject' : 'acknowledge'));
const buttonIcon = computed(() => (isItTrue(props.enrolment.attributes[field]) ? IconName.close : IconName.check));
const textColor = computed(() => (isItTrue(props.enrolment.attributes[field]) ? 'text-green-600' : 'text-red-600'));
const buttonVariant = computed(() => (isItTrue(props.enrolment.attributes[field]) ? ColorVariant.danger_outline : ColorVariant.success_outline));

const confirm = () => {
    if (props.type === 'registration') {
        confirmRegistrationFeeAsPaid(enrolment, isItTrue(props.enrolment.attributes[field]));
    } else {
        confirmTuitionFeeAsPaid(enrolment, isItTrue(props.enrolment.attributes[field]));
    }
};
</script>

<template>
    <div class="flex items-center justify-center space-x-2 rounded-full">
        <template v-if="isPaid">
            <div :class="cn('uppercase', textColor)">
                {{ $t('trans.paid') }} - {{ formatCurrency(String(enrolment?.relationships?.registrationReceipt?.attributes?.amount) ?? 0) }}
            </div>
            <BaseButton
                @click="confirm"
                :size="ButtonSize.xs"
                classes="rounded-full capitalize"
                :title="`${$t('trans.' + buttonTitle)}`"
                :variant="buttonVariant"
            >
                <BaseIcon :name="buttonIcon" />
            </BaseButton>
        </template>
        <template v-else>
            <span class="text-destructive text-sm lowercase">{{ $t('trans.no_payment') }}</span>
        </template>
    </div>
</template>
