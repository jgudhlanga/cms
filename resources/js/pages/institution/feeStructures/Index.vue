<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useFeeStructures } from '@/composables/institution/useFeeStructures';
import FeeStructureTable from '@/pages/institution/feeStructures/partials/FeeStructureTable.vue';
import CreateEdit from '@/pages/institution/feeStructures/partials/CreateEdit.vue';
import { AuthObject } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import { FeeType } from '@/types/settings';
import type { Link } from '@/types/ui';
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { IconName, icons } from '@/lib/icons';
import { ColorVariant } from '@/enums/colors';

const { onOpenModal } = useFeeStructures();

const props = defineProps<{
    feeStructures: Record<string, FeeStructure[]>;
    feeTypes: FeeType[];
    auth: AuthObject;
    errors: object;
}>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'config', href: route('institution.setup') },
    { transChoiceKey: 'fee_structure' },
];

const getTypeFeeStructures = (name: string): FeeStructure[] => {
    return props.feeStructures?.[name] || [];
};
</script>

<template>
    <Head :title="$tChoice('trans.fee_structure', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="feeTypes.length > 0">
            <div class="flex flex-col space-y-3" v-for="fee in feeTypes" :key="fee.id">
                <div class="flex w-full justify-between">
                    <HeadingSmall :title="fee.attributes.name" />
                    <BaseButton
                        @click="onOpenModal(undefined, fee)"
                        :title="$t('trans.create')"
                        :size="ButtonSize.sm"
                        :variant="ColorVariant.primary_outline"
                        classes="rounded-full"
                    >
                        <component :is="icons[IconName.add]" />
                    </BaseButton>
                </div>
                <div class="mb-5">
                  <FeeStructureTable :fee-structures="getTypeFeeStructures(fee.attributes.name)" :fee-type="fee" />
                </div>
            </div>
        </template>
        <BaseAlert
            v-else
            :title="$t('trans.no_data')"
            :description="
                $t('trans.no_data_found_description', {
                    data: `${$tChoice('trans.fee_structure', 2)}`,
                })
            "
        />
      <CreateEdit/>
    </PageContainer>
</template>
