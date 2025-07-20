<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import Breadcrumbs from '@/components/core/util/Breadcrumbs.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { BreadcrumbItemInterface } from '@/types/ui';

defineProps<{
    breadcrumbs?: BreadcrumbItemInterface[];
}>();
</script>
<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
    >
        <div class="flex items-center gap-2 px-4">
            <SidebarTrigger class="-ml-4" />
            <Separator orientation="vertical" class="mr-2 h-4" />
            <Breadcrumbs :breadcrumbs="breadcrumbs ?? []" />
        </div>
        <div>
            <TextLink :href="route('logout')" method="post" as="button" classes="text-destructive block text-sm uppercase">
                <IconButton :icon="IconName.logout" :variant="ColorVariant.danger_outline" class="size-3" />
            </TextLink>
        </div>
    </header>
    <div class="flex h-full w-full flex-col pb-10">
        <slot />
    </div>
</template>
