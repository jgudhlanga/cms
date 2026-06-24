<script setup lang="ts">
import { resolveIsDark, useAppearance } from '@/composables/core/useAppearance';
import { cn } from '@/lib/utils';
import { usePage } from '@inertiajs/vue3';
import { Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'default' | 'on-dark';
    }>(),
    {
        variant: 'default',
    },
);

const { appearance, updateAppearance } = useAppearance();
const page = usePage();

const systemPrefersDarkHint = (): boolean | undefined => {
    const shared = page.props.appearance as { systemPrefersDark?: boolean } | undefined;

    return shared?.systemPrefersDark;
};

const isDark = computed(() => resolveIsDark(appearance.value, systemPrefersDarkHint()));

const toggleAppearance = () => {
    updateAppearance(isDark.value ? 'light' : 'dark');
};

const buttonClass = computed(() =>
    cn(
        'flex size-10 items-center justify-center transition-colors',
        props.variant === 'on-dark'
            ? 'text-white hover:text-white/80'
            : 'text-primary hover:text-primary/80 dark:text-foreground dark:hover:text-foreground/80',
    ),
);
</script>

<template>
    <button
        type="button"
        :class="buttonClass"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleAppearance"
    >
        <Moon v-if="!isDark" class="size-6" :stroke-width="2.25" />
        <Sun v-else class="size-6" :stroke-width="2.25" />
    </button>
</template>
