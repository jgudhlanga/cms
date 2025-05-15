<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseCheckbox, BaseInput } from '@/components/core/form';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentCourse, DepartmentCourseUpdateParams, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseButton from '../../../../components/core/button/BaseButton.vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    departmentCourse: DepartmentCourse;
    departmentLevels: DepartmentLevel[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { institutionDepartment, departmentCourse, departmentLevels } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', transChoiceKeyIndex: 2, href: route('institution-departments.index') },
    {
        title: institutionDepartment?.attributes.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? '')),
    },
    {
        title: departmentCourse?.attributes.course,
        href: route('department-courses.show', getIdParams(departmentCourse?.id?.toString() ?? '')),
    },
];
const { navigateTo } = useUtils();
const allSelected = ref(false);
const form = useForm<DepartmentCourseUpdateParams>({
    level_ids: [],
    show_on_current_application_period: false,
    course_duration: null,
});
const selectAll = () => {
    if (allSelected.value) {
        form.level_ids = [];
        allSelected.value = false;
    } else {
        form.level_ids = departmentLevels?.map((item: DepartmentLevel) => item['id']) ?? [];
        allSelected.value = true;
    }
};
const updateModel = () => {
    allSelected.value = form.level_ids?.length == departmentLevels?.length;
};

const updateCourse = () => {};
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => updateCourse()" class="flex flex-col">
            <div class="flex space-x-3">
                <BaseCard :title="`${$tChoice('trans.course', 1)} ${$tChoice('trans.level', 2)}`">
                    <template v-if="departmentLevels && departmentLevels.length > 0">
                        <div class="flex flex-col space-y-2">
                            <div class="flex">
                                <BaseCheckbox
                                    input-id="select_all_levels"
                                    :checked="allSelected"
                                    :label="`${$t('trans.select_all')} ${$tChoice('trans.level', 1).toLowerCase()}`"
                                    @click="selectAll()"
                                />
                            </div>
                            <div class="grid grid-cols-1 gap-x-3 md:grid-cols-4">
                                <template v-for="level in departmentLevels" :key="`level_key_${level['id']}`">
                                    <BaseCheckbox
                                        :input-id="`level_id_${level['id']}`"
                                        :value="level['id']"
                                        v-model="form.level_ids"
                                        :label="level['attributes']['level']"
                                        @change="updateModel()"
                                    />
                                </template>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <Empty />
                    </template>
                </BaseCard>
                <BaseCard :title="`${$t('trans.other')} ${$t('trans.details')}`">
                    <div class="flex flex-col space-y-3">
                        <BaseCheckbox
                            input-id="show_on_current_application_period"
                            v-model="form.show_on_current_application_period"
                            :label="`${$t('trans.show_on_current_application_period')}`"
                        />
                        <BaseInput
                            input-id="course_duration"
                            :label="`${$tChoice('trans.course', 1)} ${$t('trans.duration')}`"
                            v-model="form.course_duration"
                            @input="clearFormErrors(form, 'course_duration')"
                            :error="form.errors.course_duration"
                        />
                    </div>
                </BaseCard>
            </div>
            <div class="flex items-center justify-center space-x-3 p-6">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    @click="
                        () =>
                            navigateTo(
                                route('institution-departments.show', getIdParams(institutionDepartment?.attributes?.departmentId.toString() ?? '')),
                            )
                    "
                    >{{ $t('trans.back') }}
                </BaseButton>
                <BaseButton :processing="form.processing" :disabled="form.processing"> {{ $t('trans.save') }} </BaseButton>
            </div>
        </form>
    </PageContainer>
</template>
