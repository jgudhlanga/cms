<script setup lang="ts">
import { Enrolment } from '@/types/enrolments';
import { ButtonSize } from '@/enums/buttons';
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import { useStudentApplications } from '@/composables/students/useStudentApplications';

interface Props {
    enrolment: Enrolment;
}
const props = defineProps<Props>();

const { enrolment } = props;

const {onPaymentProofModal} = useStudentApplications();

</script>

<template>
    <BaseButton
        @click="() => onPaymentProofModal({enrolmentId: enrolment.id, url: enrolment?.attributes?.applicationFeeProofOfPaymentUrl ?? ''})"
        :title="$t('trans.preview')"
        :size="ButtonSize.xs"
        classes="rounded-full lowercase"
        :variant="ColorVariant.fuchsia_outline"
    />
</template>
