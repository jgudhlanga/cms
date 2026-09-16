<script setup lang="ts">
import ConfirmDialog from '@/components/core/modal/ConfirmDialog.vue';
import ErrorDialog from '@/components/core/modal/ErrorDialog.vue';
import AppPreferencesSheet from '@/components/core/preferences/AppPreferencesSheet.vue';
import AppSidebar from '@/components/core/sidebar/AppSidebar.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { useFlashAlerts } from '@/composables/core/useFlashAlerts';
import { useUserPreference } from '@/composables/core/useUserPreference';
import { usePreferencesStore } from '@/store/core/preferences.store';
import { BreadcrumbItemType } from '@/types/ui';
import { usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { computed, onMounted } from 'vue';
import { ModalsContainer } from 'vue-final-modal';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage();
useFlashAlerts();
const preferencesStore = usePreferencesStore();
const { hydratePreferenceOnce, persistSidebarState } = useUserPreference();
const isMobile = useMediaQuery('(max-width: 768px)');
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const updateSidebarState = (open: boolean): void => {
    if (isMobile.value) {
        return;
    }

    preferencesStore.setSideBarState(open);
    void persistSidebarState(open);
};

onMounted(async () => {
    if (!isAuthenticated.value) {
        preferencesStore.markHydrated();

        return;
    }

    await hydratePreferenceOnce();
});
</script>
<template>
    <SidebarProvider :open="preferencesStore.sideBarState" @update:open="updateSidebarState">
        <AppSidebar />
        <SidebarInset class="min-w-0 overflow-x-auto">
            <div class="flex h-full min-w-0 w-full max-w-full flex-1 flex-col gap-3 overflow-x-auto rounded-xl px-3 sm:gap-4 sm:px-5 lg:px-8">
                <slot />
            </div>
        </SidebarInset>
    </SidebarProvider>
    <ConfirmDialog />
    <ErrorDialog />
    <ModalsContainer />
</template>
