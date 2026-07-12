<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import ApprenticeImportPanel from '@/pages/maintenance/partials/apprentices/ApprenticeImportPanel.vue';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

defineProps<{
    calendarYear: number;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transKey: 'trans.maintenance', href: route('maintenance.index') },
    { transKey: 'trans.maintenance_apprentice_management' },
];
</script>

<template>
    <Head :title="$t('trans.maintenance_apprentice_management')" />

    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('maintenance.index')">
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold">{{ $t('trans.maintenance_apprentice_management') }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ $t('trans.maintenance_apprentice_management_description') }}
                </p>
            </div>
        </template>

        <ApprenticeImportPanel :calendar-year="calendarYear" />
    </PageContainer>
</template>
