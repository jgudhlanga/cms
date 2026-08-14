<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import SponsoredImportPanel from '@/pages/maintenance/partials/sponsored-students/SponsoredImportPanel.vue';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

defineProps<{
    calendarYear: number;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transKey: 'trans.maintenance', href: route('maintenance.index') },
    { transKey: 'trans.maintenance_sponsored_students' },
];
</script>

<template>
    <Head :title="$t('trans.maintenance_sponsored_students')" />

    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('maintenance.index')">
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold">{{ $t('trans.maintenance_sponsored_students') }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ $t('trans.maintenance_sponsored_students_description') }}
                </p>
            </div>
        </template>

        <SponsoredImportPanel :calendar-year="calendarYear" />
    </PageContainer>
</template>
