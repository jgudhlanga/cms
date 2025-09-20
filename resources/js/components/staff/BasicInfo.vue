<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { Staff } from '@/types/staff';
import { ValueAndLabel } from '@/types/utils';
import { computed } from 'vue';

interface Props {
    staff: Staff;
    institutionDepartmentId: string;
}

const { formatDate, navigateTo } = useUtils();

const { staff } = defineProps<Props>();

const details = computed<ValueAndLabel[]>(() => {
    return [
        { transChoiceKey: 'trans.title', value: staff?.attributes?.title ?? '' },
        { transChoiceKey: 'trans.gender', value: staff?.attributes?.gender ?? '' },
        { transChoiceKey: 'trans.marital_status', value: staff?.attributes?.maritalStatus ?? '' },
        { transKey: 'trans.employee_number', value: staff?.attributes?.employeeNumber ?? '' },
        { transKey: 'trans.date_of_birth', value: staff?.attributes?.dateOfBirth ? formatDate(staff?.attributes?.dateOfBirth ?? '') : '---' },
        { transChoiceKey: 'trans.race', value: staff?.attributes?.race ?? '' },
        { transChoiceKey: 'trans.religion', value: staff?.attributes?.religion ?? '' },
        { transChoiceKey: 'trans.denomination', value: staff?.attributes?.denomination ?? '' },
        { transKey: 'trans.weight', value: staff?.attributes?.weight ?? '' },
        { transKey: 'trans.height', value: staff?.attributes?.height ?? '' },
    ];
});
</script>

<template>
    <BaseCard>
        <div class="flex flex-col space-y-3">
            <div class="flex justify-end">
                <BaseTooltip :content="`${$t('trans.edit')} ${$t('trans.personal_details')}`">
                    <IconButton
                        :variant="ColorVariant.primary_outline"
                        :icon="IconName.edit"
                        @click="() => navigateTo(route('staff.edit', { department: institutionDepartmentId, staff: staff.id.toString() }))"
                    />
                </BaseTooltip>
            </div>
            <div :class="`grid w-full grid-cols-1 gap-2 md:grid-cols-4`">
                <LabelValue
                    v-for="(detail, index) in details"
                    :key="index"
                    :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                    :value="detail.value"
                />
            </div>
        </div>
    </BaseCard>
</template>
