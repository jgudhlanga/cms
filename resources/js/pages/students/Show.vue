<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { Student, StudentHeader } from '@/types/students';
import { Link } from '@/types/ui';
import { useStudentProfile } from '@/composables/students/useStudentProfile';
import { useStudentsStore } from '@/store/students/useStudentsStore';
import { storeToRefs } from 'pinia';
import { icons } from '@/lib/icons';
import { computed } from 'vue';

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
    { title: student?.relationships?.user?.attributes.name ?? '' },
];

const { profileTabs } = useStudentProfile();

const { activeTab } = storeToRefs(useStudentsStore());
const headerData = computed<StudentHeader>(() => {
    return {
        studentId: student?.id ?? '',
        studentName: student?.relationships?.user?.attributes.name ?? '',
        avatarUrl: student?.relationships?.user?.attributes.avatarUrl ?? '',
        studentNumber: student?.attributes.studentNumber ?? '',
        level: student?.attributes.level ?? '',
        course: student?.attributes.course ?? '',
        academicCalendar: student?.relationships?.latestEnrolment?.attributes.academicCalendar ?? '',
        academicYearOption: student?.relationships?.latestEnrolment?.attributes.academicYearOption ?? '',
        enrolmentStatus: student?.relationships?.latestEnrolment?.attributes.status ?? '',
        modeOfStudy: student?.attributes.modeOfStudy ?? '',
        department: student?.attributes.department ?? '',
    };
});
</script> 

<template>
    <Head :title="$tChoice('student', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
         <Header :data="headerData" />
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger
                    v-for="tab in profileTabs(student)"
                    :key="'tab_' + tab.value"
                    :value="tab.value"
                    class="text-xs font-light uppercase flex items-center"
                >
                    <component :is="icons[tab?.icon!]" />
                    <span>{{ tab?.transLabel!() }}</span>
                </TabsTrigger>  
            </TabsList>
            <TabsContent v-for="tab in profileTabs(student)" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                <component :is="tab.component" />
            </TabsContent>
        </Tabs>
    </PageContainer>
</template>
