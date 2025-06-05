<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import SpinnerComponent from '@/components/core/util/SpinnerComponent.vue';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { DepartmentCourseParams } from '@/types/department-meta-data';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useCourses } from '@/composables/institution/useCourses';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { Course } from '@/types/institution';
import { SizeVariant } from '@/enums/sizes';

interface Props {
    institutionDepartmentId: string;
}
defineProps<Props>();

const allSelected = ref(false);
const form = useForm<DepartmentCourseParams>({
    course_ids: [],
});

const { isLoading, courses, listCourses } = useCourses();
const { syncDepartmentCourses } = useDepartmentCourses();
const selectAll = () => {
    if (allSelected.value) {
        form.course_ids = [];
        allSelected.value = false;
    } else {
        form.course_ids = courses.value?.map((item: Course) => item['id']);
        allSelected.value = true;
    }
};
const updateModel = () => {
    allSelected.value = form.course_ids?.length == courses.value?.length;
};
const { modals } = useModalStore();

watch(modals!, async () => {
    form.course_ids = getModalEdit(APP_MODULE_KEYS.department_courses);
    await listCourses();
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.department_courses"
        :title="$t('trans.link_courses')"
        :on-form-action="() => syncDepartmentCourses(institutionDepartmentId, form)"
        :form="form"
        :size="SizeVariant.full"
    >
        <template #body>
            <div class="flex flex-col space-y-3">
                <template v-if="isLoading">
                    <SpinnerComponent class="w-full" />
                </template>
                <template v-else>
                    <div class="flex flex-col space-y-2">
                        <div class="flex">
                            <BaseCheckbox
                                input-id="select_all_courses"
                                :checked="allSelected"
                                :label="`${$t('trans.select_all')} ${$tChoice('trans.course', 2).toLowerCase()}`"
                                @click="selectAll()"
                            />
                        </div>
                        <div class="grid grid-cols-4 gap-x-3 md:grid-cols-2">
                            <template v-for="course in courses" :key="`course_key_${course['id']}`">
                                <BaseCheckbox
                                    :input-id="`course_id_${course['id']}`"
                                    :value="course['id']"
                                    v-model="form.course_ids"
                                    :label="course['attributes']['name']"
                                    @change="updateModel()"
                                />
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </BaseModal>
</template>
