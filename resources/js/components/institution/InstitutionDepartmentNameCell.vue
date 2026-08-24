<script setup lang="ts">
import DepartmentColorSwatch from '@/components/institution/DepartmentColorSwatch.vue';

defineProps<{
    departmentName: string;
    colorCode?: string | null;
}>();
</script>

<template>
    <div class="flex max-w-full items-center gap-2">
        <DepartmentColorSwatch
            :color-code="colorCode"
            :department-name="departmentName"
            size-class="h-3.5 w-3.5"
        />
        <span class="truncate text-xs font-semibold uppercase tracking-wide text-foreground">
            {{ departmentName }}
        </span>
    </div>
</template>
