<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import DepartmentCourseComboSelect from '@/components/core/form/combobox/DepartmentCourseComboSelect.vue';
import DepartmentLevelComboSelect from '@/components/core/form/combobox/DepartmentLevelComboSelect.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import LevelRequirements from '@/components/students/update/LevelRequirements.vue';
import OLevelRequirements from '@/components/students/update/OLevelRequirements.vue';
import SDPRequirements from '@/components/students/update/SDPRequirements.vue';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { ref, watch } from 'vue';
import { clearFormErrors } from '@/lib/forms';

const { department, level, course } = storeToRefs(useCreateApplicationFormStore());
const { listLevelRequirements, levelRequirements, isLoading } = useDepartmentLevels();

interface Props {
    form: InertiaForm<CreateApplicationParams>;
}

const props = defineProps<Props>();
const {form} = props;
const { isItTrue } = useUtils();
const courseDisabled = ref(false);

watch(department, async () => {
    level.value = null;
    courseDisabled.value = true;
    levelRequirements.value = null;
    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
});

watch(level, async () => {
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
    <BaseCard>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <InstitutionDepartmentComboSelect
                :form="form"
                v-model="department"
                :error="form.errors.department"
                :label-uppercase="true"
                :is-required="true"
            />
            <DepartmentLevelComboSelect
                :form="form"
                :institution-department-id="department?.value?.toString() ?? ''"
                v-model="level"
                :error="form.errors.level"
                :label-uppercase="true"
                :is-required="true"
            />
            <DepartmentCourseComboSelect
                :form="form"
                :department-level-id="level?.value?.toString() ?? ''"
                v-model="course"
                :error="form.errors.course"
                :label-uppercase="true"
                :is-required="true"
                :disabled="courseDisabled"
            />
        </div>
        <div class="my-4 flex flex-col">
            <template v-if="isLoading">
                <SpinnerComponent class="flex w-full items-center justify-center" />
            </template>
            <template v-else>
                <template v-if="levelRequirements">
                    <template v-if="isItTrue(levelRequirements.attributes.isOLevelRequired)">
                        <OLevelRequirements :level-requirements="levelRequirements" />
                    </template>
                    <template v-if="levelRequirements.attributes.requiredLevel">
                        <LevelRequirements :level-requirements="levelRequirements" />
                    </template>
                    <template v-if="isItTrue(levelRequirements.attributes.onlyReadWriteRequired)">
                        <SDPRequirements :level-requirements="levelRequirements" />
                    </template>
                </template>
            </template>
        </div>
    </BaseCard>
</template>
