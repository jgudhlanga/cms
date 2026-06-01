<script setup lang="ts">
defineOptions({ inheritAttrs: false });

import LogoutButton from '@/components/auth/LogoutButton.vue';
import RemoveImpersonationButton from '@/components/auth/RemoveImpersonationButton.vue';
import Breadcrumbs from '@/components/core/util/Breadcrumbs.vue';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useUtils } from '@/composables/core/useUtils';
import { PageProps } from '@/types';
import { BreadcrumbItemInterface } from '@/types/ui';
import BackNavigationButton from '@/components/core/button/BackNavigationButton.vue';
import AppPreferencesSheet from '@/components/core/preferences/AppPreferencesSheet.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, useSlots } from 'vue';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItemInterface[];
    backUrl?: string;
}>();
const page = usePage<PageProps>();
const { isItTrue } = useUtils();
const showBackNavigation = computed((): boolean => {
    return Boolean(props.backUrl) && (props.breadcrumbs?.length ?? 0) > 1;
});
const backNavigationUrl = computed((): string => {
    return props.backUrl ?? '#';
});

const slots = useSlots();

const hasBackNavigationLeading = computed((): boolean => {
    return Boolean(slots.backNavigationLeading);
});

const showBackNavigationRow = computed((): boolean => {
    return showBackNavigation.value || hasBackNavigationLeading.value;
});

const backNavigationRowJustifyClass = computed((): string => {
    if (hasBackNavigationLeading.value && showBackNavigation.value) {
        return 'justify-between';
    }

    if (showBackNavigation.value) {
        return 'justify-end';
    }

    return 'justify-start';
});
</script>
<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:gap-4"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2 px-2 sm:px-4">
            <SidebarTrigger class="text-primary -ml-2 shrink-0 font-bold sm:-ml-4" />
            <Separator orientation="vertical" class="mr-1 hidden h-4 sm:mr-2 sm:block" />
            <Breadcrumbs :breadcrumbs="breadcrumbs ?? []" />
        </div>
        <div class="flex shrink-0 items-center justify-center space-x-2 sm:space-x-4">
            <RemoveImpersonationButton v-if="isItTrue(page.props.auth.impersonating)" />
            <LogoutButton />
            <AppPreferencesSheet />
        </div>
    </header>
    <div class="flex h-full min-w-0 w-full max-w-full flex-col overflow-x-clip pb-10">
        <div
            v-if="showBackNavigationRow"
            class="mb-10 flex items-center gap-4"
            :class="backNavigationRowJustifyClass"
        >
            <div v-if="hasBackNavigationLeading" class="min-w-0 flex-1">
                <slot name="backNavigationLeading" />
            </div>
            <div v-if="showBackNavigation" class="shrink-0">
                <BackNavigationButton :url="backNavigationUrl" />
            </div>
        </div>
        <slot />
    </div>
</template>
