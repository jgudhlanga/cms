<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import StudentProfileShell from '@/components/students/profile/StudentProfileShell.vue';
import Info from '@/pages/students/components/profile/Info.vue';
import Programs from '@/pages/students/components/profile/Programs.vue';
import Applications from '@/pages/students/components/profile/Applications.vue';
import Financials from '@/pages/students/components/profile/Financials.vue';
import Hostels from '@/pages/students/components/profile/Hostels.vue';
import Documents from '@/pages/students/components/profile/Documents.vue';
import Authentication from '@/components/users/Authentication.vue';
import {
    studentProfileTabDefinitions,
    type StudentProfileTabValue,
} from '@/composables/students/useStudentProfile';
import type { AuthObject } from '@/types/data-pagination';
import type { Student } from '@/types/students';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    student: Student;
    activeTab: StudentProfileTabValue;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const tabDefinition = computed(() =>
    studentProfileTabDefinitions().find((tab) => tab.value === props.activeTab),
);

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'dashboard', href: route('portal.dashboard') },
    {
        title: tabDefinition.value?.transLabel() ?? '',
    },
]);

const pageTitle = computed(() => tabDefinition.value?.transLabel() ?? '');
</script>

<template>
    <Head :title="pageTitle" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <StudentProfileShell :student="student" :active-tab="activeTab">
            <Info v-if="activeTab === 'basic_info'" :student="student" />
            <Programs v-else-if="activeTab === 'programs'" :student="student" />
            <Applications v-else-if="activeTab === 'applications'" :student="student" />
            <Financials v-else-if="activeTab === 'financials'" :student="student" />
            <Hostels v-else-if="activeTab === 'accommodations'" />
            <Documents v-else-if="activeTab === 'documents'" />
            <Authentication
                v-else-if="activeTab === 'authentication'"
                :user="student?.relationships?.user"
                hide-authorization
            />
        </StudentProfileShell>
    </PageContainer>
</template>
