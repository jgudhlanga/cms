<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import { BaseCheckbox, BaseInput } from '@/components/core/form';
import { hasAbility } from '@/lib/permissions';
import { DepartmentCourseLevel } from '@/types/department-meta-data';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const form = useForm({
    duration_years: props.levelCourse.durationYears ?? 1,
    taught_semester_count: props.levelCourse.taughtSemesterCount ?? 2,
    includes_industrial_attachment: props.levelCourse.includesIndustrialAttachment ?? false,
    attachment_semester_count: props.levelCourse.attachmentSemesterCount ?? 2,
});

const programmeSemesters = computed(() => props.levelCourse.programmeSemesters ?? []);

const saveStructure = () => {
    form.post(route('department-level-courses.programme-structure.update', props.levelCourse.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <BaseCard :title="`${levelCourse.level ?? ''} programme structure`">
        <div v-if="canManage" class="grid grid-cols-2 gap-3">
            <BaseInput v-model="form.duration_years" type="number" min="1" label="Duration (years)" />
            <BaseInput v-model="form.taught_semester_count" type="number" min="1" label="Taught semesters" />
            <BaseCheckbox
                v-model="form.includes_industrial_attachment"
                input-id="includes_attachment"
                label="Includes industrial attachment"
            />
            <BaseInput
                v-if="form.includes_industrial_attachment"
                v-model="form.attachment_semester_count"
                type="number"
                min="1"
                label="Attachment semesters"
            />
        </div>
        <div v-if="programmeSemesters.length" class="mt-4 flex flex-wrap gap-2">
            <span
                v-for="semester in programmeSemesters"
                :key="semester.id"
                class="rounded-full bg-slate-100 px-3 py-1 text-sm"
            >
                {{ semester.name }}
            </span>
        </div>
        <div v-if="canManage" class="mt-4 flex justify-end">
            <BaseButton :processing="form.processing" :disabled="form.processing" @click="saveStructure">
                Save programme structure
            </BaseButton>
        </div>
    </BaseCard>
</template>
