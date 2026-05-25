<script setup lang="ts">
// UI components
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Composable
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { useStudentPortal } from '@/composables/students/useStudentPortal';

// Store & types
import { AuthObject } from '@/types/data-pagination';
import { ProgramParams } from '@/types/portal';

// Utilities
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import DepartmentCourseComboSelect from '@/components/core/form/combobox/DepartmentCourseComboSelect.vue';
import DepartmentLevelComboSelect from '@/components/core/form/combobox/DepartmentLevelComboSelect.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import CreateEdit from '@/components/students/oLevels/modals/CreateEdit.vue';
import LevelRequirements from '@/components/students/update/LevelRequirements.vue';
import EditOLevelSubjects from '@/components/students/update/partials/EditOLevelSubjects.vue';
import SDPRequirements from '@/components/students/update/SDPRequirements.vue';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { useSubjects } from '@/composables/institution/useSubjects';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert } from '@/lib/alerts';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { AcademicOLevelResult, OLevelSubjectResult } from '@/types/enrolments';
import { Student } from '@/types/students';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import ToastService from '@/services/toast.service';

// Props
interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
    oLevelResults: AcademicOLevelResult[];
    allowedLevels: string[] | number[];
    currentLevels: string[] | number[];
    currentDepartments: string[] | number[];
    currentCourses: string[] | number[];
    message?: string;
}

const props = defineProps<Props>();
const { student } = props;

// Composable
const { listLevelRequirements, levelRequirements, isLoading: levelRequirementsLoading } = useDepartmentLevels();
const { listCourseRequirements, courseRequirements, isLoading: courseRequirementsLoading } = useDepartmentCourses();
const { isItTrue, navigateTo } = useUtils();
const { updateProgramForm } = useApplicationFormHelper(false);
const { programFormSchema, addProgram } = useStudentPortal();
const { listSubjects } = useSubjects();
const { loadStudentOLevelResults, isLoading } = useOLevelResults();
// Store
const store = useCreateApplicationFormStore();
const { modeOfStudy, department, course, level, required_level_completed, read_write_acknowledged } = storeToRefs(store);

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
    read_write_acknowledged: null,
    o_level_subject_ids: null,
    o_level_years: null,
    o_level_sittings: null,
    o_level_other_subject_ids: null,
    o_level_other_grade_ids: null,
    o_level_other_years: null,
    o_level_other_sittings: null,
});

const courseDisabled = ref(false);

const sameDepartmentAndLevelError = ref(false);
const sameDepartmentAndLevelErrorMessage =
    'You already have an application for this department, level and course. Please choose a different department or level or course';
const requirements = ref<CourseRequirement | DepartmentLevelRequirement | null>(null);

watch(department, async () => {
    level.value = null;
    courseDisabled.value = true;
    requirements.value = null;
    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
});

watch(level, async () => {
    course.value = null;
    courseDisabled.value = level.value === null;
    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
});

watch(course, async () => {
    const departmentId = Number(department.value?.value ?? 0);
    const levelId = Number(level.value?.value ?? 0);
    const courseId = Number(course.value?.value ?? 0);
    sameDepartmentAndLevelError.value =
        props.currentDepartments.map(Number).includes(departmentId) &&
        props.currentLevels.map(Number).includes(levelId) &&
        props.currentCourses.map(Number).includes(courseId);
    clearFormErrors(form, 'course');
    if (Number(course.value?.value) > 0) {
        if (course.value?.triggerActionValue) {
            await listCourseRequirements(level.value?.value?.toString() ?? '', course.value?.value?.toString() ?? '');
            requirements.value = courseRequirements.value;
            if (Number(requirements.value?.attributes?.departmentLeveId) !== Number(level.value?.value)) {
                await listLevelRequirements(level.value?.value?.toString() ?? '');
                requirements.value = levelRequirements.value;
            }
        } else {
            if (Number(level.value?.value) > 0) await listLevelRequirements(level.value?.value?.toString() ?? '');
            requirements.value = levelRequirements.value;
        }
    }
});

onMounted(async () => {
    /**ToastService.warning('Sorry, The registration has ended for now. Contact the administration for more info.');
    navigateTo(route('login'));
    return;*/
    await listSubjects();
});
const validateSubjectRequirements = async () => {
    // Load student O-Level results
    const oLevelResults = await loadStudentOLevelResults(String(student.id ?? ''));

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
        if (isItTrue(sameDepartmentAndLevelError.value)) {
            errorAlert(sameDepartmentAndLevelErrorMessage);
            return;
        }
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
        addProgram(student?.id?.toString() ?? '', form);
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
                <BaseAlert
                    :description="sameDepartmentAndLevelErrorMessage"
                    v-if="sameDepartmentAndLevelError"
                    class="mb-5"
                    :type="TypeVariant.danger"
                />
                <BaseAlert :description="message" v-if="message" class="mb-5" :type="TypeVariant.danger" />
                <BaseCard :title="$t('trans.programs')" :description="$t('trans.program_description')">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <InstitutionDepartmentComboSelect :form="form" v-model="department" :error="form.errors.department" :is-required="true" />
                        <DepartmentLevelComboSelect
                            :form="form"
                            :institution-department-id="department?.value?.toString() ?? ''"
                            :allowed-levels="allowedLevels ?? []"
                            v-model="level"
                            :error="form.errors.level"
                            :is-required="true"
                        />
                        <DepartmentCourseComboSelect
                            :form="form"
                            :department-level-id="level?.value?.toString() ?? ''"
                            v-model="course"
                            :error="form.errors.course"
                            :is-required="true"
                            :disabled="courseDisabled"
                        />
                        <ModeOfStudyComboSelect
                            :form="form"
                            v-model="modeOfStudy"
                            :error="form.errors.modeOfStudy"
                            :is-required="true"
                            :department-course-id="course?.value?.toString() ?? ''"
                            :department-level-id="level?.value?.toString() ?? ''"
                        />
                    </div>
                    <div class="my-4 flex w-full flex-col">
                        <template v-if="levelRequirementsLoading || courseRequirementsLoading">
                            <SpinnerComponent class="flex w-full items-center justify-center" />
                        </template>
                        <template v-else>
                            <template v-if="Number(String(requirements?.id)) > 0 && !sameDepartmentAndLevelError">
                                <template v-if="isItTrue(requirements?.attributes?.isOLevelRequired)">
                                    <EditOLevelSubjects
                                        :results="oLevelResults ?? []"
                                        :requirements="requirements"
                                        :student-id="String(student.id)"
                                    />
                                </template>
                                <template v-if="Number(String(requirements?.attributes?.requiredLevelId)) > 0">
                                    <LevelRequirements :requirements="requirements" />
                                </template>
                                <template v-if="isItTrue(requirements?.attributes?.onlyReadWriteRequired)">
                                    <SDPRequirements />
                                </template>
                            </template>
                        </template>
                    </div>
                </BaseCard>
                <div class="my-6 flex flex-col justify-center space-y-3 space-x-3 md:flex-row">
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
        <CreateEdit @saved="onUpdated" />
    </form>
</template>
