<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { Enrolment } from '@/types/enrolments';

interface Props {
    enrolment: Enrolment;
}
const props = defineProps<Props>();

const { enrolment } = props;

const { onPaymentProofModal } = useStudentApplications();
</script>

<template>
    <BaseButton
        @click="() => onPaymentProofModal({ enrolmentId: enrolment.id, url: enrolment?.attributes?.applicationFeeProofOfPaymentUrl ?? '' })"
        :title="$t('trans.preview')"
        :size="ButtonSize.xs"
        classes="rounded-full lowercase"
        :variant="ColorVariant.fuchsia_outline"
    />
</template>
