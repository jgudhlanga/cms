<script setup lang="ts">
import SettingsButton from '@/components/core/button/SettingsButton.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import {
    canViewAcademicDepartmentsMenu,
    canViewNonAcademicDepartmentsMenu,
    hasAbility,
} from '@/lib/permissions';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ auth: AuthObject; errors: object }>();
const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'institution', transChoiceKeyIndex: 1 }];

const { navigateTo } = useUtils();
const gotToDepartments = (is_academic: number) => {
    return navigateTo(route('institution-departments.index', { is_academic: is_academic }));
};

const canViewNonAcademicDepartments = computed(() => canViewNonAcademicDepartmentsMenu());
const canViewAcademicDepartments = computed(() => canViewAcademicDepartmentsMenu());
</script>

<template>
    <Head :title="$tChoice('trans.institution', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="canViewNonAcademicDepartments">
            <HeadingSmall
                :title="`${$t('trans.non_academic')} ${$tChoice('trans.department', 2)}`"
                :description="$t('trans.non_academic_department_description')"
            />
            <SettingsButton class="mt-2" :title="$t('trans.manage')" @click="gotToDepartments(0)" />
        </template>
        <template v-if="canViewAcademicDepartments">
            <HeadingSmall
                :title="`${$t('trans.academic')} ${$tChoice('trans.department', 2)}`"
                :description="$t('trans.academic_department_description')"
                class="mt-6"
            />
            <SettingsButton class="mt-2" @click="gotToDepartments(1)" :title="$t('trans.manage')" />
        </template>
        <template v-if="hasAbility('view:institution-settings')">
            <HeadingSmall :title="$t('trans.institution_config')" :description="$t('trans.institution_config_description')" class="mt-6" />
            <SettingsButton class="mt-2" @click="navigateTo(route('institution.setup'))" :title="$t('trans.setup')" />
        </template>
        <template v-if="hasAbility('root:manage')">
            <HeadingSmall :title="$t('trans.ui_payments_debug')" :description="$t('trans.ui_debug_and_check_payment_statuses')" class="mt-6" />
            <SettingsButton class="mt-2" @click="navigateTo(route('integrations.payments.check-status-create'))" :title="$t('trans.ui_debug')" />
        </template>
    </PageContainer>
</template>
