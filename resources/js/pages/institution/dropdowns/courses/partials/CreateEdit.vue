<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useCourses } from '@/composables/institution/useCourses';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Course, CourseParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useUtils } from '@/composables/core/useUtils';
import { BaseCheckbox } from '@/components/core/form';

const course = ref<Course>();
const form = useForm<CourseParams>({
    name: '',
    description: '',
    has_enrolment_requirements: false,
});

const { saveCourse } = useCourses();
const {isItTrue} = useUtils()

const { modals } = useModalStore();

watch(modals!, () => {
    course.value = getModalEdit(APP_MODULE_KEYS.courses);
    form.name = course.value?.attributes?.name ?? '';
    form.description = course.value?.attributes?.description ?? '';
    form.has_enrolment_requirements = isItTrue(course.value?.attributes?.hasEnrolmentRequirements) ?? false;
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.courses"
        :title="`${course ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.course', 1)}`"
        :on-form-action="() => saveCourse(form, course)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
            <BaseCheckbox
                input-id="has_enrolment_requirements"
                v-model="form.has_enrolment_requirements"
                label="Has enrolment requirements"
            />
        </template>
    </BaseModal>
</template>
