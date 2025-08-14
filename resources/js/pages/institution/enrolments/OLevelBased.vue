<script setup lang="ts">
import { DepartmentLevel } from '@/types/department-meta-data';
import { AcademicOLevelResult, Enrolment } from '@/types/enrolments';
import { computed } from 'vue';
import { useUtils } from '@/composables/core/useUtils';


interface Props {
    level: DepartmentLevel;
    enrolments: Enrolment[];
}

const props = defineProps<Props>();

const {  level } = props;
const {formatDate} = useUtils();


const levelRequirements = computed(() => {
    return level?.relationships?.requirement;
});

const requirementSubjects = computed(() => {
    return level?.relationships?.requirement?.relationships?.subjects;
});

const calculateScore = (results: AcademicOLevelResult[]) => results?.reduce((acc: number, result) => {
    return acc + (result.attributes?.gradePosition ? parseFloat(result.attributes.gradePosition as string) : 0);
}, 0);
</script>

<template>
    <table class="j-table">
        <thead class="j-thead">
            <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
            <th class="j-th text-left">{{ $t('trans.tracking_number') }}</th>
            <th class="j-th text-left">{{ $t('trans.application_date') }}</th>
            <th class="j-th text-center"  v-for="subject in requirementSubjects">{{ subject?.attributes?.name }}</th>
            <th class="j-th text-center"  v-for="(sub, index) in levelRequirements?.attributes.otherSubjectsCount">{{ `${$t('trans.other')} ${index + 1}` }}</th>
            <th class="j-th text-center">{{ $tChoice('trans.score', 1) }}</th>
            <th class="j-th text-center">{{ $tChoice('trans.action', 2) }}</th>
        </thead>
        <tbody class="j-tbody">
            <tr class="j-tr" v-for="enrolment in enrolments" :key="enrolment.id">
                <td class="j-td"> {{  enrolment?.attributes?.studentName }} </td>
                <td class="j-td"> {{  enrolment?.attributes?.applicationTrackingNumber }} </td>
                <td class="j-td"> {{  formatDate(enrolment?.attributes?.createdAt, 'LLL') }} </td>
                <td class="j-td text-center" v-for="subject in requirementSubjects">A</td>
                <td class="j-td text-center" v-for="(sub, index) in levelRequirements?.attributes.otherSubjectsCount">A</td>
                <td class="j-td text-center">
                    <span class="bg-gray-500 px-2 py-1 text-white rounded-full">
                        {{ calculateScore(enrolment?.relationships?.oLevelResults!).toString() }}
                    </span>
                </td>
                <td class="j-td text-center">Actions</td>
            </tr>
        </tbody>
    </table>
</template>
