<script setup lang="ts">
import AcademicCalendarClassTutorBadge from '@/components/academicCalendars/AcademicCalendarClassTutorBadge.vue';
import type { AcademicCalendarClassPreview } from '@/types/academic-calendar';
import { UserIcon, UserRoundIcon, Users } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    classPreview: AcademicCalendarClassPreview;
    showUrl?: string | null;
    canAssignStaffing?: boolean;
    showModuleStaffing?: boolean;
}>();

const emit = defineEmits<{
    assignTutor: [classId: number, staffId?: number | null];
}>();

const isSavedClass = computed(() => props.classPreview.academicCalendarClassId != null);
const isClickable = computed(() => props.showUrl != null && props.showUrl !== '');

const cardClass = computed(() => {
    const base = 'block overflow-hidden rounded-xl border border-border bg-card shadow-sm transition-all duration-200';

    return isClickable.value
        ? `${base} cursor-pointer hover:-translate-y-px hover:shadow-md`
        : `${base} cursor-default opacity-80`;
});

const onAssignTutor = (): void => {
    if (props.classPreview.academicCalendarClassId == null) {
        return;
    }

    emit('assignTutor', props.classPreview.academicCalendarClassId, props.classPreview.tutor?.id ?? null);
};

const onCardClick = (): void => {
    if (!isClickable.value || props.showUrl == null) {
        return;
    }

    router.visit(props.showUrl);
};
</script>

<template>
    <div
        :class="cardClass"
        :role="isClickable ? 'link' : undefined"
        :tabindex="isClickable ? 0 : undefined"
        @click="onCardClick"
        @keydown.enter.prevent="onCardClick"
        @keydown.space.prevent="onCardClick"
    >
        <div class="h-0.5 bg-linear-to-r from-sky-400 to-blue-600" />

        <div class="p-3">
            <div class="mb-1.5 flex flex-wrap items-center justify-between gap-1.5">
                <h2 class="text-xs font-semibold tracking-tight text-foreground sm:text-sm">{{ classPreview.name }}</h2>
                <div class="flex flex-wrap items-center gap-1">
                    <span
                        v-if="showModuleStaffing && classPreview.moduleStaffing && classPreview.moduleStaffing.total > 0"
                        class="inline-flex items-center rounded-full border border-border bg-muted px-1.5 py-px text-[10px] font-medium text-muted-foreground"
                    >
                        {{
                            $t('academic_calendar.modules_staffed_badge', {
                                staffed: classPreview.moduleStaffing.staffed,
                                total: classPreview.moduleStaffing.total,
                            })
                        }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full border px-1.5 py-px text-[10px] font-medium"
                        :class="
                            isSavedClass
                                ? 'border-green-200 bg-green-50 text-green-700'
                                : 'border-border bg-muted text-muted-foreground'
                        "
                    >
                        {{ isSavedClass ? $t('hms.status_active') : $t('trans.preview') }}
                    </span>
                </div>
            </div>

            <div v-if="isSavedClass" class="mb-1.5" @click.stop.prevent>
                <AcademicCalendarClassTutorBadge
                    :tutor="classPreview.tutor ?? null"
                    :can-assign="canAssignStaffing === true"
                    compact
                    @assign="onAssignTutor"
                />
            </div>

            <div class="flex items-center justify-between gap-1 rounded-lg bg-muted/60 px-2 py-1.5 text-center">
                <div class="flex min-w-0 flex-1 flex-col items-center gap-0">
                    <Users class="h-3 w-3 text-muted-foreground" />
                    <span class="text-[10px] leading-tight text-muted-foreground">{{ $t('students.class_total') }}</span>
                    <span class="text-xs font-semibold leading-tight text-foreground">{{ classPreview.studentCount }}</span>
                </div>
                <div class="flex min-w-0 flex-1 flex-col items-center gap-0">
                    <UserIcon class="h-3 w-3 text-blue-600" />
                    <span class="text-[10px] leading-tight text-muted-foreground">{{ $tChoice('general.male', 2) }}</span>
                    <span class="text-xs font-semibold leading-tight text-foreground">{{ classPreview.genderCounts?.male ?? 0 }}</span>
                </div>
                <div class="flex min-w-0 flex-1 flex-col items-center gap-0">
                    <UserRoundIcon class="h-3 w-3 text-pink-600" />
                    <span class="text-[10px] leading-tight text-muted-foreground">{{ $tChoice('general.female', 2) }}</span>
                    <span class="text-xs font-semibold leading-tight text-foreground">{{ classPreview.genderCounts?.female ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
