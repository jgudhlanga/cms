<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';

import BackNavigationButton from '@/components/core/button/BackNavigationButton.vue';
import LookupPillButton from '@/components/core/button/LookupPillButton.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import StudentLookupDrawer from '@/components/students/StudentLookupDrawer.vue';
import StudentProfileDangerZone from '@/components/students/profile/StudentProfileDangerZone.vue';
import StudentProfileShell from '@/components/students/profile/StudentProfileShell.vue';
import { useSectionTabQuerySync } from '@/composables/core/useSectionTabQuerySync';
import { useStudentProfile } from '@/composables/students/useStudentProfile';
import { useStudentShowNavigation } from '@/composables/students/useStudentShowNavigation';
import { hasAbility } from '@/lib/permissions';
import { useStudentsStore } from '@/store/students/useStudentsStore';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';

interface Props {
    student: Student;
    activeIntakePeriodIds?: Array<string | number>;
    offerLetterIntakePeriodIds?: Array<string | number>;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const { profileTabs } = useStudentProfile();
const { backUrl, backDestination, breadcrumbs, showBack } = useStudentShowNavigation();

const { activeTab } = storeToRefs(useStudentsStore());

const lookupOpen = ref(false);
const canLookupStudents = computed(() => hasAbility('viewAny:students'));
const lookupDepartmentId = computed(() => props.student.attributes?.institutionDepartmentId ?? null);

const visibleTabs = computed(() => profileTabs(props.student, {
    activeIntakePeriodIds: props.activeIntakePeriodIds,
    offerLetterIntakePeriodIds: props.offerLetterIntakePeriodIds,
}));

const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));

useSectionTabQuerySync(activeTab, () => visibleTabs.value.map((tab) => tab.value));

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
        <template v-if="canLookupStudents || showBack" #backNavigationTrailing>
            <LookupPillButton
                v-if="canLookupStudents"
                :label="$t('students.find_student')"
                @click="lookupOpen = true"
            />
            <BackNavigationButton v-if="showBack" :url="backUrl" :destination="backDestination" pill />
        </template>

        <StudentProfileShell :student="props.student">
            <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" nav-id="student-tabs" />
            <div
                :id="`student-tabs-panel-${activeTab}`"
                role="tabpanel"
                :aria-labelledby="`student-tabs-tab-${activeTab}`"
                tabindex="0"
                class="px-2 py-1"
            >
                <component
                    :is="activeSection?.component"
                    v-if="activeSection"
                    :key="`${activeTab}-${props.student.attributes?.applicationStatus ?? ''}-${props.student.attributes?.studentNumber ?? ''}`"
                />
            </div>
            <StudentProfileDangerZone :student="props.student" />
        </StudentProfileShell>

        <StudentLookupDrawer
            v-if="canLookupStudents"
            v-model:open="lookupOpen"
            :initial-department-id="lookupDepartmentId"
        />
    </PageContainer>
</template>
