<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useAddresses } from '@/composables/shared/useAddresses';
import { hasAbility } from '@/lib/permissions';
import { Address } from '@/types/shared';

const { createAddressColumns, onOpenModal } = useAddresses();

interface Props {
    addresses: Address[];
    title?: string;
}

defineProps<Props>();
const ability = hasAbility(['create:addresses', 'update:addresses', 'manageOwnStudentContactDetails:students']);
</script>

<template>
    <DataTable
        :data="addresses"
        :columns="createAddressColumns()"
        :on-create="() => onOpenModal()"
        :disable-create="!ability"
        :show-archived-filter="false"
    >
        <template #head-left v-if="title">
            <HeadingSmall :title="title" />
        </template>
    </DataTable>
</template>
