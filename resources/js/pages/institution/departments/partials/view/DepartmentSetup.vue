<script setup lang="ts">
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import ApplicationWorkflowSteps from '@/pages/institution/departments/partials/view/ApplicationWorkflowSteps.vue';
import Courses from '@/pages/institution/departments/partials/view/Courses.vue';
import IntakePeriodClassSizeConfig from '@/pages/institution/departments/partials/view/IntakePeriodClassSizeConfig.vue';
import Levels from '@/pages/institution/departments/partials/view/Levels.vue';
import { InstitutionDepartment } from '@/types/institution';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
</script>

<template>
    <div class="flex flex-col space-y-3">
        <!-- LEVEL SETUP -->
        <div class="flex flex-col">
            <HeadingSmall :title="`${$tChoice('trans.level', 1)} ${$t('trans.setup')}`" :description="$t('trans.levels_config_description')" />
            <Levels :department="department" />
            <CustomSeparator classes="h-1 my-4" />
        </div>
        <!-- COURSE SETUP -->
        <div class="flex flex-col">
            <HeadingSmall :title="`${$tChoice('trans.course', 1)} ${$t('trans.setup')}`" :description="$t('trans.courses_config_description')" />
            <Courses :department="department" />
            <CustomSeparator classes="h-1 my-4" />
        </div>
        <!-- INTAKE CLASS SIZE SETUP -->
        <div class="flex flex-col">
            <HeadingSmall :title="`${$t('trans.enrolment_class_size_setup')}`" :description="$t('trans.intake_class_size_config_description')" />
            <IntakePeriodClassSizeConfig :department="department" />
            <CustomSeparator classes="h-1 my-4" />
        </div>
        <!-- APPLICATION WORKFLOW SETUP -->
        <div class="flex flex-col">
            <HeadingSmall
                :title="`${$tChoice('trans.application', 2)} ${$t('trans.workflow_config')}`"
                :description="$t('trans.application_workflow_config_description')"
            />
            <ApplicationWorkflowSteps :department="department" />
            <CustomSeparator classes="h-1 my-4" />
        </div>
    </div>
</template>
