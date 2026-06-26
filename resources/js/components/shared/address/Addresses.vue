<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import CustomCard from '@/components/core/card/CustomCard.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import Empty from '@/components/core/util/Empty.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import TabsAddNewButton from '@/components/students/tabs/TabsAddNewButton.vue';
import { useAddresses } from '@/composables/shared/useAddresses';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { ADDRESS_FIELDS } from '@/lib/addressFields';
import { hasAbility } from '@/lib/permissions';
import { Address } from '@/types/shared';

const { createAddressColumns, onOpenModal, deleteAddress } = useAddresses();

interface Props {
    addresses: Address[];
    title?: string;
}

defineProps<Props>();
const ability = hasAbility(['create:addresses', 'update:addresses', 'manageOwnStudentContactDetails:students']);
</script>

<template>
    <div class="hidden flex-col md:flex">
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
    </div>
    <div class="flex flex-col space-y-6 md:hidden">
        <TabsAddNewButton :action="() => onOpenModal()" />
        <template v-if="addresses && addresses.length > 0">
            <CustomCard :title="String(index + 1)" v-for="(address, index) in addresses" :key="address.id">
                <template #header-buttons>
                    <IconButton :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="onOpenModal(address)" />
                    <IconButton :icon="IconName.trash" :variant="ColorVariant.danger_outline" @click="deleteAddress(String(address.id))" />
                </template>
                <template #body>
                    <div class="grid grid-cols-1 gap-4 text-sm">
                        <GridLabelValue :label="$t(ADDRESS_FIELDS[0].labelKey)" :value="address?.attributes?.address1 ?? ''" />
                        <GridLabelValue :label="$t(ADDRESS_FIELDS[1].labelKey)" :value="address?.attributes?.address2 ?? ''" />
                        <GridLabelValue :label="$t(ADDRESS_FIELDS[2].labelKey)" :value="address?.attributes?.address3 ?? ''" />
                        <GridLabelValue :label="$t(ADDRESS_FIELDS[3].labelKey)" :value="address?.attributes?.address4 ?? ''" />
                    </div>
                </template>
            </CustomCard>
        </template>
        <Empty v-else />
    </div>
</template>
