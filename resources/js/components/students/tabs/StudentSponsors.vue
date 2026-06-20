<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import CustomCard from '@/components/core/card/CustomCard.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import Empty from '@/components/core/util/Empty.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import TabsAddNewButton from '@/components/students/tabs/TabsAddNewButton.vue';
import { useSponsors } from '@/composables/students/useSponsors';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { Sponsor } from '@/types/students';
import { onMounted, ref } from 'vue';
interface Props {
    url?: string;
}
const props = withDefaults(defineProps<Props>(), {
    url: route('v1.portal.sponsors'),
});
const { createSponsorColumns, onOpenModal, allowed, deleteSponsor } = useSponsors();
const { isLoading, getStudentData } = useStudentPortal();
const sponsors = ref<Sponsor[]>([]);
onMounted(async () => {
    sponsors.value = await getStudentData(props.url);
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <template v-else>
        <div class="hidden flex-col md:flex">
            <DataTable
                :data="sponsors"
                :show-archived-filter="false"
                :columns="createSponsorColumns()"
                :on-create="() => onOpenModal()"
                :disable-create="!allowed"
            />
        </div>
        <div class="flex flex-col space-y-6 md:hidden">
            <TabsAddNewButton :action="() => onOpenModal()" />
            <template v-if="sponsors && sponsors.length > 0">
                <CustomCard :title="String(index + 1)" v-for="(sponsor, index) in sponsors" :key="sponsor.id">
                    <template #header-buttons>
                        <IconButton :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="onOpenModal(sponsor)" />
                        <IconButton :icon="IconName.trash" :variant="ColorVariant.danger_outline" @click="deleteSponsor(String(sponsor.id))" />
                    </template>
                    <template #body>
                        <div class="grid grid-cols-1 gap-4 text-sm">
                            <GridLabelValue :label="$tChoice('trans.name', 1)" :value="sponsor?.attributes?.name ?? ''" />
                            <GridLabelValue :label="$tChoice('trans.sponsor_type', 1)" :value="sponsor?.attributes?.sponsorType ?? ''" />
                            <GridLabelValue :label="$t('trans.phone_number')" :value="sponsor?.attributes?.phoneNumber ?? ''" />
                            <GridLabelValue :label="$t('trans.email_address')" :value="sponsor?.attributes?.email ?? ''" />
                            <GridLabelValue :label="$t('trans.address_1')" :value="sponsor?.attributes?.address1 ?? ''" />
                            <GridLabelValue :label="$t('trans.address_2')" :value="sponsor?.attributes?.address2 ?? ''" />
                            <GridLabelValue :label="$t('trans.address_3')" :value="sponsor?.attributes?.address3 ?? ''" />
                            <GridLabelValue :label="$t('trans.address_4')" :value="sponsor?.attributes?.address4 ?? ''" />
                        </div>
                    </template>
                </CustomCard>
            </template>
            <Empty v-else />
        </div>
    </template>
</template>
