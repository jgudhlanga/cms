<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import AddApplicationButton from '@/pages/portal/application/partials/AddApplicationButton.vue';
import CurrentApplications from '@/pages/portal/student/partials/CurrentApplications.vue';
import OLevelResults from '@/pages/portal/student/partials/OLevelResults.vue';
import { AuthObject } from '@/types/data-pagination';
import { Enrolment, OLevelSubjectResult } from '@/types/enrolments';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { useStudents } from '@/composables/students/useStudents';
import { CURRENT_INTAKE_PERIOD_ID } from '@/lib/constants';

interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
    applications: Enrolment[];
    multipleApplicationsLevelIds: string[] | number[];
    oLevelResults: OLevelSubjectResult[];
    currentLevel: string;
}

const props = defineProps<Props>();
const { user } = props.auth;

const { showCreateNewProgramButton } = useStudents();
const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }, { title: user.attributes?.name }];
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full items-center justify-end" v-if="showCreateNewProgramButton(applications, CURRENT_INTAKE_PERIOD_ID)">
            <AddApplicationButton :student="student" />
        </div>
        <div class="mt-6 flex w-full flex-col space-y-6">
            <CurrentApplications :applications="applications" />
            <OLevelResults :o-level-results="oLevelResults" />
        </div>
    </PageContainer>
</template>
