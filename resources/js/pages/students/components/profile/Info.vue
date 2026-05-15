<script setup lang="ts">
import { Separator } from '@/components/ui/separator'
import { useUtils } from '@/composables/core/useUtils';
import { useStudents } from '@/composables/students/useStudents';
import { IconName } from '@/enums/icons';
import { DISABILITY_OPTIONS } from '@/lib/constants';
import { Student } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';
import { computed } from 'vue';

interface Props {
    student: Student;
}

const props = defineProps<Props>();
const { student } = props;

const { isNativeCitizen, formatDate } = useUtils();
const { hasOfferLetter } = useStudents();

const personalDetails = computed<ValueAndLabel[]>(() => {
    const details: ValueAndLabel[] = [
        { transChoiceKey: 'trans.student_number', value: student?.attributes?.studentNumber ?? '' },
        { transChoiceKey: 'trans.title', value: student?.attributes?.title ?? '' },
        { transChoiceKey: 'trans.gender', value: student?.attributes?.gender ?? '' },
        { transChoiceKey: 'trans.marital_status', value: student?.attributes?.maritalStatus ?? '' },
        { transChoiceKey: 'trans.id_type', value: student?.attributes?.idType ?? '' },
    ];
    if (isNativeCitizen(student?.attributes?.idType ?? '')) {
        details.push({
            transKey: 'trans.id_number',
            value: student?.attributes?.idNumber ?? '',
        });
    } else {
        details.push(
            { transKey: 'trans.passport_number', value: student?.attributes?.passportNumber ?? '' },
            { transChoiceKey: 'trans.country', value: student?.attributes?.country ?? '' },
        );
    }
    details.push({ transKey: 'trans.date_of_birth', value: formatDate(student?.attributes?.dateOfBirth ?? '') });
    details.push({ transKey: 'trans.disability', value: DISABILITY_OPTIONS.find(option => option.value === student?.attributes?.disabilityStatus)?.label ?? '' });
    details.push(
        { transChoiceKey: 'trans.race', value: student?.attributes?.race ?? '' },
        { transChoiceKey: 'trans.religion', value: student?.attributes?.religion ?? '' },
        { transChoiceKey: 'trans.denomination', value: student?.attributes?.denomination ?? '' },
        { transKey: 'trans.weight', value: student?.attributes?.weight ?? '' },
        { transKey: 'trans.height', value: student?.attributes?.height ?? '' },
    );

    return details;
});

const data = [
  { label: 'Phone',               value: '+263 77 345 6789',       icon: IconName.phone },
  { label: 'Email',               value: 'rutendo.c@poly.ac.zw',   icon: IconName.mail  },
  { label: 'Home Address',        value: '45 Mbare Ave, Harare',   icon: IconName.house  },
  { label: 'Guardian',            value: 'Tarisai Chikwanda',      icon: IconName.user  },
  { label: 'Guardian Contact',    value: '+263 71 234 5678',       icon: IconName.phone },
  { label: 'Entry Qualification', value: '5 O-Levels incl. Maths', icon: IconName.award  },
]

</script>

<template>
    <div class="grid grid-cols-3 gap-x-6 gap-y-3">
        <LabelValue v-for="(field, idx) in personalDetails" :key="idx" :label="field.transKey ? $t(field.transKey) : $tChoice(field.transChoiceKey ?? '', 1)" :value="field.value" />
    </div>
    <Separator class="my-6" />
    <div class="grid grid-cols-3 gap-x-6 gap-y-3">
        <InfoCard v-for="(field, idx) in data" :key="idx" :label="field.label" :value="field.value" :icon="field.icon" />
    </div>
</template>
