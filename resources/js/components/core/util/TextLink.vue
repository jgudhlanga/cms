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
                'hover:text-accent-foreground text-primary decoration-persian-200 cursor-pointer underline-offset-4 transition-colors duration-300 ease-out',
                classes,
            )
        "
    >
        <slot />
        <span v-if="title">{{ title }}</span>
    </Link>
</template>
