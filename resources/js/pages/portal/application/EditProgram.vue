<script setup lang="ts">
// UI components
import { computed, onBeforeUnmount, onMounted } from 'vue';

// Page sections
import Programs from '@/components/students/update/Programs.vue';

// Composable
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { useStudentPortal } from '@/composables/students/useStudentPortal';

// Store & types
import { AuthObject } from '@/types/data-pagination';
import { ProgramParams } from '@/types/portal';

// Utilities
import { BaseButton } from '@/components/core/button';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import CreateEdit from '@/components/students/oLevels/modals/CreateEdit.vue';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import ToastService from '@/services/toast.service';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Enrolment, OLevelSubjectResult } from '@/types/enrolments';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';

// Props
interface Props {
    auth: AuthObject;
    errors: object;
    application: Enrolment;
}

const props = defineProps<Props>();
const { application } = props;

// Composable
const { updateApplication, programFormSchema } = useStudentPortal();
const { listLevelRequirements } = useDepartmentLevels();
const { listCourseRequirements } = useDepartmentCourses();
const { isItTrue, navigateTo } = useUtils();
const { updateProgramForm } = useApplicationFormHelper(true);
const { loadStudentOLevelResults, isLoading } = useOLevelResults();

// Store
const store = useUpdateProgramFormStore();
const { modeOfStudy, department, course, level, required_level_completed, read_write_acknowledged, levelRequirements, courseRequirements } =
    storeToRefs(store);

const requirements = computed(() => {
    if (courseRequirements && courseRequirements.value && Number(String(courseRequirements.value?.id)) > 0) {
        return courseRequirements.value;
    }
    if (levelRequirements && levelRequirements.value && Number(String(levelRequirements.value?.id)) > 0) {
        return levelRequirements.value;
    }
    return null;
});

// Form
const form = useForm<ProgramParams>({
    modeOfStudy: null,
    mode_of_study_id: null,
    department: null,
    department_id: null,
    course: null,
    course_id: null,
    level: null,
    level_id: null,
    required_level_completed: null,
    required_level_upload: null,
    read_write_acknowledged: null,
    o_level_subject_ids: null,
    o_level_years: null,
    o_level_sittings: null,
    o_level_other_subject_ids: null,
    o_level_other_grade_ids: null,
    o_level_other_years: null,
    o_level_other_sittings: null,
});

onMounted(async () => {
    /**ToastService.warning('Sorry, The registration has ended for now. Contact the administration for more info.');
    navigateTo(route('login'));
    return;*/
    modeOfStudy.value = { value: Number(application?.attributes?.modeOfStudyId), label: application?.attributes?.modeOfStudy ?? '' };
    department.value = { value: Number(application?.attributes?.institutionDepartmentId), label: application?.attributes?.department ?? '' };
    level.value = { value: Number(application?.attributes?.departmentLevelId), label: application?.attributes?.level ?? '' };
    course.value = {
        value: Number(application?.attributes?.departmentCourseId),
        label: application?.attributes?.course ?? '',
        triggerActionValue: isItTrue(application?.attributes?.hasEnrolmentRequirements),
    };
    if (Number(course.value?.value) > 0) {
        if (course.value?.triggerActionValue) {
            await listCourseRequirements(level.value?.value?.toString() ?? '', course.value?.value?.toString() ?? '');
            if (Number(requirements.value?.attributes?.departmentLeveId) !== Number(level.value?.value)) {
                await listLevelRequirements(level.value?.value?.toString() ?? '');
            }
        } else {
            if (Number(level.value?.value) > 0) await listLevelRequirements(level.value?.value?.toString() ?? '');
        }
    }

    if (required_level_completed) required_level_completed.value = isItTrue(application?.attributes?.requiredLevelCompleted);
    if (read_write_acknowledged) read_write_acknowledged.value = isItTrue(application?.attributes?.readWriteAcknowledged);
});

const validateSubjectRequirements = async () => {
    // Load student O-Level results
    const oLevelResults = await loadStudentOLevelResults(String(application?.attributes?.studentId));

    // Normalize mainSubjectIds (convert to number array safely)
    const rawMainSubjectIds = requirements?.value?.attributes?.mainSubjectIds ?? [];
    const mainSubjectIds: number[] = Array.isArray(rawMainSubjectIds)
        ? rawMainSubjectIds.filter((v): v is string | number => typeof v === 'string' || typeof v === 'number').map((v) => Number(v))
        : [];

    // Check if any required main subjects are missing in student's results
    if (mainSubjectIds.length > 0) {
        const missingMainSubjects = mainSubjectIds.filter((id) => !oLevelResults?.some((r: OLevelSubjectResult) => Number(r.id) === id));

        if (missingMainSubjects.length > 0) {
            return `Please check that you provided all ${mainSubjectIds.length} main subjects (year, sitting and grade)`;
        }
    }

    // Handle other subject requirements if applicable
    const otherSubjectsCount = Number(String(requirements?.value?.attributes?.otherSubjectsCount ?? '0'));
    if (otherSubjectsCount > 0) {
        const otherSubjectIds =
            oLevelResults?.filter((r: OLevelSubjectResult) => !mainSubjectIds.includes(Number(r.id))).map((r: OLevelSubjectResult) => Number(r.id)) ??
            [];

        if (otherSubjectIds.length < otherSubjectsCount) {
            return `Please provide at least ${otherSubjectsCount} other subject(s)`;
        }
    }

    return null;
};

const save = async () => {
    updateProgramForm(form);
    try {
        programFormSchema().parse(form);
        if (isItTrue(requirements.value?.attributes?.isOLevelRequired)) {
            const subjectErrors = await validateSubjectRequirements();
            if (subjectErrors) {
                errorAlert(subjectErrors);
                return;
            }
        }
        if (isItTrue(Number(String(requirements.value?.attributes?.requiredLevelId)) > 0)) {
            if (!isItTrue(required_level_completed?.value)) {
                errorAlert(trans('trans.acknowledge_level_completed'));
                return;
            }
        }
        if (isItTrue(requirements.value?.attributes?.onlyReadWriteRequired)) {
            if (!isItTrue(read_write_acknowledged?.value)) {
                errorAlert(trans('trans.acknowledge_read_write'));
                return;
            }
        }
        updateApplication(application.id.toString(), form);
    } catch (error: any) {
        if (error?.format) {
            form.setError(error.format());
        } else {
            console.error(error);
        }
    }
};
onBeforeUnmount(() => {
    store.$reset();
    store.$dispose();
});
const onUpdated = () => {
    router.visit(window.location.pathname, {
        replace: true,
        preserveScroll: true,
    });
};
</script>
<template>
    <StudentPageHeader />
    <form @submit.prevent="() => save()">
        <div class="mt-20 flex w-full flex-col bg-white px-10 md:p-0">
            <div class="flex w-full flex-col space-y-6 md:mx-auto md:w-7/8">
                <Programs :form="form" :application="application" />
                <div class="mb-10 flex flex-col justify-center space-y-3 space-x-3 md:flex-row">
                    <BaseButton class="w-full md:w-50" :size="ButtonSize.xl" :processing="isLoading">
                        {{ $t('trans.submit') }}
                    </BaseButton>
                    <BaseButton
                        @click="navigateTo(route('portal.applications'))"
                        type="button"
                        :variant="ColorVariant.shade"
                        class="w-full md:w-50"
                        :size="ButtonSize.xl"
                    >
                        {{ $t('trans.cancel') }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </form>
    <CreateEdit @saved="onUpdated" />
</template>
