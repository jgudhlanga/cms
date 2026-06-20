<script setup lang="ts">
import { svgIcons } from '@/lib/svgIcons';

interface Props {
    title: string;
    value: string | number;
    icon: string;
    iconBgColor: string;
}

defineProps<Props>();
</script>

<template>
    <div class="overflow-hidden rounded-lg bg-card shadow">
        <div class="px-3 py-1">
            <div class="flex items-center">
                <div :class="`shrink-0`">
                    <div v-html="svgIcons[icon]"></div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <div>
                        <div class="truncate text-sm font-medium text-muted-foreground">{{ title }}</div>
                        <div class="text-lg font-medium text-foreground">{{ value }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
