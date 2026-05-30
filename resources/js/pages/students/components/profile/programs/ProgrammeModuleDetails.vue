<script setup lang="ts">
import {
    displayValue,
    scoreBarColor,
    scoreLabel,
} from '@/composables/students/studentProgrammeDisplay';
import ProgrammeDetailField from '@/pages/students/components/profile/programs/ProgrammeDetailField.vue';
import ProgrammeModuleCourseWork from '@/pages/students/components/profile/programs/ProgrammeModuleCourseWork.vue';
import type { StudentProgrammeModule } from '@/types/students';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface Props {
    module: StudentProgrammeModule;
}

const props = defineProps<Props>();

const hasCourseWork = computed(() => Boolean(props.module.courseWork));

const scoreDisplay = computed((): string => {
    const total = props.module.courseWork?.aggregation.courseWorkTotal60;

    if (total !== null && total !== undefined) {
        return `${total} / 60`;
    }

    if (props.module.score !== null) {
        return `${props.module.score}%`;
    }

    return trans('students.pending');
});

const showScoreBar = computed(() => props.module.score !== null);
</script>

<template>
    <div class="overflow-hidden border-t border-border bg-muted/40">
        <div
            v-if="!hasCourseWork"
            class="grid grid-cols-1 gap-3 px-5 pb-4 pt-3 sm:grid-cols-2 lg:grid-cols-4"
        >
            <ProgrammeDetailField
                label-key="students.lecturer"
                :value="displayValue(module.lecturer)"
            />
            <ProgrammeDetailField
                label-key="students.module_type"
                :value="displayValue(module.type)"
            />
            <ProgrammeDetailField
                label-key="students.assessment"
                :value="displayValue(module.assessment)"
            />
            <ProgrammeDetailField
                label-key="students.score"
                :value="scoreDisplay"
            />
        </div>

        <div
            v-if="!hasCourseWork && showScoreBar"
            class="px-5 pb-4"
        >
            <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="scoreBarColor(module.score!)"
                    :style="{ width: `${module.score}%` }"
                />
            </div>
            <p class="mt-1 text-[0.65rem] text-muted-foreground">
                {{ scoreLabel(module.score!) }}
            </p>
        </div>

        <ProgrammeModuleCourseWork
            v-if="module.courseWork"
            :course-work="module.courseWork"
        />
    </div>
</template>
