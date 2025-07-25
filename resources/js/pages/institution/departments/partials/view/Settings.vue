<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { GenericButton } from '@/components/core/button';
import IntakePeriodSelect from '@/components/core/form/select/IntakePeriodSelect.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useInstitutionDepartmentMetadata } from '@/composables/institution/useInstitutionDepartmentMetadata';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { hasAbility } from '@/lib/permissions';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { BaseInput } from '@/components/core/form';

interface Props {
    department: InstitutionDepartment;
}

const form = useForm<any>({
    intake_period_id: null,
});
const props = defineProps<Props>();
const { navigateTo } = useUtils();
const courses = ref<DepartmentCourse[]>([]);
const levels = ref<DepartmentLevel[]>([]);

const { loadDepartmentMetadata, isLoading } = useInstitutionDepartmentMetadata();
onMounted(async () => {
    const coursesRes = await loadDepartmentMetadata(route('v1.department-metadata.courses', props.department?.id?.toString()));
    courses.value = coursesRes?.courses;
    const levelsRes = await loadDepartmentMetadata(route('v1.department-metadata.levels', props.department?.id?.toString()));
    levels.value = levelsRes?.levels;
});
</script>

<template>
    <div class="flex flex-col">
        <div class="my-6 flex justify-end" v-if="hasAbility('create:department-metadata')">
            <GenericButton
                :icon="IconName.cogs"
                class="cursor-pointer rounded-full"
                :icon-variant="ColorVariant.primary"
                :variant="ColorVariant.primary_outline"
                @click="() => navigateTo(route('department-application-steps.config', department?.id?.toString() ?? ''))"
                :title="$t('trans.application_step_config')"
            />
        </div>
        <div class="flex flex-col space-y-4">
            <HeadingSmall
                :title="`${$tChoice('trans.intake_period', 2)} ${$t('trans.config')}`"
                :description="$t('trans.intake_config_description')"
            />
            <div class="flex w-1/4 flex-col">
                <IntakePeriodSelect
                    :url="`api/v1/intake-periods?page_size=all`"
                    :label-uppercase="true"
                    :is-multi="false"
                    :is-searchable="true"
                    v-model="form.intake_period_id"
                />
            </div>
            <div class="flex flex-col">
                <template v-if="isLoading">
                    <DataLoadingSpinner />
                </template>
                <template v-else>
                    <table class="hava-table my-4" v-if="courses.length > 0 && levels.length > 0">
                        <thead class="hava-thead">
                            <tr>
                                <th class="hava-th" align="left">{{ $tChoice('trans.course', 1) }}</th>
                                <th class="hava-th" align="left" v-for="level in levels" :key="level.id">
                                    {{ level?.attributes?.level }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="hava-tbody">
                            <tr class="hava-tr" v-for="course in courses" :key="course.id">
                                <td class="hava-td">{{ course?.attributes?.course }}</td>
                                <td class="hava-td" v-for="level in levels" :key="level.id">
                                    <BaseInput :input-id="`class_size_${course?.id}_${level?.id}`" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <BaseAlert v-else :description="$t('trans.courses_and_levels_not_found')" />
                </template>
            </div>
        </div>
    </div>
</template>
