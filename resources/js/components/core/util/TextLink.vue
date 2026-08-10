<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

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
        :class="
            cn(
                'text-primary decoration-primary/50 hover:text-primary focus-visible:ring-ring cursor-pointer rounded-sm font-medium underline-offset-4 transition-colors duration-300 ease-out focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none dark:hover:brightness-110',
                classes,
            )
        "
    >
        <slot />
        <span v-if="title">{{ title }}</span>
    </Link>
</template>
