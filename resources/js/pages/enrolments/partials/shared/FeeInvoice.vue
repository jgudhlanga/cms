<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';

interface Props {
    tuition?: string | number;
    autoCardFee?: string | number;
    partTimeLevy?: string | number;
}
defineProps<Props>();

const { formatCurrency } = useUtils();
</script>

<template>
    <BaseCard title="Invoice" description="What the student should pay" color-variant="purple-500">
        <div class="flex items-center space-x-3" v-if="tuition">
            <Label>Tuition:</Label>
            <div>{{ `USD${formatCurrency(String(tuition))}` }}</div>
        </div>
        <div class="flex items-center space-x-3" v-if="autoCardFee">
            <Label>AutoCard fee:</Label>
            <div>{{ `USD${formatCurrency(String(autoCardFee))}` }}</div>
        </div>
        <div class="flex items-center space-x-3" v-if="partTimeLevy">
            <Label>Part time levy:</Label>
            <div>{{ `USD${formatCurrency(String(partTimeLevy))}` }}</div>
        </div>
    </BaseCard>
</template>
