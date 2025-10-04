<script setup lang="ts">
// UI components
import { onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue';

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
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import LevelRequirements from '@/components/students/update/LevelRequirements.vue';
import OLevelRequirements from '@/components/students/update/OLevelRequirements.vue';
import SDPRequirements from '@/components/students/update/SDPRequirements.vue';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { useSubjects } from '@/composables/institution/useSubjects';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert } from '@/lib/alerts';
import { EXAM_SITTINGS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { AcademicOLevelResult } from '@/types/enrolments';
import { Subject } from '@/types/institution';
import { Student } from '@/types/students';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';

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
const { student, oLevelResults } = props;

// Composable
const { listLevelRequirements, levelRequirements, isLoading: levelRequirementsLoading } = useDepartmentLevels();
const { listCourseRequirements, courseRequirements, isLoading: courseRequirementsLoading } = useDepartmentCourses();
const { isItTrue, navigateTo } = useUtils();
const { validateMainSubjects, validateOtherSubjects, updateProgramForm } = useApplicationFormHelper(false);
const { programFormSchema, addProgram } = useStudentPortal();
const { listSubjects, isLoading: subjectsLoading, subjects } = useSubjects();

// Store
const store = useCreateApplicationFormStore();
const {
    modeOfStudy,
    department,
    course,
    level,
    required_level_completed,
    read_write_acknowledged,
    o_level_subject_ids,
    o_level_years,
    o_level_sittings,
} = storeToRefs(store);

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
    await listSubjects();
    populateMainSubjects();
    watchEffect(() => {
        if (!subjectsLoading.value && subjects.value?.length) {
            populateMainSubjects();
        }
    });
});
const populateMainSubjects = () => {
    if (oLevelResults?.length) {
        const subjects = requirements?.value?.relationships?.subjects as Subject[] | undefined;
        if (!subjects?.length) return;
        subjects.forEach((subject) => {
            const subjectId = subject.id?.toString();
            if (!subjectId) return;
            // find the matching O-Level result for this subject
            const result = oLevelResults?.find((r: AcademicOLevelResult) => r.attributes.subjectId.toString() === subjectId);
            //===================== YEARS =============================
            if (!o_level_years) return;
            if (!o_level_years.value) {
                o_level_years.value = {};
            }
            if (result) {
                o_level_years.value[subjectId] = String(result.attributes.examYear);
            } else {
                delete o_level_years.value[subjectId];
            }
            //===================== SITTINGS =============================
            if (!o_level_sittings) return;
            if (!o_level_sittings.value) {
                o_level_sittings.value = {};
            }
            const sittingLabel =
                EXAM_SITTINGS.find((sitting) => sitting.value === result?.attributes?.examSitting)?.label ?? result?.attributes?.examSitting;
            if (result) {
                o_level_sittings.value[subjectId] = { value: result?.attributes?.examSitting, label: String(sittingLabel ?? '') };
            }
            //===================== GRADES =============================
            if (!o_level_subject_ids) return;
            if (!o_level_subject_ids.value) {
                o_level_subject_ids.value = {};
            }
            if (result) {
                o_level_subject_ids.value[subjectId] = String(result?.attributes?.gradeId);
            }
        });
    }
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
            const mainSubjectsCount = Number(String(requirements?.value?.attributes?.mainSubjectsCount ?? '0'));
            const mainErrors = validateMainSubjects(mainSubjectsCount);
            if (mainErrors && mainErrors.length > 0) {
                errorAlert(mainErrors.join('\n'));
                return;
            }
            const otherSubjectCount = Number(String(requirements?.value?.attributes?.otherSubjectsCount ?? '0'));
            const otherErrors = validateOtherSubjects(otherSubjectCount);
            if (otherErrors && otherErrors.length > 0) {
                errorAlert(otherErrors.join('\n'));
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
</script>
<template>
    <StudentPageHeader />
    <form @submit.prevent="() => save()">
        <div class="mt-20 flex w-full flex-col bg-white px-10 md:p-0">
            <div class="flex w-full flex-col md:mx-auto md:w-7/8">
                <BaseAlert
                    :description="sameDepartmentAndLevelErrorMessage"
                    v-if="sameDepartmentAndLevelError"
                    class="mb-5"
                    :type="TypeVariant.danger"
                />
                <BaseAlert
                    :description="message"
                    v-if="message"
                    class="mb-5"
                    :type="TypeVariant.danger"
                />
                <BaseCard :title="$t('trans.programs')" :description="$t('trans.program_description')">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <ModeOfStudyComboSelect :form="form" v-model="modeOfStudy" :error="form.errors.modeOfStudy" :is-required="true" />
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
                    </div>
                    <div class="my-4 flex w-full flex-col">
                        <template v-if="levelRequirementsLoading || courseRequirementsLoading">
                            <SpinnerComponent class="flex w-full items-center justify-center" />
                        </template>
                        <template v-else>
                            <template v-if="Number(String(requirements?.id)) > 0 && !sameDepartmentAndLevelError">
                                <template v-if="isItTrue(requirements?.attributes?.isOLevelRequired)">
                                    <OLevelRequirements />
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

                <CustomSeparator classes="h-1 my-5" />
                <div class="mb-10 flex items-center justify-center space-x-3">
                    <BaseButton
                        @click="navigateTo(route('portal.applications'))"
                        type="button"
                        :variant="ColorVariant.shade"
                        class="w-1/2 md:w-[200px]"
                        :size="ButtonSize.xl"
                    >
                        {{ $t('trans.cancel') }}
                    </BaseButton>
                    <BaseButton class="w-1/2 md:w-[200px]" :size="ButtonSize.xl">
                        {{ $t('trans.submit') }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </form>
</template>
