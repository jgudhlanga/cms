<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { AuthObject } from '@/types/data-pagination';
import { Enrolment } from '@/types/enrolments';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
    applications: Enrolment[];
    multipleApplicationsLevelIds: string[] | number[];
}

const props = defineProps<Props>();
const { applications, multipleApplicationsLevelIds } = props;
const { navigateTo } = useUtils();
const eligibleForMoreApplications = () => {
    return multipleApplicationsLevelIds.some((levelId) => {
        // Get applications in this level
        const sameLevelApplications = applications.filter((app: Enrolment) => {
            return app?.attributes?.levelId === levelId;
        });
        // How many are allowed at this level
        const allowed = Number(sameLevelApplications[0]?.attributes.allowedApplicationsPerLevel ?? 0);
        if (allowed <= 0) return false;
        // Student can apply if current count < allowed
        return sameLevelApplications.length < allowed;
    });
};

const remainingSlots = () => {
    return multipleApplicationsLevelIds.reduce((total: number, levelId) => {
        const sameLevelApplications = applications.filter((app: Enrolment) => {
            return app?.attributes?.levelId === levelId;
        });

        const allowed = Number(
            sameLevelApplications[0]?.attributes?.allowedApplicationsPerLevel ?? 0
        );
        const currentCount = Number(sameLevelApplications.length);

        if (allowed > 0) {
            const remaining = Math.max(allowed - currentCount, 0);
            return total + remaining; // now always number
        }

        return total;
    }, 0);
};



const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard', href: route('portal.dashboard') }, { transChoiceKey: 'application' }];
const { createStudentApplicationColumns, allowed } = useStudentApplications();
</script>
<template>
    <Head :title="$tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div v-if="eligibleForMoreApplications()" class="text-destructive flex w-fit rounded-full bg-amber-200 px-5 py-1 leading-tight">
            {{ `You can apply for ${remainingSlots()} more courses` }}
        </div>
        <DataTable
            :data="applications"
            :show-archived-filter="false"
            :columns="createStudentApplicationColumns()"
            :on-create="() => eligibleForMoreApplications() ? navigateTo(route('portal.add-program', { student: props.student.id })) : null"
            :disable-create="!allowed"
        >
        </DataTable>
    </PageContainer>
</template>
