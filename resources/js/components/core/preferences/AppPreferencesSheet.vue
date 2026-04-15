<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import Switch from '@/components/ui/switch/Switch.vue';
import { useUserPreference } from '@/composables/core/useUserPreference';
import { usePreferencesStore } from '@/store/core/preferences.store';
import { SlidersHorizontal } from 'lucide-vue-next';
import { ref } from 'vue';

const preferencesStore = usePreferencesStore();
const { persistSidebarState } = useUserPreference();
const isPreferencesDrawerOpen = ref(false);

const updateSidebarState = (open: boolean): void => {
    preferencesStore.setSideBarState(open);
    void persistSidebarState(open);
};
</script>

<template>
    <Button
        class="fixed right-4 top-1/2 z-40 size-10 -translate-y-1/2 rounded-full border border-fuchsia-700 bg-fuchsia-600 text-white shadow-md hover:bg-fuchsia-700 hover:text-white"
        size="icon"
        variant="outline"
        @click="isPreferencesDrawerOpen = true"
    >
        <SlidersHorizontal class="size-4" />
        <span class="sr-only">{{ $t('trans.open_preferences') }}</span>
    </Button>
    <Sheet v-model:open="isPreferencesDrawerOpen">
        <SheetContent side="right" class="w-[420px] p-0 sm:max-w-[420px]">
            <SheetHeader class="border-b bg-muted/30 px-6 py-5">
                <SheetTitle>{{ $t('trans.user_preferences') }}</SheetTitle>
                <SheetDescription class="mt-1 text-sm">{{ $t('trans.preferences_panel_description') }}</SheetDescription>
            </SheetHeader>
            <div class="space-y-4 px-6 py-6">
                <div class="rounded-xl border border-primary/25 bg-card p-4 shadow-xs">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p class="font-medium leading-none">{{ $t('trans.sidebar_expanded') }}</p>
                            <p class="text-sm text-muted-foreground">{{ $t('trans.sidebar_expanded_description') }}</p>
                        </div>
                        <Switch
                            class="mt-0.5"
                            :model-value="preferencesStore.sideBarState"
                            @update:model-value="updateSidebarState"
                        />
                    </div>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
