<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { Enrolment } from '@/types/enrolments';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { useStudentApplications } from '@/composables/students/useStudentApplications';

interface Props {
    enrolment: Enrolment;
}
const props = defineProps<Props>();

const { markApplicationFeeAsPaid } = useStudentApplications();

const { isItTrue } = useUtils();

const isPaid = computed(() => isItTrue(props.enrolment.attributes.applicationFeePaid));

const iconName = computed(() => (isPaid.value ? IconName.check : IconName.close));
const iconClass = computed(() => (isPaid.value ? 'text-text-green-600' : 'text-destructive'));
const buttonTitle = computed(() => (isPaid.value ? trans('trans.mark_unpaid') : trans('trans.mark_paid')));
const buttonVariant = computed(() => (isPaid.value ? ColorVariant.danger_outline : ColorVariant.success_outline));
</script>

<template>
    <div class="flex items-center justify-center space-x-2">
        <BaseIcon :name="iconName" :class="['size-5', iconClass]" />
        <BaseButton
            @click="markApplicationFeeAsPaid(enrolment, isPaid)"
            :title="buttonTitle"
            :size="ButtonSize.xs"
            classes="rounded-full lowercase"
            :variant="buttonVariant"
        />
    </div>
</template>
