<script setup lang="ts">
import SimpleAlert from '@/components/core/alert/SimpleAlert.vue';
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
        v-if="feeStructures && feeStructures.length > 0"
        :data="feeStructures"
        :columns="createFeeStructureColumns(feeType)"
        :show-archived-filter="false"
        :show-column-filters="false"
        :hide-built-in-search="true"
        :on-create="() => onOpenModal(undefined, props.feeType)"
        :disable-create="!hasAbility('create:fee-structures')"
    />
    <SimpleAlert
        v-else
        :title="$t('trans.no_data')"
        :description="$t('trans.no_data_found_description', { data: `${$tChoice('trans.fee_structure', 2)}` })"
    />
</template>
