<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import NextOfKinTable from '@/components/shared/nextOfKin/NextOfKinTable.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { NextOfKin } from '@/types/next-of-kin';
import { onMounted, ref } from 'vue';

interface Props {
    url?: string;
}
const props = withDefaults(defineProps<Props>(), {
    url: route('v1.portal.next-of-kins'),
});

const { isLoading, getStudentData } = useStudentPortal();
const nextOfKins = ref<NextOfKin[]>([]);
onMounted(async () => {
    nextOfKins.value = await getStudentData(props.url);
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <NextOfKinTable v-else :next-of-kins="nextOfKins" :title="`${$t('trans.my')} ${$t('trans.next_of_kin')}`" />
</template>
