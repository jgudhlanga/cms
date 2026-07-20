<script setup lang="ts">
import AcademicCalendarClassTutorBadge from '@/components/academicCalendars/AcademicCalendarClassTutorBadge.vue';
import type { TeachingClassCard } from '@/types/lecturer';
import { UserIcon, UserRoundIcon, Users } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    classCard: TeachingClassCard;
    showUrl?: string | null;
}>();

const isClickable = computed(() => props.showUrl != null && props.showUrl !== '');

const cardClass = computed(() => {
    const base = 'block overflow-hidden rounded-xl border border-border bg-card shadow-sm transition-all duration-200';

    return isClickable.value
        ? `${base} cursor-pointer hover:-translate-y-px hover:shadow-md`
        : `${base} cursor-default`;
});

const metaLine = computed(() =>
    [props.classCard.courseName, props.classCard.levelName, props.classCard.modeOfStudyName]
        .filter((value) => value != null && String(value).trim() !== '')
        .join(' · '),
);

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return '—';
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const onCardClick = (): void => {
    if (!isClickable.value || props.showUrl == null) {
        return;
    }

    router.visit(props.showUrl);
};

const isAssignedCode = (code: string): boolean => props.classCard.assignedModuleCodes.includes(code);
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

        <div class="p-3 space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <h2 class="text-xs font-semibold tracking-tight text-foreground sm:text-sm">
                            {{ classCard.name }}
                        </h2>
                        <span
                            v-if="classCard.isTutor"
                            class="inline-flex rounded-md bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary"
                        >
                            {{ $t('dashboard.lecturer_is_tutor') }}
                        </span>
                    </div>
                    <p v-if="metaLine" class="text-[11px] text-muted-foreground">{{ metaLine }}</p>
                </div>
                <span
                    class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-1.5 py-px text-[10px] font-medium text-green-700"
                >
                    {{ $t('hms.status_active') }}
                </span>
            </div>

            <div @click.stop.prevent>
                <AcademicCalendarClassTutorBadge
                    :tutor="classCard.tutor ?? null"
                    :can-assign="false"
                    compact
                />
            </div>

            <div v-if="classCard.moduleCodes.length > 0" class="space-y-1.5">
                <p class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                    {{ $tChoice('trans.module', 2) }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="code in classCard.moduleCodes"
                        :key="code"
                        class="inline-flex rounded-md border px-1.5 py-0.5 font-mono text-[10px]"
                        :class="
                            isAssignedCode(code)
                                ? 'border-primary/30 bg-primary/10 text-primary'
                                : 'border-border bg-muted text-muted-foreground'
                        "
                    >
                        {{ code }}
                    </span>
                </div>
            </div>

            <div v-if="classCard.assessmentWindows.length > 0" class="space-y-1.5">
                <p class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                    {{ $tChoice('trans.assessment_calendar', 2) }}
                </p>
                <div class="space-y-1">
                    <div
                        v-for="(window, index) in classCard.assessmentWindows"
                        :key="`${window.assessmentTypeName}-${window.startDate}-${index}`"
                        class="flex items-center justify-between gap-2 rounded-md border border-border/70 bg-muted/30 px-2 py-1.5"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-medium text-foreground">
                                {{ window.assessmentTypeName }}
                            </p>
                            <p class="text-[10px] text-muted-foreground">
                                {{ formatDate(window.startDate) }} – {{ formatDate(window.endDate) }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="
                                window.isOpen
                                    ? 'bg-green-50 text-green-700'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{
                                window.isOpen
                                    ? $t('dashboard.lecturer_assessment_window_open')
                                    : $t('dashboard.lecturer_assessment_window_closed')
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-1 rounded-lg bg-muted/60 px-2 py-1.5 text-center">
                <div class="flex min-w-0 flex-1 flex-col items-center gap-0">
                    <Users class="h-3 w-3 text-muted-foreground" />
                    <span class="text-[10px] leading-tight text-muted-foreground">{{ $t('students.class_total') }}</span>
                    <span class="text-xs font-semibold leading-tight text-foreground">{{ classCard.studentCount }}</span>
                </div>
                <div class="flex min-w-0 flex-1 flex-col items-center gap-0">
                    <UserIcon class="h-3 w-3 text-blue-600" />
                    <span class="text-[10px] leading-tight text-muted-foreground">{{ $tChoice('general.male', 2) }}</span>
                    <span class="text-xs font-semibold leading-tight text-foreground">{{ classCard.genderCounts?.male ?? 0 }}</span>
                </div>
                <div class="flex min-w-0 flex-1 flex-col items-center gap-0">
                    <UserRoundIcon class="h-3 w-3 text-pink-600" />
                    <span class="text-[10px] leading-tight text-muted-foreground">{{ $tChoice('general.female', 2) }}</span>
                    <span class="text-xs font-semibold leading-tight text-foreground">{{ classCard.genderCounts?.female ?? 0 }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-center sm:grid-cols-4">
                <div class="rounded-md border border-border/70 bg-background px-2 py-1.5">
                    <p class="text-[10px] text-muted-foreground">{{ $tChoice('trans.module', 2) }}</p>
                    <p class="text-xs font-semibold text-foreground">{{ classCard.stats.assignedModuleCount }}</p>
                </div>
                <div class="rounded-md border border-border/70 bg-background px-2 py-1.5">
                    <p class="text-[10px] text-muted-foreground">{{ $t('dashboard.lecturer_missing_coursework') }}</p>
                    <p class="text-xs font-semibold text-foreground">{{ classCard.stats.missingCourseWorkCount }}</p>
                </div>
                <div class="rounded-md border border-border/70 bg-background px-2 py-1.5">
                    <p class="text-[10px] text-muted-foreground">{{ $t('dashboard.lecturer_pass_rate') }}</p>
                    <p class="text-xs font-semibold text-foreground">
                        {{ classCard.stats.passRate != null ? `${classCard.stats.passRate}%` : '—' }}
                    </p>
                </div>
                <div class="rounded-md border border-border/70 bg-background px-2 py-1.5">
                    <p class="text-[10px] text-muted-foreground">{{ $t('dashboard.lecturer_average_mark') }}</p>
                    <p class="text-xs font-semibold text-foreground">
                        {{ classCard.stats.averageMark != null ? classCard.stats.averageMark : '—' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
