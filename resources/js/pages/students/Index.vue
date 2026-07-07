<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useStudents } from '@/composables/students/useStudents';
import { AuthObject, DataListProps } from '@/types/data-pagination';
import { Link } from '@/types/ui';
import { onMounted, ref, watch } from 'vue';
import StudentFilters from '@/components/students/filters/StudentFilters.vue';
import StudentStats from '@/components/students/StudentStats.vue';
import StudentExportModal from '@/components/students/export/StudentExportModal.vue';
import { storeToRefs } from 'pinia';
import { useStudentsStore } from '@/store/students/useStudentsStore';
import { Student, StudentFiltersState } from '@/types/students';

const { createStudentColumns, fetchStudents, isLoading, isStatsLoading } = useStudents();

interface Props {
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();
const breadcrumbs: Array<Link> = [{ transKey: 'dashboard', href: route('dashboard') }, { transChoiceKey: 'student' }];

const { studentRefreshKey } = storeToRefs(useStudentsStore());
const students  = ref<DataListProps<Student>>({data: [], links: {
    first: null,
    last: null,
    prev: null,
    next: null
}, meta: {
    total: 0, per_page: 0, current_page: 0, last_page: 0, from: 0, to: 0,
    path: null,
    links: null
}});  
const filters = ref<StudentFiltersState>({});

const loadStudents = async (f: StudentFiltersState = {}) => {
    const res = await fetchStudents(f);
    if (res) students.value = res;
};

const loadStudentsFromUrl = async (url: string) => {
    const res = await fetchStudents(filters.value, url);
    if (res) students.value = res;
};

const onStudentFiltersChange = async (f: StudentFiltersState) => {
    filters.value = f;
    await loadStudents(f);
};

const onStatFilter = async (partial: Partial<StudentFiltersState>) => {
    const nextFilters = { ...filters.value, ...partial };
    await onStudentFiltersChange(nextFilters);
};

onMounted(() => loadStudents());
watch(studentRefreshKey, () => loadStudents(filters.value));

</script>

<template>
    <Head :title="$tChoice('student', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="bg-card relative inline-block min-w-full overflow-auto rounded-xl px-6 py-2 my-2 align-middle">
            <StudentStats
                :filters="filters"
                :loading="isStatsLoading"
                :refresh-key="studentRefreshKey"
                @filter="onStatFilter"
            />
            <StudentFilters :filters="filters" show-export-button @change="onStudentFiltersChange" />
        </div>
        <StudentExportModal />
        <DataTable
            :data="students.data"
            :filters="filters"
            :show-archived-filter="false"
            :pagination="{ ...students.links, ...students.meta }"
            :columns="createStudentColumns()"
            :use-api="true"
            :search-url="route('v1.students.index')"
            :api-fetch-action="loadStudentsFromUrl"
            :hide-built-in-search="true" 
            :loading="isLoading"
            :show-column-filters="false"
        />
    </PageContainer>
</template>
