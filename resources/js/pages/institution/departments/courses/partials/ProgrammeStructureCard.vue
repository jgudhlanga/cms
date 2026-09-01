<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseCheckbox, BaseInput } from '@/components/core/form';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ButtonSize } from '@/enums/buttons';
import { hasAbility } from '@/lib/permissions';
import { DepartmentCourseLevel } from '@/types/department-meta-data';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

type CalendarType = 'semester' | 'term' | 'abma';

interface ProgrammeSemester {
    id: number;
    position: number;
    name: string;
    kind: string;
}

interface Props {
    levelCourse: DepartmentCourseLevel & {
        durationYears?: number;
        taughtSemesterCount?: number;
        includesIndustrialAttachment?: boolean;
        attachmentSemesterCount?: number;
        programmeSemesters?: ProgrammeSemester[];
    };
}

const props = defineProps<Props>();
const canManage = hasAbility('manage:programme-structures');

const calendarType = computed((): CalendarType => props.levelCourse.calendarType ?? 'semester');

const periodsPerYear = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return 3;
        case 'abma':
            return 4;
        default:
            return 2;
    }
});

const taughtLabel = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return trans('trans.ui_taught_terms');
        case 'abma':
            return trans('trans.ui_taught_abma_periods');
        default:
            return trans('trans.ui_taught_semesters');
    }
});

const attachmentLabel = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return trans('trans.ui_attachment_terms');
        case 'abma':
            return trans('trans.ui_attachment_abma_periods');
        default:
            return trans('trans.ui_attachment_semesters');
    }
});

const taughtHint = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return trans('trans.ui_programme_structure_taught_hint_term');
        case 'abma':
            return trans('trans.ui_programme_structure_taught_hint_abma');
        default:
            return trans('trans.ui_programme_structure_taught_hint_semester');
    }
});

const form = useForm({
    duration_years: props.levelCourse.durationYears ?? 1,
    taught_semester_count: props.levelCourse.taughtSemesterCount ?? periodsPerYear.value,
    includes_industrial_attachment: props.levelCourse.includesIndustrialAttachment ?? false,
    attachment_semester_count: props.levelCourse.attachmentSemesterCount ?? periodsPerYear.value,
});

const programmeSemesters = computed(() => props.levelCourse.programmeSemesters ?? []);

const saveStructure = () => {
    form.post(route('department-level-courses.programme-structure.update', props.levelCourse.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <BaseCard>
        <div class="flex items-start justify-between gap-3">
            <HeadingSmall :title="`${levelCourse.level ?? ''} programme structure`" />
            <BaseButton v-if="canManage" :size="ButtonSize.sm" :processing="form.processing" :disabled="form.processing" @click="saveStructure">
                Save
            </BaseButton>
        </div>
        <div v-if="canManage" class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
            <BaseInput v-model="form.duration_years" type="number" min="1" label="Duration (years)" :vertical-layout="false" />
            <div>
                <BaseInput
                    v-model="form.taught_semester_count"
                    type="number"
                    min="1"
                    :label="taughtLabel"
                    :vertical-layout="false"
                />
                <p class="text-muted-foreground mt-1 text-xs">{{ taughtHint }}</p>
            </div>
            <BaseCheckbox v-model="form.includes_industrial_attachment" input-id="includes_attachment" label="Includes industrial attachment" />
            <BaseInput
                v-if="form.includes_industrial_attachment"
                v-model="form.attachment_semester_count"
                type="number"
                min="1"
                :label="attachmentLabel"
                :vertical-layout="false"
            />
        </div>
        <div v-if="programmeSemesters.length" class="flex flex-wrap gap-1.5">
            <span v-for="semester in programmeSemesters" :key="semester.id" class="bg-muted text-muted-foreground rounded-full px-2.5 py-0.5 text-xs">
                {{ semester.name }}
            </span>
        </div>
    </BaseCard>
</template>
