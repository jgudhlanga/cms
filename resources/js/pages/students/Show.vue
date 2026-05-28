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
    { transChoiceKey: 'students.profile', transChoiceKeyIndex: 1 },
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
        <div class="overflow-hidden rounded-xl bg-card text-card-foreground">
            <Header :data="headerData" />
            <Tabs :default-value="activeTab" v-model="activeTab">
                <TabsList
                    class="h-auto min-h-11 w-full flex-wrap justify-start gap-1 rounded-none border-t border-border bg-muted/30 px-2 py-1"
                >
                    <TabsTrigger
                        v-for="tab in profileTabs(student)"
                        :key="'tab_' + tab.value"
                        :value="tab.value"
                        :disabled="tab.disabled"
                        class="flex items-center text-xs font-light uppercase"
                    >
                        <component :is="icons[tab?.icon!]" />
                        <span>{{ tab?.transLabel!() }}</span>
                    </TabsTrigger>
                </TabsList>
                <TabsContent
                    v-for="tab in profileTabs(student)"
                    :value="tab.value"
                    :key="'content_' + tab.value"
                    class="px-2 py-1"
                >
                    <component :is="tab.component" />
                </TabsContent>
            </Tabs>
        </div>
    </PageContainer>
</template>
