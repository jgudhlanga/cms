<script setup lang="ts">
import BackNavigationButton from '@/components/core/button/BackNavigationButton.vue';
import ChangeStudentNumberModal from '@/components/students/profile/ChangeStudentNumberModal.vue';
import ChangeStudentStatusModal, {
    type StudentStatusOption,
} from '@/components/students/profile/ChangeStudentStatusModal.vue';
import Header from '@/components/students/profile/Header.vue';
import InvalidIdNumberBanner from '@/components/students/profile/InvalidIdNumberBanner.vue';
import { useStudentProfileHeader } from '@/composables/students/useStudentProfileHeader';
import type { StudentProfileTabValue } from '@/composables/students/useStudentProfile';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import { useModalStore } from '@/store/core/useModalStore';
import type { Student } from '@/types/students';
import type { Link } from '@/types/ui';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const { openModal } = useModalStore();
const page = usePage();

const statusOptions = computed<StudentStatusOption[]>(
    () => (page.props.studentStatusOptions as StudentStatusOption[] | undefined) ?? [],
);

const canChangeStudentNumber = computed(
    () => Boolean(props.student?.id) && hasAbility('change-student-number:students'),
);
const canChangeStatus = computed(
    () => Boolean(props.student?.id) && statusOptions.value.length > 0 && hasAbility('change-student-status:students'),
);
</script>

<template>
    <div class="w-full min-w-0 max-w-full overflow-x-clip rounded-xl bg-card text-card-foreground">
        <Header
            :data="headerData"
            @edit-student-number="openModal(APP_MODULE_KEYS.student_number_change)"
            @edit-status="openModal(APP_MODULE_KEYS.student_status_change)"
        >
            <template v-if="showBack && backUrl" #actions>
                <BackNavigationButton :url="backUrl" :destination="backDestination" />
            </template>
        </Header>
        <ChangeStudentNumberModal
            v-if="canChangeStudentNumber"
            :student-id="props.student.id!"
            :student-number="headerData.studentNumber"
        />
        <ChangeStudentStatusModal
            v-if="canChangeStatus"
            :student-id="props.student.id!"
            :status-options="statusOptions"
            :current-status="headerData.applicationStatus"
        />
        <div class="px-2 sm:px-3">
            <InvalidIdNumberBanner :student="props.student" />
        </div>
        <div class="w-full min-w-0 px-2 py-0.5 pb-3 sm:px-3 md:pb-1">
            <slot />
        </div>
    </div>
</template>
