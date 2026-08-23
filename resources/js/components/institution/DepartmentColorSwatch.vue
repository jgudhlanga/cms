<script setup lang="ts">
import { resolveDepartmentColor } from '@/lib/departmentColor';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        colorCode?: string | null;
        departmentName?: string | null;
        sizeClass?: string;
        class?: string;
    }>(),
    {
        sizeClass: 'h-3 w-3',
    },
);

const backgroundColor = computed(() => resolveDepartmentColor(props.colorCode, props.departmentName, 1));
</script>

<template>
    <span
        class="inline-block shrink-0 rounded-[2px]"
        :class="cn(sizeClass, $props.class)"
        :style="{ backgroundColor }"
        aria-hidden="true"
    />
</template>
