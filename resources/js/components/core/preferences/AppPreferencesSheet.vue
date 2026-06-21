<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import AppearanceTabs from '@/components/core/util/AppearanceTabs.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useSidebar } from '@/components/ui/sidebar';
import Switch from '@/components/ui/switch/Switch.vue';
import { useUserPreference } from '@/composables/core/useUserPreference';
import { IconName } from '@/enums/icons';
import { usePreferencesStore } from '@/store/core/preferences.store';
import { computed, ref } from 'vue';

const preferencesStore = usePreferencesStore();
const { isMobile } = useSidebar();
const { persistSidebarState, persistLocale } = useUserPreference();
const isPreferencesDrawerOpen = ref(false);
const localeOptions = [{ value: 'en', label: 'English' }];
const selectedLocale = computed({
    get: () => preferencesStore.locale ?? 'en',
    set: (value: string) => {
        preferencesStore.setLocale(value);
        void persistLocale(value);
    },
});

const updateSidebarState = (open: boolean): void => {
    if (isMobile.value) {
        return;
    }

    preferencesStore.setSideBarState(open);
    void persistSidebarState(open);
};
</script>

<template>
    <BaseTooltip :content="$t('trans.open_preferences')">
        <button
            type="button"
            class="flex items-center"
            :aria-label="$t('trans.open_preferences')"
            @click="isPreferencesDrawerOpen = true"
        >
            <IconButton :icon="IconName.settings" tone="header-primary" />
        </button>
    </BaseTooltip>
    <Sheet v-if="isPreferencesDrawerOpen" v-model:open="isPreferencesDrawerOpen">
        <SheetContent side="right" class="w-[420px] p-0 sm:max-w-[420px]">
            <SheetHeader class="bg-muted/30 border-b px-6 py-5">
                <SheetTitle>{{ $t('trans.user_preferences') }}</SheetTitle>
                <SheetDescription class="mt-1 text-sm">{{ $t('trans.preferences_panel_description') }}</SheetDescription>
            </SheetHeader>
            <div class="space-y-4 px-6 py-6">
                <div class="border-primary/25 bg-card rounded-xl border p-4 shadow-xs">
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <p class="leading-none font-medium">{{ $t('trans.appearance') }}</p>
                            <p class="text-muted-foreground text-sm">{{ $t('trans.appearance_description') }}</p>
                        </div>
                        <AppearanceTabs class="w-full justify-center" />
                    </div>
                </div>
                <div
                    class="border-primary/25 bg-card rounded-xl border p-4 shadow-xs"
                    :class="{ 'opacity-60': isMobile }"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p class="leading-none font-medium">{{ $t('trans.sidebar_expanded') }}</p>
                            <p class="text-muted-foreground text-sm">{{ $t('trans.sidebar_expanded_description') }}</p>
                        </div>
                        <Switch
                            class="mt-0.5"
                            :model-value="preferencesStore.sideBarState"
                            :disabled="isMobile"
                            @update:model-value="updateSidebarState"
                        />
                    </div>
                </div>
                <div class="border-primary/25 bg-card rounded-xl border p-4 shadow-xs">
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <p class="leading-none font-medium">{{ $tChoice('trans.language', 1) }}</p>
                            <p class="text-muted-foreground text-sm">{{ $t('trans.ui_english_is_currently_the_only_available_language') }}</p>
                        </div>
                        <BaseSelect
                            v-model="selectedLocale"
                            class="w-full"
                            label=""
                            :options="localeOptions"
                            :is-searchable="false"
                            :is-clearable="false"
                            :is-disabled="true"
                        />
                    </div>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
