<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useStudentEnrolmentStatuses } from '@/composables/students/useStudentEnrolmentStatuses';
import { clearFormErrors } from '@/lib/forms';
import { StudentEnrolmentStatus } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}

const { isLoading, studentEnrolmentStatuses, list } = useStudentEnrolmentStatuses();

onMounted(async () => {
    await list();
});

const props = defineProps<Props>();
const options = computed(() => {
    return studentEnrolmentStatuses.value.map(
        (status: StudentEnrolmentStatus) =>
            <SelectOption>{
                value: Number(status.id),
                label: status?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'studentEnrolmentStatus');
    await list(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('students.enrolment_status', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
