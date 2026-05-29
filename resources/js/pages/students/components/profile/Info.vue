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

const mainAddress = computed(() => {
	const address = student?.relationships?.mainAddress?.attributes;

	if (!address) return '';

	const parts = [
		address.address1,
		address.address2,
		address.address3,
		address.address4,
		address.address5,
		address.address6,
	]
		.filter(part => part && part.trim() !== '')
		.map(part => part?.trim());

	// Remove duplicate values
	const uniqueParts = [...new Set(parts)];

	return uniqueParts.join(', ');
});
const otherDetails = computed<ValueAndLabel[]>(() => [
  {
    transKey: 'students.phone',
    value: student?.relationships?.mainContact?.attributes?.phoneNumber ?? '---',
    icon: IconName.phone,
  },
  {
    transKey: 'students.email',
    value: student?.relationships?.user?.attributes?.email ?? '---',
    icon: IconName.mail,
  },
  {
    transKey: 'students.home_address',
    value: mainAddress.value,
    icon: IconName.house,
  },
  {
    transKey: 'students.guardian',
    value: student?.relationships?.nextOfKin?.attributes?.name ?? '---',
    icon: IconName.user,
  },
  {
    transKey: 'students.guardian_contact',
    value: student?.relationships?.nextOfKin?.attributes?.phoneNumber ?? '---',
    icon: IconName.phone,
  },
]);

</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-x-4 lg:grid-cols-3 lg:gap-x-6">
        <LabelValue v-for="(field, idx) in personalDetails" :key="idx" :label="field.transKey ? $t(field.transKey) : $tChoice(field.transChoiceKey ?? '', 1)" :value="field.value" />
    </div>
    <Separator class="my-6" />
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-x-4 lg:grid-cols-3 lg:gap-x-6">
        <InfoCard v-for="(field, idx) in otherDetails" :key="idx" :label="field.transKey ? $t(field.transKey) : $tChoice(field.transChoiceKey ?? '', 1)" :value="field.value" :icon="field.icon" />
    </div>
</template>
