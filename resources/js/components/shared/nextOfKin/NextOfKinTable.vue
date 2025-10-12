<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import CustomCard from '@/components/core/card/CustomCard.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import Empty from '@/components/core/util/Empty.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import TabsAddNewButton from '@/components/students/tabs/TabsAddNewButton.vue';
import { useNextOfKin } from '@/composables/shared/useNextOfKin';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { hasAbility } from '@/lib/permissions';
import { NextOfKin } from '@/types/next-of-kin';

const { createNextOfKinColumns, onOpenModal, deleteNextOfKin } = useNextOfKin();

interface Props {
    nextOfKins: NextOfKin[];
    title?: string;
}

defineProps<Props>();
const ability = hasAbility(['create:next-of-kins', 'update:next-of-kins']);
</script>

<template>
    <div class="hidden flex-col md:flex">
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
    </div>
    <div class="flex flex-col space-y-6 md:hidden">
        <TabsAddNewButton :action="() => onOpenModal()" />
        <template v-if="nextOfKins && nextOfKins.length > 0">
            <CustomCard :title="String(index + 1)" v-for="(nextOfKin, index) in nextOfKins" :key="nextOfKin.id">
                <template #header-buttons>
                    <IconButton :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="onOpenModal(nextOfKin)" />
                    <IconButton :icon="IconName.trash" :variant="ColorVariant.danger_outline" @click="deleteNextOfKin(String(nextOfKin.id))" />
                </template>
                <template #body>
                    <div class="grid grid-cols-1 gap-4 text-sm">
                        <GridLabelValue :label="$tChoice('trans.name', 1)" :value="nextOfKin?.attributes?.name ?? ''" />
                        <GridLabelValue :label="$tChoice('trans.relationship', 1)" :value="nextOfKin?.attributes?.relationship ?? ''" />
                        <GridLabelValue :label="$t('trans.phone_number')" :value="nextOfKin?.attributes?.phoneNumber ?? ''" />
                        <GridLabelValue :label="$t('trans.address_1')" :value="nextOfKin?.attributes?.address1 ?? ''" />
                        <GridLabelValue :label="$t('trans.address_1')" :value="nextOfKin?.attributes?.address2 ?? ''" />
                        <GridLabelValue :label="$t('trans.address_3')" :value="nextOfKin?.attributes?.address3 ?? ''" />
                        <GridLabelValue :label="$t('trans.address_4')" :value="nextOfKin?.attributes?.address4 ?? ''" />
                    </div>
                </template>
            </CustomCard>
        </template>
        <Empty v-else />
    </div>
</template>
