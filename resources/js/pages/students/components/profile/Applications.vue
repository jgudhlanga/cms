<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import ApplicationsByIntakePeriod from '@/components/students/applications/ApplicationsByIntakePeriod.vue';
import CurrentIntakeApplicationHub, {
    type ApplicationHubProps,
} from '@/components/students/applications/CurrentIntakeApplicationHub.vue';
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
    offerLetterIntakePeriodIds?: Array<string | number>;
    applicationHub?: ApplicationHubProps | null;
}

const props = defineProps<Props>();

const { applications, isLoading, loadError, fetchStudentApplications } = useStudentProfileApplications();
const { showEditProgramButton } = useStudents();

const studentId = computed(() => props.student?.id ?? '');
const canEditApplications = computed(() => hasAbility('update:student-applications'));
const activeIntakePeriodIds = computed(() => props.activeIntakePeriodIds ?? []);
const offerLetterIntakePeriodIds = computed(() => props.offerLetterIntakePeriodIds ?? []);

const existingApplicationIntakeIds = computed(() =>
    applications.value
        .map((application) => application.attributes?.intakePeriodId)
        .filter((id) => id !== undefined && id !== null),
);

const canStartApplication = computed(() => props.applicationHub?.canStartApplication ?? false);

const showEmptyState = computed(
    () => !loadError.value && applications.value.length === 0 && !canStartApplication.value,
);

const showApplicationsList = computed(
    () => !loadError.value && (applications.value.length > 0 || canStartApplication.value),
);

const hasEditableApplications = computed(() =>
    applications.value.some((application) => showEditProgramButton(application, activeIntakePeriodIds.value)),
);

const showReadOnlyHint = computed(
    () => !loadError.value && applications.value.length > 0 && canEditApplications.value && !hasEditableApplications.value,
);

const urlParams = new URLSearchParams(window.location.search);
const highlightFeePaid = urlParams.get('fee_paid') === '1';

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
            v-else-if="loadError"
            class="rounded-2xl border border-dashed border-border bg-card py-12"
        >
            <Empty :message="$t('students.applications_load_failure')" />
        </div>

        <div
            v-else-if="showEmptyState"
            class="rounded-2xl border border-dashed border-border bg-card py-12"
        >
            <Empty :message="$t('students.no_applications')" />
            <p class="mt-2 text-center text-sm text-muted-foreground">
                {{ $t('students.no_applications_description') }}
            </p>
        </div>

        <template v-else-if="showApplicationsList">
            <CurrentIntakeApplicationHub
                v-if="applicationHub"
                :application-hub="applicationHub"
                :existing-application-intake-ids="existingApplicationIntakeIds"
                :highlight-fee-paid="highlightFeePaid"
            />

            <BaseAlert
                v-if="showReadOnlyHint"
                class="mb-4"
                :type="TypeVariant.info"
                :description="$t('students.past_intake_applications_read_only')"
            />

            <ApplicationsByIntakePeriod
                v-if="applications.length > 0"
                :applications="applications"
                :active-intake-period-ids="activeIntakePeriodIds"
                :offer-letter-intake-period-ids="offerLetterIntakePeriodIds"
                :can-edit="canEditApplications"
                compact
                editable
            />
        </template>
    </div>
</template>
