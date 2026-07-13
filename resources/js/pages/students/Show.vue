<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';
import { storeToRefs } from 'pinia';

import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import StudentProfileDangerZone from '@/components/students/profile/StudentProfileDangerZone.vue';
import StudentProfileShell from '@/components/students/profile/StudentProfileShell.vue';
import { useStudentProfile } from '@/composables/students/useStudentProfile';
import { useStudentShowNavigation } from '@/composables/students/useStudentShowNavigation';
import { useStudentsStore } from '@/store/students/useStudentsStore';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';

interface Props {
    student: Student;
    activeIntakePeriodIds?: Array<string | number>;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { student } = props;

const validTabValues = ['basic_info', 'programs', 'applications', 'financials', 'accommodations', 'documents', 'authentication'] as const;

const { profileTabs } = useStudentProfile();
const { backUrl, backDestination, breadcrumbs, showBack } = useStudentShowNavigation();

const { activeTab } = storeToRefs(useStudentsStore());

const visibleTabs = computed(() => profileTabs(student, { activeIntakePeriodIds: props.activeIntakePeriodIds }));

const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));

onMounted(() => {
    const tabParam = new URL(usePage().url, window.location.origin).searchParams.get('tab');
    if (tabParam && validTabValues.includes(tabParam as (typeof validTabValues)[number])) {
        activeTab.value = tabParam;
    }
});

watch(
    visibleTabs,
    (tabs) => {
        if (tabs.length === 0) {
            return;
        }

        if (!tabs.some((tab) => tab.value === activeTab.value)) {
            activeTab.value = tabs[0].value;
        }
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="$tChoice('student', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <StudentProfileShell
            :student="student"
            :back-url="backUrl"
            :back-destination="backDestination"
            :show-back="showBack"
        >
            <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" />
            <div class="px-2 py-1">
                <component :is="activeSection?.component" v-if="activeSection" />
            </div>
            <StudentProfileDangerZone :student="student" />
        </StudentProfileShell>
    </PageContainer>
</template>
