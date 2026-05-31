<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { storeToRefs } from 'pinia';

import PageContainer from '@/components/core/page/PageContainer.vue';
import { useStudentProfile } from '@/composables/students/useStudentProfile';
import { useStudentProfileHeader } from '@/composables/students/useStudentProfileHeader';
import { icons } from '@/lib/icons';
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
            <Tabs :default-value="activeTab" v-model="activeTab">
                <TabsList
                    class="h-auto min-h-11 w-full flex-wrap justify-start gap-1 rounded-none border-t border-border bg-muted/30 px-2 py-1"
                >
                    <TabsTrigger
                        v-for="tab in visibleTabs"
                        :key="'tab_' + tab.value"
                        :value="tab.value"
                        class="flex items-center text-xs font-light uppercase"
                    >
                        <component :is="icons[tab?.icon!]" />
                        <span>{{ tab?.transLabel!() }}</span>
                    </TabsTrigger>
                </TabsList>
                <TabsContent
                    v-for="tab in visibleTabs"
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
