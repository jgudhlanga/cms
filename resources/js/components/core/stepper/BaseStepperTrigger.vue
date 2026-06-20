<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { StepperTrigger } from '@/components/ui/stepper';
import { IconName, icons } from '@/lib/icons';

defineProps<{ state: any }>();
</script>

<template>
    <StepperTrigger as-child>
        <Button
            :variant="state === 'completed' || state === 'active' ? 'default' : 'outline'"
            size="icon"
            class="z-10 shrink-0 rounded-full"
            :class="[state === 'active' && 'ring-ring ring-offset-background ring-2 ring-offset-2']"
            :disabled="state !== 'completed'"
        >
            <component :is="icons[IconName.check]" v-if="state === 'completed'" class="size-5" />
            <component :is="icons[IconName.circle]" v-if="state === 'active'" class="size-5" />
            <component :is="icons[IconName.dot]" v-if="state === 'inactive'" class="size-5" />
        </Button>
    </StepperTrigger>
</template>
