<script setup lang="ts">
import { computed } from 'vue'
import { StudentHeader } from '@/types/students';
import { trans } from 'laravel-vue-i18n';


  interface Props {
    data: StudentHeader | null;
  }
  const props = defineProps<Props>();

    const yearSemesterDisplay = computed(() => {
      const calendar = props.data?.academicCalendar?.trim();
      const yearOption = props.data?.academicYearOption?.trim();

      if (calendar && yearOption) {
        return `${calendar} · ${yearOption}`;
      }

      return calendar || yearOption || '';
    });

    const levelCourseDisplay = computed(() => {
      const level = props.data?.level?.trim();
      const course = props.data?.course?.trim();

      if (level && course) {
        return `${level} ${trans('general.in')} ${course}`;
      }

      return level || course || '';
    });

    const trackingBadge = computed(() => {
      if (props.data?.studentNumber?.trim()) {
        return props.data.studentNumber;
      }

      return props.data?.applicationTrackingNumber?.trim() || '';
    });

</script>
<template>
  <section class="w-full min-w-0 px-2 py-1.5 sm:px-3">
    <div class="flex w-full min-w-0 flex-col gap-1.5">
      <div class="flex w-full min-w-0 items-center justify-between gap-2">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-1.5 gap-y-0.5">
          <h1 class="min-w-0 text-sm font-extrabold uppercase leading-tight tracking-tight wrap-break-word text-foreground sm:text-base">
            {{ data?.studentName }}
          </h1>
          <span
            v-if="data?.enrolmentStatus"
            class="inline-flex shrink-0 items-center rounded-full border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[10px] font-semibold leading-none text-emerald-600 dark:text-emerald-400"
          >
            {{ data.enrolmentStatus }}
          </span>
          <span
            v-else-if="data?.applicationStatus"
            class="inline-flex shrink-0 items-center rounded-full border border-amber-500/30 bg-amber-500/15 px-1.5 py-px text-[10px] font-semibold leading-none text-amber-700 dark:text-amber-400"
          >
            {{ data.applicationStatus }}
          </span>
          <span
            v-if="trackingBadge"
            class="shrink-0 rounded-full bg-muted px-1.5 py-px font-mono text-[10px] leading-none tracking-wide text-foreground"
          >
            {{ trackingBadge }}
          </span>
        </div>
        <div v-if="$slots.actions" class="shrink-0">
          <slot name="actions" />
        </div>
      </div>

      <p
        v-if="levelCourseDisplay"
        class="w-full min-w-0 text-[11px] font-medium leading-tight wrap-break-word uppercase text-foreground sm:text-xs"
      >
        {{ levelCourseDisplay }}
      </p>

      <div class="flex w-full min-w-0 flex-wrap items-start gap-1">
        <span
          v-if="yearSemesterDisplay"
          class="inline-flex max-w-full items-center rounded-full border border-primary/30 bg-primary/15 px-1.5 py-0.5 text-[10px] font-medium leading-snug text-primary"
        >
          <span class="min-w-0 wrap-break-word">📅 {{ yearSemesterDisplay }}</span>
        </span>
        <span
          v-else-if="data?.intakePeriod"
          class="inline-flex max-w-full items-center rounded-full border border-primary/30 bg-primary/15 px-1.5 py-0.5 text-[10px] font-medium leading-snug text-primary"
        >
          <span class="min-w-0 wrap-break-word">📅 {{ data.intakePeriod }}</span>
        </span>
        <span
          v-if="data?.modeOfStudy"
          class="inline-flex max-w-full items-center rounded-full border border-sky-500/30 bg-sky-500/15 px-1.5 py-0.5 text-[10px] font-medium leading-snug text-sky-600 dark:text-sky-400"
        >
          <span class="min-w-0 wrap-break-word">⏱️ {{ data.modeOfStudy }}</span>
        </span>
        <span
          v-if="data?.department"
          class="inline-flex max-w-full items-center rounded-full border border-purple-500/30 bg-purple-500/15 px-1.5 py-0.5 text-[10px] font-medium leading-snug text-purple-600 dark:text-purple-400"
        >
          <span class="min-w-0 wrap-break-word">🏛️ {{ data.department }}</span>
        </span>
      </div>
    </div>
  </section>
</template>
<style scoped>
    button:active {
      transform: scale(0.97);
    }
  </style>
