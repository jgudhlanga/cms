<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';

interface Props {
    href: string;
    tabindex?: number;
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    as?: string;
    title?: string;
    classes?: string;
}

withDefaults(defineProps<Props>(), {
    method: 'get',
});
</script>

<template>
    <Link
        :href="href"
        :method="method"
        :tabindex="tabindex"
        :as="as"
        :class="cn('cursor-pointer font-medium text-primary underline-offset-4 decoration-primary/50 transition-colors duration-300 ease-out hover:text-primary dark:hover:brightness-110', classes)"
    >
        <slot />
        <span v-if="title">{{ title }}</span>
    </Link>
</template>
