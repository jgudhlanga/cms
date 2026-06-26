<script setup lang="ts">
import LogoutButton from '@/components/auth/LogoutButton.vue';
import RemoveImpersonationButton from '@/components/auth/RemoveImpersonationButton.vue';
import AppLogo from '@/components/core/image/AppLogo.vue';
import HeaderActionGroup from '@/components/core/page/HeaderActionGroup.vue';
import AppPreferencesSheet from '@/components/core/preferences/AppPreferencesSheet.vue';
import EnvironmentBadge from '@/components/core/util/EnvironmentBadge.vue';
import Heading from '@/components/core/util/Heading.vue';
import { useUtils } from '@/composables/core/useUtils';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';

const props = withDefaults(
    defineProps<{
        pageTitle?: string | null;
    }>(),
    {
        pageTitle: null,
    },
);

const page = usePage<PageProps>();
const { isItTrue } = useUtils();

const headerTitle = () => props.pageTitle ?? page.props.auth.user.attributes?.name ?? '';
</script>

<template>
    <nav
        class="fixed top-0 right-0 left-0 z-50 w-full border-b border-border bg-background/95 px-4 text-foreground shadow-md backdrop-blur-sm supports-backdrop-filter:bg-background/80 sm:px-6 lg:px-10 dark:shadow-sm"
    >
        <div class="flex w-full items-center justify-between gap-3 py-3 md:mx-auto md:w-7/8">
            <div class="flex size-8 shrink-0 items-center justify-start rounded-full border border-border">
                <AppLogo class="shrink-0 rounded-full" />
            </div>
            <Heading class="min-w-0 truncate" :title="headerTitle()" />
            <div class="flex items-center gap-2 pr-2 sm:pr-4">
                <RemoveImpersonationButton v-if="isItTrue(page.props.auth.impersonating)" />
                <EnvironmentBadge />
                <HeaderActionGroup>
                    <LogoutButton />
                    <AppPreferencesSheet />
                </HeaderActionGroup>
            </div>
        </div>
    </nav>
</template>
