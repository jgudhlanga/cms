<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import ApplicationsByIntakePeriod from '@/components/students/applications/ApplicationsByIntakePeriod.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Empty from '@/components/core/util/Empty.vue';
import { TypeVariant } from '@/enums/type-variants';
import { useStudentProfileApplications } from '@/composables/students/useStudentProfileApplications';
import { useStudents } from '@/composables/students/useStudents';
import { hasAbility } from '@/lib/permissions';
import type { Student } from '@/types/students';
import { computed, onMounted } from 'vue';

interface Props {
    student: Student;
    activeIntakePeriodIds?: Array<string | number>;
}

const props = defineProps<Props>();

const { applications, isLoading, loadError, fetchStudentApplications } = useStudentProfileApplications();
const { showEditProgramButton } = useStudents();

const studentId = computed(() => props.student?.id ?? '');
const canEditApplications = computed(() => hasAbility('update:student-applications'));
const activeIntakePeriodIds = computed(() => props.activeIntakePeriodIds ?? []);

const hasEditableApplications = computed(() =>
    applications.value.some((application) => showEditProgramButton(application, activeIntakePeriodIds.value)),
);

const showReadOnlyHint = computed(
    () => !loadError.value && applications.value.length > 0 && canEditApplications.value && !hasEditableApplications.value,
);

onMounted(async () => {
    if (studentId.value) {
        await fetchStudentApplications(studentId.value);
    }
});
</script>

<template>
    <div class="flex flex-col font-sans py-4">
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

        <template v-else>
            <BaseAlert
                v-if="showReadOnlyHint"
                class="mb-4"
                :type="TypeVariant.info"
                :description="$t('students.past_intake_applications_read_only')"
            />

            <ApplicationsByIntakePeriod
                :applications="applications"
                :active-intake-period-ids="activeIntakePeriodIds"
                :can-edit="canEditApplications"
                compact
                editable
            />
        </template>
    </div>
</template>
