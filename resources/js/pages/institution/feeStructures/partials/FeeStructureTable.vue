<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import { useFeeStructures } from '@/composables/institution/useFeeStructures';
import { hasAbility } from '@/lib/permissions';
import { FeeStructure } from '@/types/institution';
import { FeeType } from '@/types/settings';

interface Props {
    feeStructures?: FeeStructure[];
    feeType?: FeeType;
}

const props = defineProps<Props>();
const { createFeeStructureColumns, onOpenModal } = useFeeStructures();
</script>

<template>
    <DataTable
        :data="feeStructures ?? []"
        :columns="createFeeStructureColumns(feeType)"
        :show-archived-filter="false"
        :show-column-filters="false"
        :hide-built-in-search="true"
        :on-create="() => onOpenModal(undefined, props.feeType)"
        :disable-create="!hasAbility('create:fee-structures')"
    />
</template>
