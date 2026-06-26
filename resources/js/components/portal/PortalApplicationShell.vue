<script setup lang="ts">
import PortalApplicationIntakeBanner from '@/components/portal/PortalApplicationIntakeBanner.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';

withDefaults(
    defineProps<{
        intakeName?: string | null;
        openIntakeNames?: string | null;
        pageTitle?: string | null;
        stickyFooter?: boolean;
        hideIntakeBanner?: boolean;
        wide?: boolean;
    }>(),
    {
        stickyFooter: false,
        hideIntakeBanner: false,
        wide: false,
    },
);
</script>

<template>
    <StudentPageHeader :page-title="pageTitle" />
    <div class="bg-background pt-16 text-foreground sm:pt-20" :class="stickyFooter ? 'pb-24' : 'pb-8'">
        <div
            class="mx-auto w-full px-4 sm:px-0"
            :class="wide ? 'max-w-2xl md:max-w-6xl' : 'max-w-2xl lg:max-w-3xl'"
        >
            <slot name="header" />
            <PortalApplicationIntakeBanner
                v-if="!hideIntakeBanner"
                :intake-name="intakeName"
                :open-intake-names="openIntakeNames"
            />
            <slot />
        </div>
        <slot name="footer" />
    </div>
</template>
