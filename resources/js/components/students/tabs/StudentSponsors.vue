<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useSponsors } from '@/composables/students/useSponsors';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { Sponsor } from '@/types/students';
import { onMounted, ref } from 'vue';
interface Props {
    url?: string;
}
const props = withDefaults(defineProps<Props>(), {
    url: route('v1.portal.sponsors'),
});
const { createSponsorColumns, onOpenModal, allowed } = useSponsors();
const { isLoading, getStudentData } = useStudentPortal();
const sponsors = ref<Sponsor[]>([]);
onMounted(async () => {
    sponsors.value = await getStudentData(props.url);
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <DataTable
        v-else
        :data="sponsors"
        :show-archived-filter="false"
        :columns="createSponsorColumns()"
        :on-create="() => onOpenModal()"
        :disable-create="!allowed"
    />
</template>
