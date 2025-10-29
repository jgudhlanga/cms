<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton, GenericButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { TypeVariant } from '@/enums/type-variants';
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
const { navigateTo, formatDate } = useUtils();
const eligibleForMoreApplications = () => {
    return multipleApplicationsLevelIds.some((levelId) => {
        const sameLevelApplications = applications.filter((app: Enrolment) => {
            return app?.attributes?.levelId === levelId;
        });
        const allowed = Number(sameLevelApplications[0]?.attributes.allowedApplicationsPerLevel ?? 0);
        if (allowed <= 0) return false;
        return sameLevelApplications.length < allowed;
    });
};

const remainingSlots = () => {
    return multipleApplicationsLevelIds.reduce((total: number, levelId) => {
        const sameLevelApplications = applications.filter((app: Enrolment) => {
            return app?.attributes?.levelId === levelId;
        });

        const allowed = Number(sameLevelApplications[0]?.attributes?.allowedApplicationsPerLevel ?? 0);
        const currentCount = Number(sameLevelApplications.length);

        if (allowed > 0) {
            const remaining = Math.max(allowed - currentCount, 0);
            return total + remaining;
        }

        return total;
    }, 0);
};

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard', href: route('portal.dashboard') }, { transChoiceKey: 'application' }];
</script>
<template>
    <Head :title="$tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="my-6 grid grid-cols-1 gap-4 md:grid-cols-2" v-if="eligibleForMoreApplications()">
            <BaseAlert :description="`You can apply for ${remainingSlots()} more courses`" :type="TypeVariant.info" />
            <div class="flex w-full items-center justify-end">
                <GenericButton
                    :icon="IconName.add"
                    class="w-full rounded-full md:w-[200px]"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.primary_outline"
                    @click="() => navigateTo(route('portal.add-program', { student: props.student.id }))"
                    title="New Application"
                />
            </div>
        </div>
        <div v-if="applications && applications.length > 0" class="my-6 space-y-4">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow" v-for="application in applications" :key="application.id">
                <!-- Card Header -->
                <div class="bg-card border-b border-gray-100 px-4 py-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-accent-foreground text-xs font-semibold uppercase">
                            {{ application.attributes.course }}
                        </h3>
                        <div class="flex space-x-2">
                            <BaseButton
                                title="Edit"
                                :variant="ColorVariant.success_outline"
                                :size="ButtonSize.xs"
                                class="rounded-full"
                                @click="navigateTo(route('portal.application.edit', String(application.id ?? '')))"
                            />
                        </div>
                    </div>
                </div>
                <!-- Card Body -->
                <div class="p-4">
                    <div class="text-accent-foreground grid grid-cols-1 gap-4 text-sm md:grid-cols-5">
                        <GridLabelValue :label="$tChoice('trans.department', 1)" :value="application.attributes.department" />
                        <GridLabelValue :label="$tChoice('trans.level', 1)" :value="application.attributes.level" />
                        <GridLabelValue :label="$tChoice('trans.application_date', 1)" :value="formatDate(application.attributes.createdAt, 'L')" />
                        <GridLabelValue :label="$tChoice('trans.update_date', 1)" :value="formatDate(application.attributes.updatedAt, 'L')" />
                        <GridLabelValue
                            :label="`${$tChoice('trans.application', 1)} ${$tChoice('trans.status', 1)}`"
                            :value="application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? ''"
                        />
                    </div>
                </div>
                <!-- Card Footer -->
                <div class="flex space-x-2 p-4">
                    <BaseButton
                        type="button"
                        title="View Application"
                        :variant="ColorVariant.primary_outline"
                        classes="rounded-full"
                        :size="ButtonSize.xs"
                        @click="navigateTo(route('portal.application.view', String(application.id ?? '')))"
                    />
                </div>
            </div>
        </div>
        <Empty v-else />
    </PageContainer>
</template>
