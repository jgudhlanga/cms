<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import { useApplicationRanking } from '@/composables/students/useApplicationRanking';
import { useClassListStore } from '@/store/enrolments/useClassListStore';
import { DepartmentLevel } from '@/types/department-meta-data';
import { EnrolmentApplication } from '@/types/enrolments';
import { storeToRefs } from 'pinia';
import { ref } from 'vue';

interface Props {
    level: DepartmentLevel;
    departmentId: string;
    applications: EnrolmentApplication[];
    classSize: number;
    slotSize: number;
}

const props = defineProps<Props>();

const { classList } = storeToRefs(useClassListStore());

const { levelRequirements, requirementSubjects, getMainSubjectGrade, getOtherSubjectGrades, sortedApplications, faultyApplications } =
    useApplicationRanking(props.level, ref(props.applications), ref(props.classSize));
</script>

<template>
    <div class="my-2">
        <table class="j-table">
            <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th text-left">#</th>
                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.phone', 1) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.amount', 1) }}</th>
                    <th class="j-th text-center">Sitting Count</th>
                    <th class="j-th text-center">First Sitting</th>
                    <th class="j-th text-center" v-for="subject in requirementSubjects" :key="`tr_${subject.id}`">{{ subject?.attributes?.name }}</th>
                    <th class="j-th text-center" v-for="sub in levelRequirements?.attributes.otherSubjectsCount" :key="`${sub}th_other_sub`">
                        {{ `${$t('trans.other')} ${Number(sub)}` }}
                    </th>
                    <th class="j-th text-center">{{ $tChoice('trans.score', 1) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.action', 2) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <tr
                    :class="`${index + 1 <= slotSize ? 'bg-green-100' : 'j-tr'}`"
                    v-for="(application, index) in sortedApplications"
                    :key="application.applicationId"
                >
                    <td class="j-td">{{ index + 1 }}</td>
                    <td class="j-td">{{ application.studentName }}</td>
                    <td class="j-td">{{ application.phoneNumber }}</td>
                    <td class="j-td text-center">{{ application.receiptAmount }}</td>
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
                        v-for="result in getOtherSubjectGrades(application?.academicResults ?? [])"
                        :key="`${result.gradeId}_other_sub`"
                    >
                        {{ result.grade }}
                        <span class="text-[8px]">{{ result.examYear }}</span>
                    </td>
                    <td class="j-td text-center">{{ application.totalScore }}</td>
                    <td class="j-td text-center">
                        <BaseCheckbox
                            v-if="index + 1 <= slotSize"
                            :input-id="`${application?.applicationId}_class_list`"
                            v-model="classList[application.applicationId]"
                        />
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
                            v-for="result in getOtherSubjectGrades(application?.academicResults ?? [])"
                            :key="`${result.gradeId}_other_sub`"
                        >
                            {{ result.grade }}
                            <span class="text-[8px]">({{ result.examYear }})</span>
                        </td>
                        <td class="j-td text-center">{{ application.totalScore ?? '---' }}</td>
                        <td class="j-td">
                            <span class="text-red-800">faulty</span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>
