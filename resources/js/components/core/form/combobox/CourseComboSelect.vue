<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useCourses } from '@/composables/institution/useCourses';
import { clearFormErrors } from '@/lib/forms';
import { Course } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from '@/lib/debounce';
import { computed, onMounted } from 'vue';

interface Props {
    form?: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}

const { isLoading, courses, listCourses } = useCourses();
onMounted(async () => {
    await listCourses();
});
const props = defineProps<Props>();
const options = computed(() => {
    return courses.value.map(
        (course: Course) =>
            <SelectOption>{
                value: Number(course.id),
                label: course?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    if (props.form) {
        clearFormErrors(props.form, 'course');
    }
    await listCourses(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.course', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
