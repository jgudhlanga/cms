<script setup lang="ts">
import EclipseButton from '@/components/core/button/EclipseButton.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import ApplicationFeePaymentStatus from '@/pages/institution/enrolments/ApplicationFeePaymentStatus.vue';
import TuitionFeePaymentStatus from '@/pages/institution/enrolments/TuitionFeePaymentStatus.vue';
import { DepartmentApplicationStep, DepartmentLevel } from '@/types/department-meta-data';
import { AcademicOLevelResult, Enrolment } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    level: DepartmentLevel;
    enrolments: Enrolment[];
    steps: DepartmentApplicationStep[];
    step: DepartmentApplicationStep;
}

const props = defineProps<Props>();

const { level } = props;
const { formatDate } = useUtils();
const { approveApplication, applicationFeePaymentRequired, tuitionFeePaymentRequired } = useStudentApplications();

const levelRequirements = computed(() => {
    return level?.relationships?.requirement;
});

const requirementSubjects = computed(() => {
    return level?.relationships?.requirement?.relationships?.subjects;
});

const calculateScore = (results: AcademicOLevelResult[]) =>
    results?.reduce((acc: number, result) => {
        return acc + (result.attributes?.gradePosition ? parseFloat(result.attributes.gradePosition as string) : 0);
    }, 0);

const getMainSubjectGrade = (results: AcademicOLevelResult[], subjectId: string | number): string => {
    const match = results.find((r) => String(r.attributes.subjectId) === String(subjectId));
    return match?.attributes.grade || 'N/A';
};

const getOtherSubjectGrades = (results: AcademicOLevelResult[]): Record<number, string> => {
    const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
    const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
    const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;
    const requiredIds = requiredSubjects.map((s: any) => String(s.id));
    const otherSubjects = results.filter((r) => !requiredIds.includes(String(r.attributes.subjectId)));

    return otherSubjects.slice(0, otherSubjectsCount).reduce(
        (acc, r, index) => {
            acc[index + 1] = r.attributes.grade || 'N/A';
            return acc;
        },
        {} as Record<number, string>,
    );
};

const buttonOptions = (enrolment: Enrolment) => {
    const choices = [];
    for (const option of props.steps) {
        choices.push({
            key: option.id,
            id: option.id,
            title: option?.attributes?.workflowStep,
            action: () => approveApplication(enrolment, option.id?.toString() ?? '', props.step),
        });
    }
    return choices;
};
</script>

<template>
    <table class="j-table">
        <thead class="j-thead">
            <tr class="j-th">
                <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                <th class="j-th text-left">{{ $t('trans.tracking_number') }}</th>
                <th class="j-th text-left" v-if="applicationFeePaymentRequired(step)">{{ $t('trans.application_fee') }}</th>
                <th class="j-th text-left" v-if="tuitionFeePaymentRequired(step)">{{ $t('trans.tuition_fee') }}</th>
                <th class="j-th text-left">{{ $t('trans.application_date') }}</th>
                <th class="j-th text-center" v-for="subject in requirementSubjects" :key="subject.id">{{ subject?.attributes?.name }}</th>
                <th class="j-th text-center" v-for="sub in levelRequirements?.attributes.otherSubjectsCount" :key="`${sub}_other_sub`">
                    {{ `${$t('trans.other')} ${Number(sub)}` }}
                </th>
                <th class="j-th text-center">{{ $tChoice('trans.score', 1) }}</th>
                <th class="j-th text-center">{{ $tChoice('trans.action', 2) }}</th>
            </tr>
        </thead>
        <tbody class="j-tbody">
            <tr class="j-tr" v-for="enrolment in enrolments" :key="enrolment.id">
                <td class="j-td">
                    <TextLink :href="''" :title="enrolment?.attributes?.studentName" />
                </td>
                <td class="j-td">{{ enrolment?.attributes?.applicationTrackingNumber }}</td>
                <td class="j-td text-center" v-if="applicationFeePaymentRequired(step)">
                    <ApplicationFeePaymentStatus :enrolment="enrolment" />
                </td>
                <td class="j-td text-center" v-if="tuitionFeePaymentRequired(step)">
                    <TuitionFeePaymentStatus :enrolment="enrolment" />
                </td>
                <td class="j-td">{{ formatDate(enrolment?.attributes?.createdAt, 'LLL') }}</td>
                <td class="j-td text-center" v-for="subject in requirementSubjects" :key="`${subject.id}_td`">
                    {{ getMainSubjectGrade(enrolment?.relationships?.oLevelResults!, subject?.id?.toString() ?? '') }}
                </td>
                <td
                    class="j-td text-center"
                    v-for="grade in getOtherSubjectGrades(enrolment?.relationships?.oLevelResults!)"
                    :key="`${grade}_other_td`"
                >
                    {{ grade }}
                </td>
                <td class="j-td text-center">
                    <span class="rounded-full bg-gray-500 px-2 py-1 text-white">
                        {{ calculateScore(enrolment?.relationships?.oLevelResults!).toString() }}
                    </span>
                </td>
                <td class="j-td text-center">
                    <EclipseButton :options="buttonOptions(enrolment)" :show-only-icon="true" />
                </td>
            </tr>
        </tbody>
    </table>
</template>
