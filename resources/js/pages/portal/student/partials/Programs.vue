<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import DepartmentCourseComboSelect from '@/components/core/form/combobox/DepartmentCourseComboSelect.vue';
import DepartmentLevelComboSelect from '@/components/core/form/combobox/DepartmentLevelComboSelect.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { watch, ref } from 'vue';

const { department, level, course } = storeToRefs(useCreateApplicationFormStore());

interface Props {
    form: InertiaForm<CreateApplicationParams>;
}

defineProps<Props>();
const courseDisabled = ref(false);

watch(department, async () => {
    level.value = null;
    courseDisabled.value = true;
});

watch(level, async () => {
    course.value = null;
    courseDisabled.value = false;
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
    </BaseCard>
</template>
