<script setup lang="ts">
import LogoutButton from '@/components/auth/LogoutButton.vue';
import RemoveImpersonationButton from '@/components/auth/RemoveImpersonationButton.vue';
import AppLogo from '@/components/core/image/AppLogo.vue';
import AppPreferencesSheet from '@/components/core/preferences/AppPreferencesSheet.vue';
import Heading from '@/components/core/util/Heading.vue';
import { useUtils } from '@/composables/core/useUtils';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';

const page = usePage<PageProps>();
const { isItTrue } = useUtils();
</script>

<template>
    <nav
        class="fixed top-0 right-0 left-0 z-50 w-full border-b border-border bg-background/95 px-10 text-foreground shadow-md backdrop-blur-sm supports-backdrop-filter:bg-background/80 dark:shadow-sm"
    >
        <div class="flex w-full items-center justify-between space-x-5 py-3 md:mx-auto md:w-7/8">
            <div class="flex size-8 items-center justify-start rounded-full border border-border">
                <AppLogo class="shrink-0 rounded-full" />
            </div>
            <Heading :title="page.props.auth.user.attributes?.name" />
            <div class="flex items-center space-x-4">
                <RemoveImpersonationButton v-if="isItTrue(page.props.auth.impersonating)" />
                <LogoutButton />
                <AppPreferencesSheet />
            </div>
        </div>
    </nav>
</template>
