<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import FeeStructureTable from '@/pages/institution/feeStructures/partials/FeeStructureTable.vue';
import CreateEdit from '@/pages/institution/feeStructures/partials/CreateEdit.vue';
import { AuthObject } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import { FeeType } from '@/types/settings';
import type { Link } from '@/types/ui';
import { trans_choice } from 'laravel-vue-i18n';

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

const defaultOpenFeeTypeIds = computed((): string[] => {
    const first = props.feeTypes[0];

    return first?.id ? [String(first.id)] : [];
});

const feeTypeDescription = (fee: FeeType): string => {
    const count = getTypeFeeStructures(fee.attributes.name).length;
    const parts: string[] = [];

    if (fee.attributes.description) {
        parts.push(fee.attributes.description);
    }

    parts.push(trans_choice('trans.fee_structure', count, { count }));

    return parts.join(' · ');
};
</script>

<template>
    <Head :title="$tChoice('trans.fee_structure', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <BaseAccordion
            v-if="feeTypes.length > 0"
            class="w-full"
            :default-value="defaultOpenFeeTypeIds"
        >
            <BaseAccordionItem
                v-for="fee in feeTypes"
                :key="fee.id"
                :value="String(fee.id)"
                :title="fee.attributes.name"
                :description="feeTypeDescription(fee)"
            >
                <FeeStructureTable
                    :fee-structures="getTypeFeeStructures(fee.attributes.name)"
                    :fee-type="fee"
                />
            </BaseAccordionItem>
        </BaseAccordion>
        <BaseAlert
            v-else
            :title="$t('trans.no_data')"
            :description="
                $t('trans.no_data_found_description', {
                    data: `${$tChoice('trans.fee_structure', 2)}`,
                })
            "
        />
        <CreateEdit />
    </PageContainer>
</template>
