<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { PageProps } from '@/types';
import { Student } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = usePage<PageProps>();
const { user } = props.props.auth;
const { isNativeCitizen, formatDate } = useUtils();
const { isLoading, getStudentData } = useStudentPortal();
const student = ref<Student | null>(null);

onMounted(async () => {
    student.value = await getStudentData(route('v1.portal.personal'));
});

const personalDetails = computed<ValueAndLabel[]>(() => {
    const details: ValueAndLabel[] = [
        { transChoiceKey: 'trans.title', value: student.value?.title ?? '' },
        { transKey: 'trans.first_name', value: user.attributes?.firstname ?? '' },
        { transKey: 'trans.middle_name', value: user.attributes?.middleName ?? '' },
        { transKey: 'trans.last_name', value: user.attributes?.lastname ?? '' },
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
        <div class="flex space-x-3">
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
