<script setup lang="ts">
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import { useStudentApplications } from '@/composables/students/useStudentApplications';

interface Props {
    step: DepartmentApplicationStep;
}

defineProps<Props>();

const { onUploadPopModal } = useStudentApplications();

</script>

<template>
    <div class="flex flex-col" v-if="step?.relationships?.metadata?.actions">
        <div class="flex flex-col space-y-3" v-for="action in step?.relationships?.metadata?.actions" :key="action.action">
            <template v-if="action.action.toLowerCase() == 'upload-receipt'">
                <BaseButton @click="onUploadPopModal" :variant="ColorVariant.danger_outline" classes="w-1/3 mt-3 rounded-full">{{ $t('trans.upload_proof') }}</BaseButton>
            </template>
        </div>
    </div>
</template>
