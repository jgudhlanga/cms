<script setup lang="ts">
import { ChevronDown, ChevronRight } from 'lucide-vue-next';

defineProps<{
    expanded: boolean;
}>();

defineEmits<{
    toggle: [];
}>();
</script>

<template>
    <button
        type="button"
        class="ml-auto inline-flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
        :aria-expanded="expanded"
        :aria-label="expanded ? $t('trans.ui_collapse_row') : $t('trans.ui_expand_row')"
        @click.stop="$emit('toggle')"
    >
        <ChevronDown v-if="expanded" class="h-4 w-4" />
        <ChevronRight v-else class="h-4 w-4" />
    </button>
</template>
