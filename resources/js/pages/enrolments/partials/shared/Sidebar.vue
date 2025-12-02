<script setup lang="ts">
import { ClassListTopNext, ClassListType, OtherApplication } from '@/types/enrolments';
import { PropType } from 'vue';

interface Props {
    nextTop: ClassListTopNext[];
    otherApplications: OtherApplication[];
    type: PropType<ClassListType>;
}

defineProps<Props>();

const getDescription = (type: ClassListType) => {
    switch (type) {
        case 'provisional':
            return 'Awaiting verification of applicants details';
        case 'verified':
            return 'Awaiting confirmation to final class list';
        default:
            return '';
    }
};
const getRouteName = (type: ClassListType, applicationId: string) => {
    switch (type) {
        case 'provisional':
            return route('enrolments.verify', { student_program: applicationId, type: 'provisional' });
        case 'verified':
            return route('enrolments.confirm', { student_program: applicationId, type: 'verified' });
        default:
            return '';
    }
};
</script>

<template>
    <div class="flex flex-col space-y-3" v-if="otherApplications && otherApplications.length > 0">
        <HeadingSmall title="Other applications" description="Applications in other departments" />
        <div class="flex flex-col space-y-2">
            <div
                v-for="application in otherApplications"
                :key="application.applicationId"
                class="text-accent-foreground flex flex-col rounded-md border-r-2 border-black bg-gray-200 px-3 py-2 text-[8px] uppercase"
            >
                <div class="flex justify-between">
                    <div>{{ application.department }}</div>
                    <div class="bg-primary text-persian-200 rounded-full px-2 py-0.5">{{ application.level }}</div>
                </div>
                <div class="my-1 flex justify-between">
                    <div>{{ application.course }}</div>
                    <div>{{ application.modeOfStudy }}</div>
                </div>
                <div class="flex">
                    {{ application.inClassList ? 'In Class List' : 'Not in Class List' }}
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-col space-y-3" v-if="nextTop && nextTop.length > 0">
        <HeadingSmall title="Next 10" :description="getDescription(type as ClassListType)" />
        <div class="flex flex-col space-y-2">
            <TextLink
                classes="bg-gray-200 px-3 py-2 rounded-md text-xs uppercase text-accent-foreground border-r-2 border-black"
                v-for="application in nextTop"
                :key="application.applicationId"
                :title="application.name"
                :href="getRouteName(type as ClassListType, String(application.applicationId))"
            />
        </div>
    </div>
</template>
