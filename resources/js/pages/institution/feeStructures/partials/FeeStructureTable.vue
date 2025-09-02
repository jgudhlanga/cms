<script setup lang="ts">
import SimpleAlert from '@/components/core/alert/SimpleAlert.vue';
import { BaseButton } from '@/components/core/button';
import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { useFeeStructures } from '@/composables/institution/useFeeStructures';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { FeeStructure } from '@/types/institution';
import { FeeType } from '@/types/settings';

interface Props {
    feeStructures?: FeeStructure[];
    feeType?: FeeType;
}

defineProps<Props>();
const { onOpenModal } = useFeeStructures();
const { onDelete, onForceDelete, onRestore } = useDataTables();

const { formatCurrency } = useUtils();
</script>

<template>
    <div v-if="feeStructures && feeStructures?.length > 0">
        <table class="j-table">
            <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th text-left">{{ $tChoice('trans.level', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.mode_of_study', 1) }}</th>
                    <th class="j-th text-left">{{ $t('trans.amount_in_us') }}</th>
                    <th class="j-th text-left">{{ $t('trans.local_amount') }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.action', 2) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <tr class="j-tr" v-for="fee in feeStructures" :key="fee.id">
                    <td class="j-td">{{ fee?.attributes?.level }}</td>
                    <td class="j-td">{{ fee?.attributes?.modeOfStudy }}</td>
                    <td class="j-td">{{ formatCurrency(fee?.attributes?.localFcaAmount) }}</td>
                    <td class="j-td">{{ formatCurrency(fee?.attributes?.amount) }}</td>
                    <td class="j-td space-x-2 text-center">
                        <template v-if="!!fee?.attributes?.deletedAt">
                            <BaseButton
                                @click="
                                    () =>
                                        onRestore(
                                            hasAbility('restore:fee-structures'),
                                            route('fee-structures.restore', { fee_structure: fee.id }),
                                            $tChoice('trans.fee_structure', 1),
                                        )
                                "
                                :title="$t('trans.restore')"
                                :size="ButtonSize.sm"
                                :variant="ColorVariant.success_outline"
                                classes="rounded-full"
                            >
                                <component :is="icons[IconName.restore]" />
                            </BaseButton>
                        </template>
                        <template v-else>
                            <BaseButton
                                @click="onOpenModal(fee, feeType)"
                                :title="$t('trans.edit')"
                                :size="ButtonSize.sm"
                                :variant="ColorVariant.fuchsia_outline"
                                classes="rounded-full"
                            >
                                <component :is="icons[IconName.edit]" />
                            </BaseButton>
                            <BaseButton
                                @click="
                                    () =>
                                        onDelete(
                                            hasAbility('delete:fee-structures'),
                                            route('fee-structures.destroy', { fee_structure: fee.id }),
                                            $tChoice('trans.fee_structure', 1),
                                        )
                                "
                                :title="$tChoice('trans.archive', 1)"
                                :size="ButtonSize.sm"
                                :variant="ColorVariant.danger_outline"
                                classes="rounded-full"
                            >
                                <component :is="icons[IconName.archive]" />
                            </BaseButton>

                            <BaseButton
                                @click="
                                    () =>
                                        onForceDelete(
                                            hasAbility('forceDelete:fee-structures'),
                                            route('fee-structures.force-delete', { fee_structure: fee.id }),
                                            $tChoice('trans.fee_structure', 1),
                                        )
                                "
                                :title="$t('trans.force_delete')"
                                :size="ButtonSize.sm"
                                :variant="ColorVariant.danger"
                                classes="rounded-full"
                            >
                                <component :is="icons[IconName.trash]" />
                            </BaseButton>
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <SimpleAlert
        v-else
        :title="$t('trans.no_data')"
        :description="$t('trans.no_data_found_description', { data: `${$tChoice('trans.fee_structure', 2)}` })"
    />
</template>
