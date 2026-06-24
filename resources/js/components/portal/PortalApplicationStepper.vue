<script setup lang="ts">
import { cn } from '@/lib/utils';

type ApplicationStep = 'level' | 'fee' | 'apply';

const props = withDefaults(
    defineProps<{
        currentStep?: ApplicationStep;
    }>(),
    {
        currentStep: 'level',
    },
);

const steps = [
    { id: 'level', labelKey: 'trans.portal_application_step_level' },
    { id: 'fee', labelKey: 'trans.portal_application_step_fee' },
    { id: 'apply', labelKey: 'trans.portal_application_step_apply' },
] as const;

const stepIndex = (id: string) => steps.findIndex((step) => step.id === id);

const isComplete = (id: string) => stepIndex(props.currentStep) > stepIndex(id);

const isCurrent = (id: string) => props.currentStep === id;
</script>

<template>
    <nav aria-label="Application progress" class="mx-auto mb-6 w-full max-w-2xl overflow-x-auto px-4 sm:px-0">
        <ol class="flex min-w-max items-center gap-3">
            <li v-for="(step, index) in steps" :key="step.id" class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                        :class="
                            cn(
                                isCurrent(step.id) && 'bg-primary text-primary-foreground',
                                isComplete(step.id) && 'bg-primary/15 text-primary',
                                !isCurrent(step.id) && !isComplete(step.id) && 'bg-muted text-muted-foreground',
                            )
                        "
                    >
                        {{ index + 1 }}
                    </span>
                    <span class="text-sm font-medium" :class="isCurrent(step.id) ? 'text-foreground' : 'text-muted-foreground'">
                        {{ $t(step.labelKey) }}
                    </span>
                </div>
                <span v-if="index < steps.length - 1" class="h-px w-8 bg-border" aria-hidden="true" />
            </li>
        </ol>
    </nav>
</template>
