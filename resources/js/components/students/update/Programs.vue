<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Enrolment } from '@/types/enrolments';
import { CreateApplicationParams, ProgramParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, watch } from 'vue';

interface Props {
    form: InertiaForm<CreateApplicationParams | ProgramParams>;
    application?: Enrolment | null;
}

const props = defineProps<Props>();

const { form, application } = props;
const { isItTrue } = useUtils();

const isEditing = Number(String(application?.id)) > 0;

const store = isEditing ? useUpdateProgramFormStore() : useCreateApplicationFormStore();
let skipFirstDepartmentWatch = isEditing;
let skipFirstLevelWatch = isEditing;

const { department, level, course, modeOfStudy } = storeToRefs(store);

const { listLevelRequirements, levelRequirements, isLoading: levelRequirementsLoading } = useDepartmentLevels(isEditing);
const { listCourseRequirements, courseRequirements, isLoading: courseRequirementsLoading } = useDepartmentCourses(isEditing);

watch(department, async () => {
    if (skipFirstDepartmentWatch) {
        skipFirstDepartmentWatch = false;
        return;
    }
    level.value = null;
    course.value = null;
    levelRequirements.value = null;
    courseRequirements.value = null;
    modeOfStudy.value = null;
    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
});

watch(level, async () => {
    if (skipFirstLevelWatch) {
        skipFirstLevelWatch = false;
        return;
    }
    course.value = null;
    levelRequirements.value = null;
    courseRequirements.value = null;
    modeOfStudy.value = null;
    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
});
watch(course, async () => {
    levelRequirements.value = null;
    courseRequirements.value = null;
    if (skipFirstLevelWatch) {
        skipFirstLevelWatch = false;
        return;
    }
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
    clearFormErrors(form, 'course');
});

const requirements = computed(() => {
    return courseRequirements.value && Number(String(courseRequirements.value?.id)) > 0 ? courseRequirements.value : levelRequirements.value;
});
</script>

<template>
    <BaseCard :title="$t('trans.programs')" :description="$t('trans.program_description')">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <InstitutionDepartmentComboSelect :form="form" v-model="department" :error="form.errors.department" :is-required="true" />
            <DepartmentLevelComboSelect
                :form="form"
                :institution-department-id="department?.value?.toString() ?? ''"
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
            />
            <ModeOfStudyComboSelect
                :department-course-id="course?.value?.toString() ?? ''"
                :department-level-id="level?.value?.toString() ?? ''"
                :form="form"
                v-model="modeOfStudy"
                :error="form.errors.modeOfStudy"
                :is-required="true"
            />
        </div>
        <div class="my-4 flex w-full flex-col">
            <template v-if="levelRequirementsLoading || courseRequirementsLoading">
                <SpinnerComponent class="flex w-full items-center justify-center" />
            </template>
            <template v-else>
                <template v-if="Number(String(requirements?.id)) > 0">
                    <template v-if="isItTrue(requirements?.attributes?.isOLevelRequired)">
                        <OLevelRequirements :application="application" :requirements="requirements" />
                    </template>
                    <template v-if="Number(String(requirements?.attributes?.requiredLevelId)) > 0">
                        <LevelRequirements :requirements="requirements" :application="application" />
                    </template>
                    <template v-if="isItTrue(requirements?.attributes?.onlyReadWriteRequired)">
                        <SDPRequirements :application="application" />
                    </template>
                </template>
            </template>
        </div>
    </BaseCard>
</template>
