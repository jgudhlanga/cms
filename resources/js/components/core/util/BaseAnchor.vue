<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    href: string;
    title: string;
    classes?: string;
    target?: string;
}
withDefaults(defineProps<Props>(), {
    target: '_blank',
});
</script>

<template>
    <a :href="href" :class="cn('text-primary font-bold hover:text-blue-700', classes)" v-bind="$attrs" :target="target">
        {{ title }}
    </a>
</template>
