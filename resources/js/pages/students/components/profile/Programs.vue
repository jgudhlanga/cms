<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Empty from '@/components/core/util/Empty.vue';
import { programmeHeading } from '@/composables/students/studentProgrammeDisplay';
import { useStudentProgrammes } from '@/composables/students/useStudentProgrammes';
import ProgrammeSemesterCard from '@/pages/students/components/profile/programs/ProgrammeSemesterCard.vue';
import type { Student } from '@/types/students';
import { computed, onMounted } from 'vue';

interface Props {
    student: Student;
}

const props = defineProps<Props>();

const { programmes, isLoading, loadError, fetchProgrammes } = useStudentProgrammes();

const studentId = computed(() => props.student?.id ?? '');

onMounted(async () => {
    if (studentId.value) {
        await fetchProgrammes(studentId.value);
    }
});
</script>

<template>
    <div class="flex flex-col rounded-2xl bg-muted/30 p-4 font-sans sm:p-6">
        <DataLoadingSpinner v-if="isLoading" />

        <div
            v-else-if="loadError || programmes.length === 0"
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

        <BaseAccordion v-else class="w-full">
            <BaseAccordionItem
                v-for="programme in programmes"
                :key="programme.id"
                :value="String(programme.id)"
                :title="programmeHeading(programme.level, programme.course, programme.courseCode)"
                :description="programme.calendarYear ?? undefined"
            >
                <div class="flex flex-col gap-4">
                    <ProgrammeSemesterCard
                        v-for="semester in programme.semesters"
                        :key="semester.id"
                        :semester="semester"
                    />
                </div>
            </BaseAccordionItem>
        </BaseAccordion>
    </div>
</template>
