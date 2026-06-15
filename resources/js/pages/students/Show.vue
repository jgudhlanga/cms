<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { storeToRefs } from 'pinia';

import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import { useStudentProfile } from '@/composables/students/useStudentProfile';
import { useStudentProfileHeader } from '@/composables/students/useStudentProfileHeader';
import { useStudentsStore } from '@/store/students/useStudentsStore';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';
import { Link } from '@/types/ui';

interface Props {
    student: Student;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { student } = props;
const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'student', href: route('students.index') },
    { transChoiceKey: 'students.profile', transChoiceKeyIndex: 1 },
];

const { profileTabs } = useStudentProfile();

const { activeTab } = storeToRefs(useStudentsStore());
const { headerData } = useStudentProfileHeader(() => student);

const visibleTabs = computed(() => profileTabs(student));

const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));

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
        <div class="w-full min-w-0 max-w-full overflow-x-clip rounded-xl bg-card text-card-foreground">
            <Header :data="headerData" />
            <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" />
            <div class="px-2 py-1">
                <component :is="activeSection?.component" v-if="activeSection" />
            </div>
        </div>
    </PageContainer>
</template>
