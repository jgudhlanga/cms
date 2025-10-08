<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Addresses from '@/components/shared/address/Addresses.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { Address } from '@/types/shared';
import { onMounted, ref } from 'vue';

interface Props {
    url?: string;
}
const props = withDefaults(defineProps<Props>(), {
    url: route('v1.portal.addresses'),
});

const { isLoading, getStudentData } = useStudentPortal();
const addresses = ref<Address[]>([]);
onMounted(async () => {
    addresses.value = await getStudentData(props.url);
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <Addresses v-else :addresses="addresses" :title="`${$t('trans.my')} ${$tChoice('trans.address', 2)}`" />
</template>
