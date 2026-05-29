<script setup lang="ts">
import ApplicationsByIntakePeriod from '@/components/students/applications/ApplicationsByIntakePeriod.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useStudentProfileApplications } from '@/composables/students/useStudentProfileApplications';
import type { Student } from '@/types/students';
import { computed, onMounted } from 'vue';

interface Props {
    student: Student;
}

const props = defineProps<Props>();

const { applications, isLoading, loadError, fetchStudentApplications } = useStudentProfileApplications();

const studentId = computed(() => props.student?.id ?? '');

onMounted(async () => {
    if (studentId.value) {
        await fetchStudentApplications(studentId.value);
    }
});
</script>

<template>
    <div class="flex flex-col rounded-2xl bg-muted/30 p-4 font-sans sm:p-6">
        <DataLoadingSpinner v-if="isLoading" />

        <div
            v-else-if="loadError || applications.length === 0"
            class="rounded-2xl border border-dashed border-border bg-card py-12"
        >
            <Empty
                :message="loadError ? $t('students.applications_load_failure') : $t('students.no_applications')"
            />
            <p
                v-if="!loadError"
                class="mt-2 text-center text-sm text-muted-foreground"
            >
                {{ $t('students.no_applications_description') }}
            </p>
        </div>

        <ApplicationsByIntakePeriod
            v-else
            :applications="applications"
            compact
        />
    </div>
</template>
