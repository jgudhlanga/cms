<script setup lang="ts">
import { RadioGroupOption } from '@/types/forms';

interface Props {
    options: RadioGroupOption[];
    selectedGradeId?: string | null;
    disabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    selectedGradeId: null,
    disabled: false,
});

const emit = defineEmits<{
    select: [gradeId: string];
}>();

const gradeIdFromOption = (option: RadioGroupOption): string => String(option.value).split('|')[1] ?? '';

const isSelected = (option: RadioGroupOption, selectedGradeId: string | null): boolean =>
    selectedGradeId === gradeIdFromOption(option);
</script>

<template>
    <div class="inline-flex flex-wrap gap-2">
        <button
            v-for="option in options"
            :key="option.inputId"
            type="button"
            class="flex h-9 min-w-11 items-center justify-center rounded-md border px-3 text-sm font-semibold transition-colors"
            :class="
                isSelected(option, selectedGradeId ?? null)
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-border bg-background text-foreground hover:bg-muted'
            "
            :disabled="disabled"
            @click="emit('select', gradeIdFromOption(option))"
        >
            {{ option.label }}
        </button>
    </div>
</template>
