<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { Student } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';
import { computed, onMounted, ref } from 'vue';

const { isNativeCitizen, formatDate } = useUtils();
const { isLoading, getStudentData, onOpenPersonalDetailsModal } = useStudentPortal();
const student = ref<Student | null>(null);

onMounted(async () => {
    student.value = await getStudentData(route('v1.portal.personal'));
});

const personalDetails = computed<ValueAndLabel[]>(() => {
    const details: ValueAndLabel[] = [
        { transChoiceKey: 'trans.title', value: student.value?.title ?? '' },
        { transChoiceKey: 'trans.gender', value: student.value?.gender ?? '' },
        { transChoiceKey: 'trans.marital_status', value: student.value?.maritalStatus ?? '' },
        { transChoiceKey: 'trans.id_type', value: student.value?.idType ?? '' },
    ];
    if (isNativeCitizen(student.value?.idType ?? '')) {
        details.push({
            transKey: 'trans.id_number',
            value: student.value?.idNumber ?? '',
        });
    } else {
        details.push(
            { transKey: 'trans.passport_number', value: student.value?.passportNumber ?? '' },
            { transChoiceKey: 'trans.country', value: student.value?.country ?? '' },
        );
    }
    details.push({ transKey: 'trans.date_of_birth', value: formatDate(student.value?.dateOfBirth ?? '') });
    details.push(
        { transChoiceKey: 'trans.race', value: student.value?.race ?? '' },
        { transChoiceKey: 'trans.religion', value: student.value?.religion ?? '' },
        { transChoiceKey: 'trans.denomination', value: student.value?.denomination ?? '' },
        { transKey: 'trans.weight', value: student.value?.weight ?? '' },
        { transKey: 'trans.height', value: student.value?.height ?? '' },
    );

    return details;
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <BaseCard v-else>
        <div class="flex flex-col space-y-3">
            <div class="flex justify-end">
                <BaseTooltip :content="`${$t('trans.edit')} ${$t('trans.personal_details')}`">
                    <IconButton
                        :variant="ColorVariant.primary_outline"
                        :icon="IconName.edit"
                        @click="() => onOpenPersonalDetailsModal(student ?? undefined)"
                    />
                </BaseTooltip>
            </div>
            <div :class="`grid w-full grid-cols-1 gap-2 md:grid-cols-4`">
                <LabelValue
                    v-for="(detail, index) in personalDetails"
                    :key="index"
                    :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                    :value="detail.value"
                />
            </div>
        </div>
    </BaseCard>
</template>
