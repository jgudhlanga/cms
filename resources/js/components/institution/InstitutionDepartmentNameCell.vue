<script setup lang="ts">
import DepartmentColorSwatch from '@/components/institution/DepartmentColorSwatch.vue';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';

defineProps<{
    departmentName: string;
    colorCode?: string | null;
    expanded: boolean;
}>();

defineEmits<{
    toggle: [];
}>();
</script>

<template>
    <button
        type="button"
        class="group flex max-w-full items-center gap-2 text-left"
        :aria-expanded="expanded"
        @click="$emit('toggle')"
    >
        <DepartmentColorSwatch
            :color-code="colorCode"
            :department-name="departmentName"
            size-class="h-3.5 w-3.5"
        />
        <span class="truncate text-xs font-semibold uppercase tracking-wide text-foreground group-hover:text-primary">
            {{ departmentName }}
        </span>
        <ChevronDown v-if="expanded" class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
        <ChevronRight v-else class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
    </button>
</template>
