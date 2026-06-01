<script setup lang="ts">
import { useStudentProfile, type StudentProfileTabValue } from '@/composables/students/useStudentProfile';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

interface Props {
    activeTab: StudentProfileTabValue;
}

defineProps<Props>();

const { portalSidebarProfileTabs } = useStudentProfile();
const tabs = portalSidebarProfileTabs();
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-50 border-t border-border bg-card px-2 py-2 shadow-lg md:hidden"
        aria-label="Profile navigation"
    >
        <div class="flex items-stretch gap-1 overflow-x-auto pb-[env(safe-area-inset-bottom)]">
            <Link
                v-for="tab in tabs"
                :key="tab.value"
                :href="route(tab.routeName!)"
                class="flex min-w-[4.5rem] shrink-0 flex-col items-center justify-center rounded-md px-2 py-1.5 transition-colors"
                :class="
                    cn(
                        activeTab === tab.value
                            ? 'text-primary'
                            : 'text-muted-foreground hover:text-foreground',
                    )
                "
            >
                <component :is="icons[tab.icon]" class="size-5 shrink-0" />
                <span
                    class="mt-1 max-w-[4.5rem] truncate text-center text-[10px] font-medium leading-tight"
                    :class="activeTab === tab.value ? 'text-primary' : 'text-muted-foreground'"
                >
                    {{ tab.transLabel() }}
                </span>
            </Link>
        </div>
    </nav>
</template>
