<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import InstitutionDepartmentNameCell from '@/components/institution/InstitutionDepartmentNameCell.vue';
import EnrolmentSetupTabs from '@/pages/institution/enrolments/partials/EnrolmentSetupTabs.vue';
import IntakePeriodClassSizeConfig from '@/pages/institution/departments/partials/view/IntakePeriodClassSizeConfig.vue';
import type { Link } from '@/types/ui';
import { InstitutionDepartment } from '@/types/institution';
import { Head } from '@inertiajs/vue3';

interface Props {
    department: InstitutionDepartment;
    navigationDepartments: Array<{
        id: number;
        name: string;
        departmentCode: string;
        colorCode?: string | null;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'institution_setup', href: route('institution.setup') },
    { title: 'Enrolment setup', href: route('application-offerings.index') },
    { title: props.department.attributes?.department ?? '' },
];
</script>

<template>
    <Head :title="`${$t('application_requirements.class_sizes_heading')} — ${department.attributes?.department}`" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('application-offerings.index')">
        <div class="space-y-4">
            <header class="rounded-lg border border-border bg-card px-3 py-2.5">
                <InstitutionDepartmentNameCell
                    :department-name="department.attributes?.department ?? ''"
                    :color-code="department.attributes?.colorCode"
                />
                <p class="mt-2 text-xs text-muted-foreground">
                    {{ $t('application_requirements.class_sizes_description') }}
                </p>
            </header>

            <EnrolmentSetupTabs :department-id="Number(department.id)" active="class-sizes" />

            <IntakePeriodClassSizeConfig :department="department" enrolment-setup />
        </div>
    </PageContainer>
</template>
