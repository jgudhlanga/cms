<script setup lang="ts">
import { StepperDescription, StepperTitle } from '@/components/ui/stepper';

defineProps<{ state: any; title: string; description?: string }>();
</script>

<template>
    <div class="mt-5 flex flex-col items-center text-center">
        <StepperTitle :class="[state === 'active' && 'text-primary']" class="text-sm font-semibold transition lg:text-base">
            {{ title }}
        </StepperTitle>
        <StepperDescription
            v-if="description"
            :class="[state === 'active' && 'text-primary']"
            class="text-muted-foreground sr-only text-xs transition md:not-sr-only lg:text-sm"
        >
            {{ description }}
        </StepperDescription>
    </div>
</template>
