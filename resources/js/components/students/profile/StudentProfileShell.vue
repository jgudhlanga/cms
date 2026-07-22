<script setup lang="ts">
import BackNavigationButton from '@/components/core/button/BackNavigationButton.vue';
import Header from '@/components/students/profile/Header.vue';
import InvalidIdNumberBanner from '@/components/students/profile/InvalidIdNumberBanner.vue';
import { useStudentProfileHeader } from '@/composables/students/useStudentProfileHeader';
import type { StudentProfileTabValue } from '@/composables/students/useStudentProfile';
import type { Student } from '@/types/students';
import type { Link } from '@/types/ui';

interface Props {
    student: Student;
    activeTab?: StudentProfileTabValue;
    backUrl?: string;
    backDestination?: Link;
    showBack?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showBack: false,
});

const { headerData } = useStudentProfileHeader(() => props.student);
</script>

<template>
    <div class="w-full min-w-0 max-w-full overflow-x-clip rounded-xl bg-card text-card-foreground">
        <Header :data="headerData">
            <template v-if="showBack && backUrl" #actions>
                <BackNavigationButton :url="backUrl" :destination="backDestination" />
            </template>
        </Header>
        <div class="px-2 sm:px-3">
            <InvalidIdNumberBanner :student="props.student" />
        </div>
        <div class="w-full min-w-0 px-2 py-0.5 pb-3 sm:px-3 md:pb-1">
            <slot />
        </div>
    </div>
</template>
