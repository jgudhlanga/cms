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

const logoShellClass =
    'flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-white aspect-[671/1080]';
const logoImageClass = 'size-full object-contain';
</script>
<template>
    <div
        class="relative"
        :class="isMobile ? 'px-3 py-4' : state === 'collapsed' ? 'px-0 py-1' : 'p-2'"
    >
        <div v-if="isMobile" class="flex items-start gap-3 pr-10 text-left">
            <div :class="[logoShellClass, 'h-20']">
                <component :is="activeTenant.attributes.logo" :classes="logoImageClass" />
            </div>
            <div class="grid min-w-0 flex-1 justify-items-start gap-1 text-sm leading-normal">
                <span class="text-white w-full min-w-0 truncate font-semibold uppercase">
                    {{ activeTenant.attributes.name }}
                </span>
                <span
                    class="text-sidebar-foreground/70 inline-flex w-fit max-w-full min-w-0 items-center truncate rounded-full border border-current bg-transparent px-2 py-0.5 font-mono text-xs leading-tight font-medium tracking-tight tabular-nums"
                    :aria-label="`${$t('general.version_label')} ${appVersion}`"
                >
                    {{ `${$t('general.version_label')}${appVersion}` }}
                </span>
            </div>
        </div>
        <div v-else-if="state === 'collapsed'" class="flex flex-col items-center gap-2 text-center">
            <div :class="[logoShellClass, 'h-14']">
                <component :is="activeTenant.attributes.logo" :classes="logoImageClass" />
            </div>
        </div>
        <div v-else class="flex items-center space-x-2">
            <div :class="[logoShellClass, 'h-16']">
                <component :is="activeTenant.attributes.logo" :classes="logoImageClass" />
            </div>
            <div class="grid min-w-0 flex-1 justify-items-start text-left text-sm leading-tight">
                <span class="text-white w-full min-w-0 truncate font-semibold uppercase">
                    {{ activeTenant.attributes.name }}
                </span>
                <span
                    class="text-white inline-flex w-fit max-w-full min-w-0 items-center truncate bg-transparent py-0.5 font-mono text-xs leading-tight font-medium tracking-tight tabular-nums"
                    :aria-label="`${$t('general.version_label')} ${appVersion}`"
                >
                    {{ `${$t('general.version_label')}${appVersion}` }}
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
