<script setup lang="ts">
import { useStudentProfile } from '@/composables/students/useStudentProfile';
import { useUtils } from '@/composables/core/useUtils';
import { hasAbility } from '@/lib/permissions';
import { icons } from '@/lib/icons';
import { IconName } from '@/enums/icons';
import { computed } from 'vue';

const { portalSidebarProfileTabs } = useStudentProfile();
const { navigateTo } = useUtils();

const quickActions = computed(() => {
    const tabs = portalSidebarProfileTabs().filter((tab) => tab.show && tab.routeName);
    const profileActions = tabs
        .filter((tab) => tab.value !== 'authentication')
        .map((tab) => ({
            label: tab.transLabel(),
            icon: icons[tab.icon],
            url: route(tab.routeName!),
        }));

    const oLevelAction = hasAbility('manageOwnStudentAcademicDetails:students')
        ? [{
            label: 'O Levels',
            icon: icons[IconName.award],
            url: route('portal.list-o-levels'),
        }]
        : [];

    const authTab = tabs.find((tab) => tab.value === 'authentication');
    const authAction = authTab
        ? [{
            label: authTab.transLabel(),
            icon: icons[authTab.icon],
            url: route(authTab.routeName!),
        }]
        : [];

    return [...profileActions, ...oLevelAction, ...authAction];
});

const openAction = (url: string): void => {
    navigateTo(url);
};
</script>

<template>
    <section class="w-full min-w-0 space-y-1.5">
        <h2 class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            {{ $t('students.dashboard_quick_actions') }}
        </h2>

        <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <button
                v-for="action in quickActions"
                :key="action.url"
                type="button"
                class="flex min-w-0 items-center gap-2 rounded-lg border border-border bg-card px-2 py-1.5 text-left shadow-sm transition-colors hover:bg-muted/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                @click="openAction(action.url)"
            >
                <component :is="action.icon" class="h-3.5 w-3.5 shrink-0 text-primary" />
                <span class="min-w-0 wrap-break-word text-[11px] font-medium leading-snug text-foreground">{{ action.label }}</span>
            </button>
        </div>
    </section>
</template>
