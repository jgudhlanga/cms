<script setup lang="ts">
import { portalSidebarProfileTabs } from '@/composables/students/useStudentProfileTabs';
import { useUtils } from '@/composables/core/useUtils';
import { hasAbility } from '@/lib/permissions';
import { icons } from '@/lib/icons';
import { IconName } from '@/enums/icons';
import { computed } from 'vue';

const { navigateTo } = useUtils();

const accentByKey: Record<string, string> = {
    basic_info: 'bg-violet-500/15 text-violet-600 dark:text-violet-400',
    programs: 'bg-teal-500/15 text-teal-600 dark:text-teal-400',
    applications: 'bg-pink-500/15 text-pink-600 dark:text-pink-400',
    financials: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    accommodations: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    documents: 'bg-blue-500/15 text-blue-600 dark:text-blue-400',
    o_levels: 'bg-red-500/15 text-red-600 dark:text-red-400',
    authentication: 'bg-sky-500/15 text-sky-600 dark:text-sky-400',
};

const defaultAccent = 'bg-primary/10 text-primary';

const quickActions = computed(() => {
    const tabs = portalSidebarProfileTabs().filter((tab) => tab.show && tab.routeName);
    const profileActions = tabs
        .filter((tab) => tab.value !== 'authentication')
        .map((tab) => ({
            key: tab.value,
            label: tab.transLabel(),
            icon: icons[tab.icon],
            accent: accentByKey[tab.value] ?? defaultAccent,
            url: route(tab.routeName!),
        }));

    const oLevelAction = hasAbility('manageOwnStudentAcademicDetails:students')
        ? [{
            key: 'o_levels',
            label: 'O Levels',
            icon: icons[IconName.award],
            accent: accentByKey.o_levels,
            url: route('portal.list-o-levels'),
        }]
        : [];

    const authTab = tabs.find((tab) => tab.value === 'authentication');
    const authAction = authTab
        ? [{
            key: authTab.value,
            label: authTab.transLabel(),
            icon: icons[authTab.icon],
            accent: accentByKey.authentication,
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
    <section class="w-full min-w-0 space-y-3">
        <div class="min-w-0">
            <h2 class="text-base font-semibold text-foreground">
                {{ $t('students.dashboard_quick_actions') }}
            </h2>
            <p class="mt-0.5 wrap-break-word text-sm text-muted-foreground">
                {{ $t('students.dashboard_quick_actions_description') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <button
                v-for="action in quickActions"
                :key="action.url"
                type="button"
                class="flex min-h-14 min-w-0 items-center gap-3 rounded-xl border border-border bg-card px-4 py-3.5 text-left shadow-sm transition-colors hover:bg-muted/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                @click="openAction(action.url)"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                    :class="action.accent"
                >
                    <component :is="action.icon" class="size-4" />
                </span>
                <span class="min-w-0 wrap-break-word text-sm font-medium leading-snug text-foreground">{{ action.label }}</span>
            </button>
        </div>
    </section>
</template>
