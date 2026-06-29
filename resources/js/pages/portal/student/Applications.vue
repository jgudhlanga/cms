<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import ApplicationsByIntakePeriod from '@/components/students/applications/ApplicationsByIntakePeriod.vue';
import { useStudents } from '@/composables/students/useStudents';
import { CURRENT_INTAKE_PERIOD_ID } from '@/lib/constants';
import { AuthObject } from '@/types/data-pagination';
import { Enrolment } from '@/types/enrolments';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AddApplicationButton from '@/pages/portal/application/partials/AddApplicationButton.vue';

interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
    applications: Enrolment[];
    multipleApplicationsLevelIds: string[] | number[];
}

const props = defineProps<Props>();
const { applications } = props;

const { showCreateNewProgramButton } = useStudents();
const page = usePage();
const returningStudent = computed(() => page.props.returningStudent as { hasReapplyAcknowledgement?: boolean } | null);
const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard', href: route('portal.dashboard') }, { transChoiceKey: 'application' }];
</script>

<template>
    <Head :title="$tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full items-center justify-end" v-if="showCreateNewProgramButton(applications, CURRENT_INTAKE_PERIOD_ID) && returningStudent?.hasReapplyAcknowledgement">
            <AddApplicationButton :student="student" />
        </div>
        <div class="my-6">
            <ApplicationsByIntakePeriod v-if="applications && applications.length > 0" :applications="applications" />
            <Empty v-else />
        </div>
    </PageContainer>
</template>
