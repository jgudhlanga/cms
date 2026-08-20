<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { ColorVariant } from '@/enums/colors';
import { WorkflowStep } from '@/types/settings';

interface Props {
    step: WorkflowStep;
    status: 'active' | 'completed' | 'pending';
}

defineProps<Props>();

const { onUploadPopModal, proofOfPaymentRequired } = useStudentApplications();
</script>

<template>
    <div class="flex flex-col" v-if="proofOfPaymentRequired(step) && status != 'completed'">
        <BaseButton @click="onUploadPopModal" :variant="ColorVariant.danger_outline" classes="w-1/3 mt-3 rounded-full">{{
            $t('trans.upload_proof')
        }}</BaseButton>
    </div>
</template>
