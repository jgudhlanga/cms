<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import HostelOccupantImportPanel from '@/pages/hms/hostels/occupants/partials/HostelOccupantImportPanel.vue';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    hostel: {
        id: number;
        name: string;
    };
    canConfirmPayments: boolean;
}>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'hms.title', href: route('hostels.index') },
    { title: props.hostel.name, href: route('hostels.show', props.hostel.id) },
    { transKey: 'hms.import_occupants_title' },
]);
</script>

<template>
    <Head :title="$t('hms.import_occupants_title')" />

    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('hostels.show', hostel.id)"
        :hasBackNavigationLeading="true"
    >
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold">{{ $t('hms.import_occupants_title') }}</h2>
                <p class="text-sm text-muted-foreground">{{ hostel.name }}</p>
            </div>
        </template>

        <HostelOccupantImportPanel
            :hostel-id="Number(hostel.id)"
            :hostel-name="hostel.name"
            :can-confirm-payments="canConfirmPayments"
        />
    </PageContainer>
</template>
