<script setup lang="ts">
type EnrollmentPath = 'zimbabwean' | 'returning' | 'international';

defineProps<{
    activePath: EnrollmentPath;
    pathOptions: { id: EnrollmentPath; labelKey: string }[];
}>();

const emit = defineEmits<{
    (e: 'switch-path', value: EnrollmentPath): void;
}>();
</script>

<template>
    <div class="mb-6 grid grid-cols-1 gap-2 sm:grid-cols-3">
        <button
            v-for="option in pathOptions"
            :key="option.id"
            type="button"
            class="rounded-xl border px-3 py-2.5 text-xs font-semibold uppercase transition sm:text-sm"
            :class="
                activePath === option.id
                    ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                    : 'border-border bg-card text-muted-foreground hover:border-primary/40 dark:shadow-none'
            "
            @click="emit('switch-path', option.id)"
        >
            {{ $t(option.labelKey) }}
        </button>
    </div>
</template>
