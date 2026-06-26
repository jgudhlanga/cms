<script setup lang="ts">
import { evaluatePasswordRules } from '@/lib/passwordRules';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps<{
    password: string;
}>();

const rules = computed(() => evaluatePasswordRules(props.password));
const isVisible = computed(() => props.password.length > 0);
</script>

<template>
    <div
        v-if="isVisible"
        role="status"
        aria-live="polite"
        class="mt-1.5 space-y-1"
    >
        <div class="flex gap-0.5">
            <div
                v-for="rule in rules"
                :key="rule.id"
                :class="cn('h-1 flex-1 rounded-full transition-colors', rule.passed ? 'bg-emerald-500' : 'bg-red-500')"
                :aria-label="$t(rule.ariaLabelKey)"
            />
        </div>
        <div class="flex justify-between gap-0.5 px-0.5">
            <span
                v-for="rule in rules"
                :key="`label-${rule.id}`"
                :class="cn(
                    'flex-1 text-center text-[10px] font-medium leading-tight transition-colors',
                    rule.passed ? 'text-foreground' : 'text-red-600 dark:text-red-400',
                )"
                :aria-label="$t(rule.ariaLabelKey)"
            >
                {{ rule.shortLabel }}
            </span>
        </div>
    </div>
</template>
