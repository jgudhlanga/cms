<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import DepartmentCourseComboSelect from '@/components/core/form/combobox/DepartmentCourseComboSelect.vue';
import DepartmentLevelComboSelect from '@/components/core/form/combobox/DepartmentLevelComboSelect.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import LevelRequirements from '@/components/students/update/LevelRequirements.vue';
import OLevelRequirements from '@/components/students/update/OLevelRequirements.vue';
import SDPRequirements from '@/components/students/update/SDPRequirements.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Enrolment } from '@/types/enrolments';
import { CreateApplicationParams, UpdateProgramParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { ref, watch } from 'vue';

interface Props {
    form: InertiaForm<CreateApplicationParams | UpdateProgramParams>;
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

const { listLevelRequirements, levelRequirements, isLoading } = useDepartmentLevels(isEditing);

const courseDisabled = ref(false);

watch(department, async () => {
    if (skipFirstDepartmentWatch) {
        skipFirstDepartmentWatch = false;
        return;
    }
    level.value = null;
    courseDisabled.value = true;
    levelRequirements.value = null;

    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
});

watch(level, async () => {
    if (skipFirstLevelWatch) {
        skipFirstLevelWatch = false;
        return;
    }
    course.value = null;
    courseDisabled.value = level.value === null;

    await listLevelRequirements(level.value?.value?.toString() ?? '');
    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
});
watch(course, async () => {
    clearFormErrors(form, 'course');
});
</script>

<template>
    <BaseCard :title="$t('trans.programs')" :description="$t('trans.program_description')">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <ModeOfStudyComboSelect :form="form" v-model="modeOfStudy" :error="form.errors.modeOfStudy" :is-required="true" />
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
                :disabled="courseDisabled"
            />
        </div>
        <div class="my-4 flex w-full flex-col">
            <template v-if="isLoading">
                <SpinnerComponent class="flex w-full items-center justify-center" />
            </template>
            <template v-else>
                <template v-if="levelRequirements">
                    <template v-if="isItTrue(levelRequirements.attributes.isOLevelRequired)">
                        <OLevelRequirements :application="application" />
                    </template>
                    <template v-if="levelRequirements.attributes.requiredLevel">
                        <LevelRequirements :level-requirements="levelRequirements" :application="application" />
                    </template>
                    <template v-if="isItTrue(levelRequirements.attributes.onlyReadWriteRequired)">
                        <SDPRequirements :level-requirements="levelRequirements" :application="application" />
                    </template>
                </template>
            </template>
        </div>
    </BaseCard>
</template>
