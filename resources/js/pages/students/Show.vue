<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudents } from '@/composables/students/useStudents';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { DISABILITY_OPTIONS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import OfferLetterAnchor from '@/pages/portal/student/partials/OfferLetterAnchor.vue';
import { AuthObject } from '@/types/data-pagination';
import { Enrolment } from '@/types/enrolments';
import { Contact } from '@/types/shared';
import { Student } from '@/types/students';
import { Link } from '@/types/ui';
import { User } from '@/types/users';
import { ValueAndLabel } from '@/types/utils';
import { computed } from 'vue';

interface Props {
    user: User;
    student: Student | null;
    programs: Enrolment[];
    contacts: Contact[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { student, user } = props;
const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'user', href: route('users.index') },
    { transChoiceKey: 'student', transChoiceKeyIndex: 1 },
    { title: user.attributes.name ?? '' },
];

const { isNativeCitizen, formatDate, navigateTo } = useUtils();
const { hasOfferLetter } = useStudents();

const personalDetails = computed<ValueAndLabel[]>(() => {
    const details: ValueAndLabel[] = [
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
    details.push({
        transKey: 'trans.disability',
        value: DISABILITY_OPTIONS.find((option) => option.value === student?.attributes?.disabilityStatus)?.label ?? '',
    });
    details.push(
        { transChoiceKey: 'trans.race', value: student?.attributes?.race ?? '' },
        { transChoiceKey: 'trans.religion', value: student?.attributes?.religion ?? '' },
        { transChoiceKey: 'trans.denomination', value: student?.attributes?.denomination ?? '' },
        { transKey: 'trans.weight', value: student?.attributes?.weight ?? '' },
        { transKey: 'trans.height', value: student?.attributes?.height ?? '' },
    );

    return details;
});
</script>

<template>
    <Head :title="$tChoice('student', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        
    </PageContainer>
</template>
