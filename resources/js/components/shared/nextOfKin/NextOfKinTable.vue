<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useNextOfKin } from '@/composables/shared/useNextOfKin';
import { hasAbility } from '@/lib/permissions';
import { NextOfKin } from '@/types/next-of-kin';

const { createNextOfKinColumns, onOpenModal } = useNextOfKin();

interface Props {
    nextOfKins: NextOfKin[];
    title?: string;
}

defineProps<Props>();
const ability = hasAbility(['create:next-of-kins', 'update:next-of-kins']);
</script>

<template>
    <DataTable
        :data="nextOfKins"
        :columns="createNextOfKinColumns()"
        :on-create="() => onOpenModal()"
        :disable-create="!ability"
        :show-archived-filter="false"
    >
        <template #head-left v-if="title">
            <HeadingSmall :title="title" />
        </template>
    </DataTable>
</template>
