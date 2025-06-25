<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useSponsors } from '@/composables/portal/useSponsors';
import { AuthObject } from '@/types/data-pagination';
import { Sponsor } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import SponsorForm from '@/components/students/sponsors/SponsorForm.vue';

interface Props {
    sponsors: Sponsor[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const { createSponsorColumns, onOpenModal, allowed } = useSponsors();
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transChoiceKey: 'sponsor' }];
</script>
<template>
    <Head :title="$tChoice('trans.sponsor', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="sponsors"
            :show-archived-filter="false"
            :columns="createSponsorColumns()"
            :on-create="() => onOpenModal()"
            :disable-create="!allowed"
        />
        <SponsorForm/>
    </PageContainer>
</template>
