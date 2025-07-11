<script setup lang="ts">
import TableLoading from '@/components/core/loader/TableLoading.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useSponsors } from '@/composables/students/useSponsors';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { Sponsor } from '@/types/students';
import { onMounted, ref } from 'vue';

const { createSponsorColumns, onOpenModal, allowed } = useSponsors();
const { isLoading, getStudentData } = useStudentPortal();
const sponsors = ref<Sponsor[]>([]);
onMounted(async () => {
    sponsors.value = await getStudentData(route('v1.portal.sponsors'));
});
</script>

<template>
    <TableLoading v-if="isLoading" />
    <DataTable
        v-else
        :data="sponsors"
        :show-archived-filter="false"
        :columns="createSponsorColumns()"
        :on-create="() => onOpenModal()"
        :disable-create="!allowed"
    />
</template>
