<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { PageProps } from '@/types';
import { DepartmentCourse } from '@/types/department-meta-data';
import { usePage } from '@inertiajs/vue3';

interface Props {
    institutionDepartmentId: string;
    departmentCourses: DepartmentCourse[];
    departmentCoursesIds: Array<string | undefined | null> | null;
}

defineProps<Props>();
const { props: pageProps } = usePage<PageProps>();
const { can } = pageProps?.auth;

const { createDepartmentCourseColumns, openDepartmentCoursesModal } = useDepartmentCourses();
</script>

<template>
    <DataTable :data="departmentCourses" :columns="createDepartmentCourseColumns()" :show-archived-filter="false">
        <template #head-right v-if="can['create:department-metadata']">
            <GenericButton
                :icon="IconName.add"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary"
                @click="() => openDepartmentCoursesModal(departmentCoursesIds)"
                :title="$t('trans.link_courses')"
            />
        </template>
    </DataTable>
</template>
