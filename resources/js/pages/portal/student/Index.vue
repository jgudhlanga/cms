<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import ComponentHeader from '@/pages/dashboard/partials/ComponentHeader.vue';
import { AuthObject } from '@/types/data-pagination';
import { Enrolment, OLevelSubjectResult } from '@/types/enrolments';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

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

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }, { title: user.attributes?.name }];
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col space-y-6">
            <div class="flex w-full flex-col">
                <ComponentHeader header-title="Current applications" description="Overview of your applications" />
            </div>
            <div class="flex w-full flex-col">
                <ComponentHeader header-title="O Levels" description="Your O-Level grades you provided" />
            </div>
        </div>
    </PageContainer>
</template>
