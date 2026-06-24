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
    <div class="mb-6">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <button
                v-for="option in pathOptions"
                :key="option.id"
                type="button"
                class="rounded-xl border px-3 py-2.5 text-sm font-medium transition"
                :class="
                    activePath === option.id
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'border-border bg-card text-muted-foreground hover:border-primary/40'
                "
                @click="emit('switch-path', option.id)"
            >
                {{ $t(option.labelKey) }}
            </button>
        </div>
    </div>
</template>
