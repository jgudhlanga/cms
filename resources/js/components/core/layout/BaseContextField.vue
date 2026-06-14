<script setup lang="ts">
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        label: string;
        icon?: IconName;
        hint?: string;
        required?: boolean;
        class?: string;
    }>(),
    {
        required: false,
    },
);
</script>

<template>
    <div :class="cn('rounded-lg border border-border/60 bg-muted/20 p-3', $props.class)">
        <div class="mb-2 flex items-center gap-2">
            <component v-if="icon" :is="icons[icon]" class="h-4 w-4 shrink-0 text-muted-foreground" />
            <span class="text-sm font-medium text-foreground">
                {{ label }}<RequiredIndicator v-if="required" />
            </span>
        </div>
        <p v-if="hint" class="mb-2 text-xs text-muted-foreground">
            {{ hint }}
        </p>
        <slot />
    </div>
</template>
