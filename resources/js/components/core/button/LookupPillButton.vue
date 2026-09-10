<script setup lang="ts">
import { Search } from 'lucide-vue-next';

defineProps<{
    label: string;
}>();

const emit = defineEmits<{
    click: [];
}>();
</script>

<template>
    <!-- Same pill geometry and text scale as BackNavigationButton, filled so the lookup reads as the primary action. -->
    <button
        type="button"
        class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border border-primary bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
        @click="emit('click')"
    >
        <Search class="h-3.5 w-3.5 shrink-0" />
        {{ label }}
    </button>
</template>
