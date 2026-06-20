<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';

interface Props {
    stepIndex: number;
    prevStepAction: () => void;
    nextStepAction: () => void;
    previousDisabled: boolean;
    processing?: boolean;
    maxStep: number;
}

defineProps<Props>();
</script>

<template>
    <div class="mt-4 flex items-center justify-between">
        <BaseButton type="button" :disabled="previousDisabled" :variant="ColorVariant.shade" @click="prevStepAction()" :size="ButtonSize.lg">
            {{ $t('trans.back') }}
        </BaseButton>
        <div class="flex items-center gap-3">
            <BaseButton v-if="stepIndex !== maxStep" type="button" @click="nextStepAction()" :size="ButtonSize.lg">
                {{ $t('trans.next') }}
            </BaseButton>
            <BaseButton :processing="processing" :size="ButtonSize.lg" v-if="stepIndex === maxStep" type="submit">
                {{ $t('trans.submit') }}
            </BaseButton>
        </div>
    </div>
</template>
