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
    <div class="relative" :class="isMobile ? 'px-3 py-5' : 'p-3'">
        <div v-if="isMobile" class="flex items-start gap-3 pr-10 text-left">
            <div
                class="bg-sidebar-primary text-sidebar-primary-foreground flex size-12 shrink-0 aspect-square items-center justify-center rounded-full"
            >
                <component :is="activeTenant.attributes.logo" class="rounded-full border-2 border-white" />
            </div>
            <div class="grid min-w-0 flex-1 justify-items-start gap-2.5 text-sm leading-normal">
                <span class="text-sidebar-foreground w-full min-w-0 truncate font-semibold uppercase">
                    {{ activeTenant.attributes.name }}
                </span>
                <span
                    class="border-primary/40 text-sidebar-foreground/90 inline-flex w-fit max-w-full min-w-0 items-center truncate rounded-full border bg-transparent px-2 py-0.5 font-mono text-xs leading-tight font-semibold tracking-tight tabular-nums"
                    :aria-label="`${$t('general.version_label')} ${appVersion}`"
                >
                    {{ appVersion }}
                </span>
            </div>
        </div>
        <div v-else-if="state === 'collapsed'" class="flex flex-col items-center gap-2 text-center">
            <div
                class="bg-sidebar-primary text-sidebar-primary-foreground flex size-10 aspect-square items-center justify-center rounded-full"
            >
                <component :is="activeTenant.attributes.logo" class="rounded-full border-2 border-white" />
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
                <span class="text-sidebar-foreground w-full min-w-0 truncate font-semibold uppercase">
                    {{ activeTenant.attributes.name }}
                </span>
                <span
                    class="border-primary/40 text-sidebar-foreground/90 inline-flex w-fit max-w-full min-w-0 items-center truncate rounded-full border bg-transparent px-2 py-0.5 font-mono text-xs leading-tight font-semibold tracking-tight tabular-nums"
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
            :aria-label="$t('trans.ui_close_sidebar')"
        />
    </div>
</template>
