<script setup lang="ts">
import ConfirmStudyPositionModal from '@/components/students/study-position/ConfirmStudyPositionModal.vue';
import StudyPositionBanner from '@/components/students/study-position/StudyPositionBanner.vue';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import type { StudyPositionBannerItem, StudyPositionStatus, StudyPositionSummary } from '@/types/study-position';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        context?: 'admin' | 'portal';
        studentId?: string | number | null;
    }>(),
    {
        context: 'admin',
        studentId: null,
    },
);

const page = usePage();
const { openModal } = useModalStore();

// Admin pages carry the full status for the viewed student; portal pages the student's own summary.
const adminStatus = computed<StudyPositionStatus | null>(() =>
    props.context === 'admin' ? ((page.props.studentStudyPosition as StudyPositionStatus | null | undefined) ?? null) : null,
);
const portalSummary = computed<StudyPositionSummary | null>(() =>
    props.context === 'portal' ? ((page.props.studyPosition as StudyPositionSummary | null | undefined) ?? null) : null,
);

const state = computed(() => adminStatus.value?.state ?? portalSummary.value?.state ?? null);
const periodLabel = computed(() => adminStatus.value?.periodLabel ?? portalSummary.value?.periodLabel ?? null);

const items = computed<StudyPositionBannerItem[]>(() => {
    if (adminStatus.value) {
        return adminStatus.value.items.map((item) => ({
            programme: item.programme.label,
            state: item.state,
            answeredPhase: item.confirmation?.phase?.label ?? null,
            systemPhase: item.systemPhase?.label ?? null,
            answerLabel: item.confirmation?.answerLabel ?? null,
            syncNote: item.confirmation?.syncNote ?? null,
            drift: item.drift,
        }));
    }

    return (portalSummary.value?.items ?? []).map((item) => ({
        programme: item.programme,
        state: item.state,
        answeredPhase: item.answeredPhase,
        systemPhase: item.systemPhase,
    }));
});

const canConfirm = computed(() => Boolean(adminStatus.value?.items.some((item) => item.canConfirm)));

const onConfirm = (): void => {
    openModal(props.context === 'admin' ? APP_MODULE_KEYS.student_study_position_confirm : APP_MODULE_KEYS.student_study_position_prompt);
};
</script>

<template>
    <StudyPositionBanner
        v-if="state"
        :context="context"
        :state="state"
        :period-label="periodLabel"
        :items="items"
        :can-confirm="canConfirm"
        @confirm="onConfirm"
    />
    <ConfirmStudyPositionModal v-if="context === 'admin' && canConfirm && adminStatus && studentId" :student-id="studentId" :status="adminStatus" />
</template>
