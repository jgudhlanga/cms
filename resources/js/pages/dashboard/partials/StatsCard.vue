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
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="px-3 py-1">
            <div class="flex items-center">
                <div :class="`flex-shrink-0`">
                    <div v-html="svgIcons[icon]"></div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">{{ title }}</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ value }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</template>
