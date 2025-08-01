<script setup lang="ts">
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';

interface Props {
    step: DepartmentApplicationStep;
    action?: () => void;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col" v-if="step?.relationships?.metadata?.actions">
        <div class="flex flex-col space-y-3" v-for="action in step?.relationships?.metadata?.actions" :key="action.action">
            <template v-if="action.action.toLowerCase() == 'upload-receipt'">
                <BaseButton :variant="ColorVariant.danger_outline" classes="w-1/3 mt-2">{{ $t('trans.upload_proof') }}</BaseButton>
            </template>
        </div>
    </div>
</template>
