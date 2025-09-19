<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
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
const { enrolment } = props;
const field = props.type === 'registration' ? 'registrationFeePaid' : 'tuitionFeePaid';
const isPaid = computed(() => isItTrue(props.enrolment.attributes[field]));
</script>

<template>
    <div class="flex items-center justify-center space-x-2 rounded-full">
        <template v-if="isPaid">
            <BaseButton
                :size="ButtonSize.xs"
                classes="rounded-full"
                :title="`${$t('trans.confirm')} (${formatCurrency(String(enrolment?.relationships?.registrationReceipt?.attributes?.amount) ?? 0)})`"
                :variant="ColorVariant.fuchsia_outline"
            />
        </template>
        <template v-else>
            <span class="text-destructive text-sm lowercase">{{ $t('trans.no_payment') }}</span>
        </template>
    </div>
</template>
