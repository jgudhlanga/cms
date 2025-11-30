<script setup lang="ts">
import { ClassListTopNext, OtherApplication } from '@/types/enrolments';

interface Props {
    nextTop: ClassListTopNext[];
    otherApplications: OtherApplication[];
}

defineProps<Props>();
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
        <HeadingSmall title="Next 10" description="Awaiting verification in this class list" />
        <div class="flex flex-col space-y-2">
            <TextLink
                classes="bg-gray-200 px-3 py-2 rounded-md text-xs uppercase text-accent-foreground border-r-2 border-black"
                v-for="application in nextTop"
                :key="application.applicationId"
                :title="application.name"
                :href="route('enrolments.verify', { student_program: application.applicationId })"
            />
        </div>
    </div>
</template>
