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
import { DISABILITY_OPTIONS } from '@/lib/constants';
import { Student } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';
import { computed, onMounted, ref } from 'vue';

interface Props {
    url?: string;
}
const props = withDefaults(defineProps<Props>(), {
    url: route('v1.portal.personal'),
});

const { isNativeCitizen, formatDate } = useUtils();
const { isLoading, getStudentData, onOpenPersonalDetailsModal } = useStudentPortal();
const student = ref<Student | null>(null);

onMounted(async () => {
    student.value = await getStudentData(props.url);
});

const personalDetails = computed<ValueAndLabel[]>(() => {
    const details: ValueAndLabel[] = [
        { transChoiceKey: 'trans.title', value: student.value?.attributes?.title ?? '' },
        { transChoiceKey: 'trans.gender', value: student.value?.attributes?.gender ?? '' },
        { transChoiceKey: 'trans.marital_status', value: student.value?.attributes?.maritalStatus ?? '' },
        { transChoiceKey: 'trans.id_type', value: student.value?.attributes?.idType ?? '' },
    ];
    if (isNativeCitizen(student.value?.attributes?.idType ?? '')) {
        details.push({
            transKey: 'trans.id_number',
            value: student.value?.attributes?.idNumber ?? '',
        });
    } else {
        details.push(
            { transKey: 'trans.passport_number', value: student.value?.attributes?.passportNumber ?? '' },
            { transChoiceKey: 'trans.country', value: student.value?.attributes?.country ?? '' },
        );
    }
    details.push({ transKey: 'trans.date_of_birth', value: formatDate(student.value?.attributes?.dateOfBirth ?? '') });
    details.push({
        transKey: 'trans.disability',
        value: DISABILITY_OPTIONS.find((option) => option.value === student.value?.attributes?.disabilityStatus)?.label ?? '',
    });
    details.push(
        { transChoiceKey: 'trans.race', value: student.value?.attributes?.race ?? '' },
        { transChoiceKey: 'trans.religion', value: student.value?.attributes?.religion ?? '' },
        { transChoiceKey: 'trans.denomination', value: student.value?.attributes?.denomination ?? '' },
        { transKey: 'trans.weight', value: student.value?.attributes?.weight ?? '' },
        { transKey: 'trans.height', value: student.value?.attributes?.height ?? '' },
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
                        :variant="ColorVariant.success_outline"
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
