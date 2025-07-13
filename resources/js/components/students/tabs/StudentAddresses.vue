<script setup lang="ts">
import TableLoading from '@/components/core/loader/TableLoading.vue';
import Addresses from '@/components/shared/address/Addresses.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { Address } from '@/types/shared';
import { onMounted, ref } from 'vue';

const { isLoading, getStudentData } = useStudentPortal();
const addresses = ref<Address[]>([]);
onMounted(async () => {
    addresses.value = await getStudentData(route('v1.portal.addresses'));
});
</script>

<template>
    <TableLoading v-if="isLoading" />
    <Addresses v-else :addresses="addresses" :title="`${$t('trans.my')} ${$tChoice('trans.address', 2)}`" />
</template>
