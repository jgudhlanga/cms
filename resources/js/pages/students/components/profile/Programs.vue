<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Empty from '@/components/core/util/Empty.vue';
import { programmeHeading } from '@/composables/students/studentProgrammeDisplay';
import { useStudentProgrammes } from '@/composables/students/useStudentProgrammes';
import CoursePathwayProgressCard from '@/pages/students/components/profile/programs/CoursePathwayProgressCard.vue';
import ProgrammeSemesterCard from '@/pages/students/components/profile/programs/ProgrammeSemesterCard.vue';
import type { Student } from '@/types/students';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    student: Student;
}

const props = defineProps<Props>();

const { programmes, pathways, isLoading, loadError, fetchProgrammes } = useStudentProgrammes();

const studentId = computed(() => props.student?.id ?? '');
const hasContent = computed(() => programmes.value.length > 0 || pathways.value.length > 0);
const openProgrammes = ref<string[]>([]);

const defaultOpenProgrammes = (): string[] => {
    const activeProgramme = programmes.value.find((programme) => programme.isActive);

    if (activeProgramme) {
        return [String(activeProgramme.id)];
    }

    const firstProgramme = programmes.value[0];

    return firstProgramme ? [String(firstProgramme.id)] : [];
};

watch(programmes, () => {
    if (openProgrammes.value.length === 0) {
        openProgrammes.value = defaultOpenProgrammes();
    }
});

const onSelectStage = (applicationId: string | number): void => {
    const value = String(applicationId);

    if (!programmes.value.some((programme) => String(programme.id) === value)) {
        return;
    }

    if (!openProgrammes.value.includes(value)) {
        openProgrammes.value = [...openProgrammes.value, value];
    }

    document.getElementById(`programme-${value}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

onMounted(async () => {
    if (studentId.value) {
        await fetchProgrammes(studentId.value);
    }
});
</script>

<template>
    <div class="flex w-full min-w-0 flex-col gap-4 py-4 font-sans">
        <DataLoadingSpinner v-if="isLoading" />

        <div
            v-else-if="loadError || !hasContent"
            class="rounded-2xl border border-dashed border-border bg-card py-12"
        >
            <Empty
                :message="loadError ? $t('students.programmes_load_failure') : $t('students.no_programmes')"
            />
            <p
                v-if="!loadError"
                class="mt-2 text-center text-sm text-muted-foreground"
            >
                {{ $t('students.no_programmes_description') }}
            </p>
        </div>

        <template v-else>
            <CoursePathwayProgressCard
                v-for="pathway in pathways"
                :key="pathway.departmentCourseId"
                :pathway="pathway"
                @select-stage="onSelectStage"
            />

            <BaseAccordion
                v-if="programmes.length > 0"
                class="w-full"
                :model-value="openProgrammes"
                @update:model-value="openProgrammes = Array.isArray($event) ? $event : [$event]"
            >
                <BaseAccordionItem
                    v-for="programme in programmes"
                    :key="programme.id"
                    :value="String(programme.id)"
                    :title="programmeHeading(programme.level, programme.course, programme.courseCode)"
                    :description="programme.calendarYear ?? undefined"
                    :id="`programme-${programme.id}`"
                >
                    <template
                        v-if="programme.isActive"
                        #trigger-extra
                    >
                        <span class="mr-1.5 shrink-0 px-2.5 py-1 text-[0.72rem] uppercase tracking-wide text-primary">
                            {{ $t('students.active_programme') }}
                        </span>
                    </template>

                    <div class="flex flex-col gap-2">
                        <ProgrammeSemesterCard
                            v-for="semester in programme.semesters"
                            :key="semester.id"
                            :semester="semester"
                            :student-id="studentId"
                            :expand-modules-with-marks="programme.isActive === true && semester.isCurrent === true"
                            @status-updated="fetchProgrammes(studentId)"
                        />
                    </div>
                </BaseAccordionItem>
            </BaseAccordion>
        </template>
    </div>
</template>
