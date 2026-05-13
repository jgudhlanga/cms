<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudents } from '@/composables/students/useStudents';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';
import { Link } from '@/types/ui';
import { User } from '@/types/users';
import { useStudentProfile } from '@/composables/students/useStudentProfile';
import { useStudentsStore } from '@/store/students/useStudentsStore';
import { storeToRefs } from 'pinia';
import { icons } from '@/lib/icons';

interface Props {
    user: User;
    student: Student | null;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { student, user } = props;
const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'student', href: route('students.index') },
    { title: user.attributes.name ?? '' },
];

const { isNativeCitizen, formatDate, navigateTo } = useUtils();
const { hasOfferLetter } = useStudents();

const { profileTabs } = useStudentProfile();

const { activeTab } = storeToRefs(useStudentsStore());
</script>

<template>
    <Head :title="$tChoice('student', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <!-- <PageHeaderAvatar :line-one="user.attributes?.name" :line-two="user.attributes?.email" /> -->
         <Header />
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger
                    v-for="tab in profileTabs()"
                    :key="'tab_' + tab.value"
                    :value="tab.value"
                    class="text-xs font-light uppercase flex items-center"
                >
                    <component :is="icons[tab?.icon!]" />
                    <span>{{ tab?.transLabel!() }}</span>
                </TabsTrigger>  
            </TabsList>
            <TabsContent v-for="tab in profileTabs()" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                <component :is="tab.component" />
            </TabsContent>
        </Tabs>
    </PageContainer>
</template>
