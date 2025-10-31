<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { IconName } from '@/enums/icons';
import { DepartmentLevel } from '@/types/department-meta-data';
import { EnrolmentApplication } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    level: DepartmentLevel;
    departmentId: string;
    applications: EnrolmentApplication[];
    classSize: number;
    slotSize: number;
    otherGenderHasWaitingList: boolean;
}

const props = defineProps<Props>();
const { level, applications } = props;
const levelRequirements = computed(() => level?.relationships?.requirement);
const requirementSubjects = computed(() => level?.relationships?.requirement?.relationships?.subjects);

const { applyPolicyAlgorithmToApplications, getFaultyApplications, getMainSubjectGrade, getOtherSubjectGrades } = useEnrolments();
const { formatDate } = useUtils();
const sortedApplications = applyPolicyAlgorithmToApplications(applications, level);
const faultyApplications = getFaultyApplications(applications, level);
const getRowClass = (rowIndex: number) => {
    if (rowIndex + 1 <= props.slotSize) {
        return 'bg-green-100';
    }
    if (rowIndex + 1 > props.slotSize && rowIndex + 1 <= props.slotSize * 2) {
        return 'bg-purple-100';
    }
    return 'j-tr';
};

const getIconClass = (rowIndex: number) => {
    if (rowIndex + 1 <= props.slotSize) {
        return 'text-green-600';
    }
    if (rowIndex + 1 > props.slotSize && rowIndex + 1 <= props.slotSize * 2) {
        return 'text-purple-600';
    }
    return '';
};


</script>

<template>
    <div class="my-2">
        {{ 'Class Size: ' + props.classSize + ', Slot Size: ' + props.slotSize + ', Other gender waitingList: ' + props.otherGenderHasWaitingList }}
        <table class="j-table">
            <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th text-left">#</th>
                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.phone', 1) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.date', 1) }}</th>
                    <th class="j-th text-center">Sitting Count</th>
                    <th class="j-th text-center">First Sitting</th>
                    <th class="j-th text-center" v-for="subject in requirementSubjects" :key="`tr_${subject.id}`">{{ subject?.attributes?.name }}</th>
                    <th class="j-th text-center" v-for="sub in levelRequirements?.attributes.otherSubjectsCount" :key="`${sub}th_other_sub`">
                        {{ `${$t('trans.other')} ${Number(sub)}` }}
                    </th>
                    <th class="j-th text-center">{{ $tChoice('trans.score', 1) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.status', 1) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <tr :class="getRowClass(index)" v-for="(application, index) in sortedApplications" :key="application.applicationId">
                    <td class="j-td">{{ index + 1 }}</td>
                    <td class="j-td">{{ application.studentName }}</td>
                    <td class="j-td">{{ application.phoneNumber }}</td>
                    <td class="j-td text-center">{{ formatDate(application.applicationDate, 'DD MMM YY, HH:mm:ss') }}</td>
                    <td class="j-td text-center">{{ application.examSittingsCount }}</td>
                    <td class="j-td text-center">{{ application.firstExamYear }}</td>
                    <td class="j-td text-center" v-for="subject in requirementSubjects" :key="`td_${subject.id}`">
                        {{ getMainSubjectGrade(application?.academicResults ?? [], String(subject?.id))?.grade }}
                        <span class="text-[8px]">{{
                            getMainSubjectGrade(application?.academicResults ?? [], String(subject?.id))?.examYear ?? '---'
                        }}</span>
                    </td>
                    <td
                        class="j-td text-center"
                        v-for="result in getOtherSubjectGrades(application?.academicResults ?? [], level)"
                        :key="`${result.gradeId}_other_sub`"
                    >
                        {{ result.grade }}
                        <span class="text-[8px]">{{ result.examYear }}</span>
                    </td>
                    <td class="j-td text-center">{{ application.totalScore }}</td>
                    <td class="j-td text-center">
                        <template v-if="application.inClassList">
                            <BaseIcon v-if="index + 1 <= slotSize * 2" :name="IconName.check_done" :class="`h-4 w-full ${getIconClass(index)}`" />
                        </template>
                        <BaseIcon v-else :name="IconName.close" class="h-4 w-full" />
                    </td>
                </tr>
                <template v-if="faultyApplications.length > 0">
                    <tr class="bg-red-100" v-for="application in faultyApplications" :key="application.applicationId">
                        <td class="j-td">{{ application.studentId }}</td>
                        <td class="j-td">{{ application.studentName }}</td>
                        <td class="j-td">{{ application.phoneNumber }}</td>
                        <td class="j-td text-center">{{ application.receiptAmount }}</td>
                        <td class="j-td text-center">{{ application.examSittingsCount }}</td>
                        <td class="j-td text-center">{{ application.firstExamYear }}</td>
                        <td class="j-td text-center" v-for="subject in requirementSubjects" :key="`td_${subject.id}`">
                            {{ getMainSubjectGrade(application?.academicResults ?? [], String(subject?.id))?.grade }}
                            <span class="text-[8px]"
                                >({{ getMainSubjectGrade(application?.academicResults ?? [], String(subject?.id))?.examYear ?? '---' }})</span
                            >
                        </td>
                        <td
                            class="j-td text-center"
                            v-for="result in getOtherSubjectGrades(application?.academicResults ?? [], level)"
                            :key="`${result.gradeId}_other_sub`"
                        >
                            {{ result.grade }}
                            <span class="text-[8px]">({{ result.examYear }})</span>
                        </td>
                        <td class="j-td text-center">{{ application.totalScore ?? '---' }}</td>
                        <td class="j-td text-center">
                            <span class="text-red-800">error</span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>
