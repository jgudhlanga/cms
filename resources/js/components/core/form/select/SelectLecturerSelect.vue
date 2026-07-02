<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useStaff } from '@/composables/institution/useStaff';
import { clearFormErrors } from '@/lib/forms';
import { Staff } from '@/types/staff';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';

interface Props {
    institutionDepartmentId: number | string;
    form?: InertiaForm<any>;
    error?: string;
    isRequired?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isRequired: false,
});

const staffIdsModel = defineModel<number[]>({ default: () => [] });

const academicStaffRoleSlugs = 'lecturer,senior-lecturer,lecturer-in-charge,head-of-department';

const staffUrl = computed(
    () =>
        `${route('v1.department-metadata.staff', String(props.institutionDepartmentId))}?page_size=all&only[roles][]=${academicStaffRoleSlugs}`,
);

const { isLoading, loadStaff, staff } = useStaff();

onMounted(async () => {
    await loadStaff(staffUrl.value);
});

watch(staffUrl, async (url) => {
    await loadStaff(url);
});

const options = computed(() =>
    staff.value?.data?.map(
        (member: Staff) =>
            ({
                value: Number(member.id),
                label: member?.relationships?.user?.attributes?.name ?? '',
            }) satisfies SelectOption,
    ) ?? [],
);

const selectedOptions = computed({
    get: () => options.value.filter((option) => staffIdsModel.value.includes(Number(option.value))),
    set: (selected: SelectOption[]) => {
        staffIdsModel.value = selected.map((option) => Number(option.value));
        if (props.form) {
            clearFormErrors(props.form, 'staff_ids');
        }
    },
});
</script>

<template>
    <BaseCombobox
        :label="$tChoice('syllabus.lecturer', 2)"
        v-model="selectedOptions"
        :options="options"
        :is-loading="isLoading"
        :is-required="isRequired"
        :error="error"
        multiple
    />
</template>
