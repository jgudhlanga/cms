<script setup lang="ts">
import { ClassListTopNext, ClassListType, OtherApplication } from '@/types/enrolments';

interface Props {
    nextTop: ClassListTopNext[];
    otherApplications?: OtherApplication[];
    type: ClassListType;
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
            return route('enrolments.verify', { student_application: applicationId, type: 'provisional' });
        case 'verified':
            return route('enrolments.confirm', { student_application: applicationId, type: 'verified' });
        default:
            return '';
    }
};
</script>

<template>
    <div class="flex flex-col space-y-3" v-if="otherApplications && otherApplications.length > 0">
        <HeadingSmall :title="$t('trans.ui_other_applications')" :description="$t('trans.ui_applications_in_other_departments')" />
        <div class="flex flex-col space-y-2">
            <div
                v-for="application in otherApplications"
                :key="application.applicationId"
                class="text-foreground flex flex-col rounded-md border border-border bg-card px-3 py-2 text-[8px] uppercase"
            >
                <div class="flex justify-between">
                    <div>{{ application.department }}</div>
                    <div class="bg-primary text-primary-foreground rounded-full px-2 py-0.5">{{ application.level }}</div>
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
        <HeadingSmall :title="$t('trans.ui_next_5')" :description="getDescription(type as ClassListType)" />
        <div class="flex flex-col space-y-2">
            <TextLink
                classes="bg-card px-3 py-2 rounded-md text-xs uppercase text-foreground border border-border hover:bg-muted"
                v-for="application in nextTop"
                :key="application.applicationId"
                :title="application.name"
                :href="getRouteName(type as ClassListType, String(application.applicationId))"
            />
        </div>
    </div>
</template>
