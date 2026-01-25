<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { DISABILITY_OPTIONS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import { AuthObject } from '@/types/data-pagination';
import { Enrolment } from '@/types/enrolments';
import { Student } from '@/types/students';
import { Link } from '@/types/ui';
import { User } from '@/types/users';
import { ValueAndLabel } from '@/types/utils';
import { computed } from 'vue';
import OfferLetterAnchor from '@/pages/portal/student/partials/OfferLetterAnchor.vue';
import { useStudents } from '@/composables/students/useStudents';
import { Contact } from '@/types/shared';

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
        <div v-if="student" class="flex flex-col space-y-6">
            <BaseCard>
                <div class="grid w-full grid-cols-1 gap-2 md:grid-cols-4">
                    <LabelValue
                        v-for="(detail, index) in personalDetails"
                        :key="index"
                        :label="`${detail?.transKey ? $t(detail.transKey) : $tChoice(detail.transChoiceKey ?? '', 1)}`"
                        :value="detail.value"
                    />
                </div>
            </BaseCard>
            <BaseCard :title="$tChoice('trans.contact', 2)">
                <div class="grid w-full grid-cols-1 gap-2 md:grid-cols-4">
                    <LabelValue :label="$t('trans.phone_number')" :value="String(contacts[0].attributes?.phoneNumber)" />
                    <LabelValue :label="$t('trans.email_address')" :value="String(contacts[0].attributes?.emailAddress)" />
                </div>
            </BaseCard>
            <div class="flex flex-col space-y-3" v-if="programs && programs.length > 0">
                <div class="flex justify-between">
                    <HeadingSmall title="Student Programs" />
                    <div class="flex space-x-2">
                        <span>{{ $tChoice('trans.student_number', 1) }}:</span>
                        <BaseTag :variant="ColorVariant.fuchsia_outline" :title="student.attributes.studentNumber ?? '---'" />
                    </div>
                </div>
                <table class="j-table">
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th class="j-th text-left">#</th>
                            <th class="j-th text-left">{{ $tChoice('trans.department', 1) }}</th>
                            <th class="j-th text-left">{{ $tChoice('trans.level', 1) }}</th>
                            <th class="j-th text-left">{{ $tChoice('trans.course', 1) }}</th>
                            <th class="j-th text-left">{{ $tChoice('general.mode', 1) }}</th>
                            <th class="j-th text-left">{{ $t('general.intake') }}</th>
                            <th class="j-th text-left">{{ $t('trans.application_date') }}</th>
                            <th class="j-th text-left">{{ $tChoice('trans.status', 1) }}</th>
                            <th class="j-th text-right">{{ $tChoice('trans.action', 1) }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <tr class="j-tr" v-for="(application, index) in programs" :key="String(application.id)">
                            <td class="j-td">{{ index + 1 }}</td>
                            <td class="j-td">{{ application.attributes.department }}</td>
                            <td class="j-td">{{ application.attributes.level }}</td>
                            <td class="j-td">{{ application.attributes.course }}</td>
                            <td class="j-td">{{ application.attributes.modeOfStudy }}</td>
                            <td class="j-td">{{ application.attributes.intakePeriod }}</td>
                            <td class="j-td">{{ formatDate(application.attributes.createdAt, 'LLL') }}</td>
                            <td class="j-td">{{ application.relationships?.departmentWorkflowStep?.attributes?.workflowStep }}</td>
                            <td class="j-td text-right">
                                <div class="flex items-center justify-between">
                                    <OfferLetterAnchor v-if="hasOfferLetter(application)" :student-program-id="String(application.id)" />
                                    <BaseButton
                                        v-if="hasAbility('update:student-programs')"
                                        title="Edit"
                                        :size="ButtonSize.xs"
                                        classes="rounded-full"
                                        :variant="ColorVariant.primary_outline"
                                        @click="() => navigateTo(route('students.program-edit', String(application.id)))"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <BaseAlert v-else :description="$t('messages.no_program_found')" :type="TypeVariant.danger" />
        </div>
        <BaseAlert v-else :description="$t('messages.no_student_found', { user: user.attributes.name ?? '' })" :type="TypeVariant.danger" />
    </PageContainer>
</template>
