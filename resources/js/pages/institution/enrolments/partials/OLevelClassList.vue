<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
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
    selectedIds?: Set<number>;
    isSelected?: (applicationId: number) => boolean;
}

const props = withDefaults(defineProps<Props>(), {
    selectedIds: () => new Set(),
    isSelected: () => false,
});

const emit = defineEmits<{
    toggle: [applicationId: number, checked: boolean];
    selectGroup: [applications: EnrolmentApplication[], checked: boolean];
}>();

const { level, applications } = props;
const levelRequirements = computed(() => level?.relationships?.requirement);
const requirementSubjects = computed(() => level?.relationships?.requirement?.relationships?.subjects);

const {
    applyPolicyAlgorithmToApplications,
    groupByClassListType,
    getFaultyApplications,
    getMainSubjectGrade,
    getOtherSubjectGrades,
    getClassListTypeClasses,
    getClassListTypeDescription,
    addToClassList,
    getClassListType,
} = useEnrolments();
const { formatDate } = useUtils();
const faultyApplications = computed(() => getFaultyApplications(applications, level));
const groupedApplications = computed(() => groupByClassListType(applications));
const selectableApplications = computed(() => applications.filter((app) => app.inClassList));
const allSelectableSelected = computed(
    () => selectableApplications.value.length > 0 && selectableApplications.value.every((app) => props.isSelected(app.applicationId)),
);

const onSelectAllOnList = (checked: boolean | 'indeterminate') => {
    emit('selectGroup', selectableApplications.value, checked === true);
};
</script>

<template>
    <div class="my-2">
        <table class="j-table">
            <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th w-10 text-center">
                        <Checkbox
                            :model-value="allSelectableSelected"
                            :disabled="selectableApplications.length === 0"
                            :aria-label="$t('trans.ui_select_all_eligible')"
                            @update:model-value="onSelectAllOnList"
                        />
                    </th>
                    <th class="j-th text-left">#</th>
                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.phone', 1) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.date', 1) }}</th>
                    <th class="j-th text-center">{{ $t('trans.ui_sitting_count') }}</th>
                    <th class="j-th text-center">{{ $t('trans.ui_first_sitting') }}</th>
                    <th class="j-th text-center" v-for="subject in requirementSubjects" :key="`tr_${subject.id}`">{{ subject?.attributes?.name }}</th>
                    <th class="j-th text-center" v-for="sub in levelRequirements?.attributes.otherSubjectsCount" :key="`${sub}th_other_sub`">
                        {{ `${$t('trans.other')} ${Number(sub)}` }}
                    </th>
                    <th class="j-th text-center">{{ $tChoice('trans.score', 1) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.status', 1) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <template v-for="(groupApplications, classListType) in groupedApplications" :key="classListType">
                    <tr class="j-tr">
                        <td colspan="100%">
                            <div
                                :class="`flex w-full items-center space-x-2 border-b px-3 py-2 text-sm uppercase ${getClassListTypeClasses(classListType)}`"
                            >
                                <span>{{ classListType }}</span>
                                <span class="text-xs lowercase">{{ `(${getClassListTypeDescription(classListType)})` }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr
                        class="j-tr"
                        v-for="(application, index) in applyPolicyAlgorithmToApplications(groupApplications, level)"
                        :key="application.applicationId"
                    >
                        <td class="j-td text-center">
                            <Checkbox
                                v-if="application.inClassList"
                                :model-value="isSelected(application.applicationId)"
                                :aria-label="application.studentName"
                                @update:model-value="(checked) => emit('toggle', application.applicationId, checked === true)"
                            />
                        </td>
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
                                <span>{{ application.classListType }}</span>
                            </template>
                            <template v-else>
                                <IconButton
                                    :icon="IconName.add"
                                    @click="addToClassList(String(application.applicationId), getClassListType(index, classSize))"
                                />
                            </template>
                        </td>
                    </tr>
                </template>
                <template v-if="faultyApplications.length > 0">
                    <tr class="bg-red-100" v-for="application in faultyApplications" :key="application.applicationId">
                        <td class="j-td"></td>
                        <td class="j-td">{{ application.studentId }}</td>
                        <td class="j-td">{{ application.studentName }}</td>
                        <td class="j-td">{{ application.phoneNumber }}</td>
                        <td class="j-td text-center">{{ application.applicationDate }}</td>
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
                            <span class="text-red-800">{{ $t('trans.ui_error') }}</span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>
