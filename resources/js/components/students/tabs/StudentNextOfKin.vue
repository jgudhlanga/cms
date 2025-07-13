<script setup lang="ts">
import TableLoading from '@/components/core/loader/TableLoading.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { onMounted, ref } from 'vue';
import { NextOfKin } from '@/types/next-of-kin';
import NextOfKinTable from '@/components/shared/nextOfKin/NextOfKinTable.vue';

const { isLoading, getStudentData } = useStudentPortal();
const nextOfKins = ref<NextOfKin[]>([]);
onMounted(async () => {
    nextOfKins.value = await getStudentData(route('v1.portal.next-of-kins'));
});
</script>

<template>
    <TableLoading v-if="isLoading" />
    <NextOfKinTable v-else :next-of-kins="nextOfKins" :title="`${$t('trans.my')} ${$t('trans.next_of_kin')}`" />
</template>
