<script setup lang="ts">
import type { Link } from '@/types/ui';
import { Enrolment } from '@/types/enrolments';
import {useUtils} from '@/composables/core/useUtils';
import BaseImage from '@/components/core/image/BaseImage.vue';

interface Props {
    enrolment: Enrolment;
}

const props = defineProps<Props>();

const { formatDate } = useUtils();

const calculateScore = (results: AcademicOLevelResult[]) => results?.reduce((acc: number, result) => {
    return acc + (result.attributes?.gradePosition ? parseFloat(result.attributes.gradePosition as string) : 0);
}, 0);

const mergeSubjects = (results: AcademicOLevelResult[]) => {
    return results?.map((item: AcademicOLevelResult) => item?.attributes?.subject)?.join(', ')
};

const mergeGrades = (results: AcademicOLevelResult[]) => {
    return results?.map((item: AcademicOLevelResult) => item?.attributes?.grade)?.join(', ')
};
</script>

<template>
    <div class="flex flex-col p-4 shadow-lg rounded-lg">
        <div class="flex mb-2 items-center justify-between">
            <div class="flex space-x-2 items-center">
                <BaseImage :is-person="true" class="h-8 w-8" :src="''" />
                <div>{{ enrolment.attributes?.studentName }}</div>
            </div>
            <div class="py-1 px-2 rounded-full bg-gray-300 text-xs">{{ enrolment?.relationships?.departmentWorkflowStep?.attributes?.workflowStep }}</div>
        </div>
        <div class="flex flex-col space-y-[2px] text-gray-500">
            <div class="flex items-center text-xs space-x-3">
                <div :class="`flex w-2/7 font-medium`">{{ $t('trans.tracking_number') }}:</div>
                <div :class="`flex w-5/7 font-extralight`">{{ enrolment.attributes?.applicationTrackingNumber }}</div>
            </div>
                <div class="flex items-center text-xs space-x-3">
                <div :class="`flex w-2/7 font-medium`">{{ $t('trans.application_date') }}:</div>
                <div :class="`flex w-5/7 font-extralight`">{{ formatDate(enrolment.attributes?.createdAt) }}</div>
            </div>
                <div class="flex items-center text-xs space-x-3">
                <div :class="`flex w-2/7 font-medium`">{{ $tChoice('trans.subject', 2) }}:</div>
                <div :class="`flex w-5/7 font-extralight`">{{ mergeSubjects(enrolment?.relationships?.oLevelResults) }}</div>
            </div>
                <div class="flex items-center text-xs space-x-3">
                <div :class="`flex w-2/7 font-medium`">{{ $tChoice('trans.grade', 2) }}:</div>
                <div :class="`flex w-5/7 font-extralight`">{{ mergeGrades(enrolment?.relationships?.oLevelResults) }}</div>
            </div>
            <div class="flex items-center text-xs space-x-3">
                <div :class="`flex w-2/7 font-medium`">{{ $tChoice('trans.score', 1) }}:</div>
                <div :class="`flex w-5/7 font-extralight`"><span class="bg-green-500 px-2 py-1 text-white rounded-full">{{ calculateScore(enrolment?.relationships?.oLevelResults).toString() }}</span></div>
            </div>
        </div>
    </div>
</template>
