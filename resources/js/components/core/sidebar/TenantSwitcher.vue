<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import { useSidebar } from '@/components/ui/sidebar';
import { useSidebarMenu } from '@/composables/core/useSidebarMenu';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { PageProps } from '@/types';
import { TenantInterface } from '@/types/tenants';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { tenants } = useSidebarMenu();

const activeTenant = ref<TenantInterface>(tenants[0]);
const { isMobile, state, toggleSidebar } = useSidebar();

const page = usePage<PageProps>();
const appVersion = computed(() => page.props.appVersion);
</script>
<template>
    <div class="relative p-3">
        <div v-if="isMobile || state === 'collapsed'" class="flex flex-col items-center gap-2 text-center">
            <div
                class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square items-center justify-center rounded-full"
                :class="isMobile ? 'size-12' : 'size-10'"
            >
                <component :is="activeTenant.attributes.logo" class="rounded-full border-2 border-white" />
            </div>
            <div v-if="isMobile" class="grid w-full justify-items-center text-sm leading-tight">
                <span class="w-full min-w-0 truncate font-semibold uppercase text-sidebar-foreground">
                    {{ activeTenant.attributes.name }}
                </span>
                <span
                    class="inline-flex w-fit max-w-full min-w-0 items-center justify-self-center truncate rounded-full border border-primary/40 bg-transparent px-2 py-0.5 font-mono text-xs font-semibold leading-tight tracking-tight text-sidebar-foreground/90 tabular-nums"
                    :aria-label="`${$t('general.version_label')} ${appVersion}`"
                >
                    {{ appVersion }}
                </span>
            </div>
        </div>
        <div v-else class="flex items-center space-x-2">
            <div
                class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square items-center justify-center rounded-full"
                :class="state === 'collapsed' ? 'size-6' : 'size-8'"
            >
                <component :is="activeTenant.attributes.logo" class="rounded-full border-2 border-white" />
            </div>
            <div class="grid min-w-0 flex-1 justify-items-start text-left text-sm leading-tight">
                <span class="w-full min-w-0 truncate font-semibold uppercase text-sidebar-foreground">
                    {{ activeTenant.attributes.name }}
                </span>
                <span
                    class="inline-flex w-fit max-w-full min-w-0 items-center truncate rounded-full border border-primary/40 bg-transparent px-2 py-0.5 font-mono text-xs font-semibold leading-tight tracking-tight text-sidebar-foreground/90 tabular-nums"
                    :aria-label="`${$t('general.version_label')} ${appVersion}`"
                >
                    {{ appVersion }}
                </span>
            </div>
        </div>
        <IconButton
            :icon="IconName.close"
            :variant="ColorVariant.danger_outline"
            @click.stop.prevent="toggleSidebar"
            class="absolute top-3 right-3 flex md:hidden"
            aria-label="Close Sidebar"
        />
    </div>
</template>
