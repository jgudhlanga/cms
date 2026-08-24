<script setup lang="ts">
import { cn } from '@/lib/utils';
import { trans } from 'laravel-vue-i18n';

const model = defineModel<boolean | null>({ required: true });

defineProps<{
    label: string;
    id: string;
}>();

const select = (value: boolean) => {
    model.value = value;
};

const buttonClass = (selected: boolean, selectedTone: 'yes' | 'no') =>
    cn(
        'border-r border-border px-3 py-1.5 text-xs font-semibold transition-colors last:border-r-0',
        selected && selectedTone === 'yes' && 'bg-emerald-100 text-emerald-900',
        selected && selectedTone === 'no' && 'bg-red-100 text-red-900',
        !selected && 'bg-card text-foreground hover:bg-muted',
    );
</script>

<template>
    <div class="flex items-center justify-between gap-4 border-b border-border py-2.5 last:border-b-0">
        <p class="min-w-0 flex-1 text-sm font-medium text-foreground">{{ label }}</p>

        <div
            class="inline-flex shrink-0 overflow-hidden rounded-md border border-border"
            role="group"
            :aria-label="label"
        >
            <button
                type="button"
                :id="`${id}-yes`"
                :class="buttonClass(model === true, 'yes')"
                :aria-pressed="model === true"
                @click="select(true)"
            >
                {{ trans('enrolments.label_matches') }}
            </button>
            <button
                type="button"
                :id="`${id}-no`"
                :class="buttonClass(model === false, 'no')"
                :aria-pressed="model === false"
                @click="select(false)"
            >
                {{ trans('enrolments.label_does_not_match') }}
            </button>
        </div>
    </div>
</template>
