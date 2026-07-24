<script setup lang="ts">
import { computed } from 'vue';
import { StudentHeader } from '@/types/students';
import { trans, trans_choice } from 'laravel-vue-i18n';

interface Props {
  data: StudentHeader | null;
}

interface HeaderFact {
  label: string;
  value: string;
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

const programmeFacts = computed<HeaderFact[]>(() => {
  const facts: HeaderFact[] = [];
  const intakeOrYear = yearSemesterDisplay.value || props.data?.intakePeriod?.trim() || '';
  const mode = props.data?.modeOfStudy?.trim() || '';
  const department = props.data?.department?.trim() || '';

  if (intakeOrYear) {
    facts.push({
      label: trans('trans.intake'),
      value: intakeOrYear,
    });
  }

  if (mode) {
    facts.push({
      label: trans('trans.mode'),
      value: mode,
    });
  }

  if (department) {
    facts.push({
      label: trans_choice('trans.department', 1),
      value: department,
    });
  }

  return facts;
});

const apprenticeFacts = computed<HeaderFact[]>(() => {
  if (!props.data?.isApprenticeThisYear) {
    return [];
  }

  const facts: HeaderFact[] = [];
  const employer = props.data?.employer?.trim();
  const apprenticeNumber = props.data?.apprenticeNumber?.trim();

  if (employer) {
    facts.push({
      label: trans('trans.employer'),
      value: employer,
    });
  }

  if (apprenticeNumber) {
    facts.push({
      label: trans('trans.apprentice_no'),
      value: apprenticeNumber,
    });
  }

  return facts;
});

const hasProgrammeFacts = computed(() => programmeFacts.value.length > 0);
const hasApprenticeFacts = computed(() => apprenticeFacts.value.length > 0);
</script>

<template>
  <section class="w-full min-w-0 border-b border-border px-2 py-1.5 sm:px-3">
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

      <div
        v-if="hasProgrammeFacts || hasApprenticeFacts"
        class="flex w-full min-w-0 flex-col gap-1"
      >
        <dl
          v-if="hasProgrammeFacts"
          class="flex w-full min-w-0 flex-wrap items-baseline text-[10px] leading-snug sm:text-[11px]"
        >
          <template v-for="(fact, index) in programmeFacts" :key="`programme-${fact.label}`">
            <span
              v-if="index > 0"
              class="mx-1.5 text-muted-foreground/40"
              aria-hidden="true"
            >|</span>
            <div class="inline-flex min-w-0 max-w-full items-baseline gap-1">
              <dt class="shrink-0 text-muted-foreground">
                {{ fact.label }}
              </dt>
              <dd class="min-w-0 font-bold wrap-break-word text-foreground">
                {{ fact.value }}
              </dd>
            </div>
          </template>
        </dl>

        <div
          v-if="hasApprenticeFacts"
          class="flex min-w-0 max-w-full flex-wrap items-baseline gap-x-2 gap-y-0.5 border-foreground/15 text-[10px] leading-snug sm:text-[11px]"
        >
          <span class="shrink-0 font-semibold tracking-wide text-primary uppercase">
            {{ $t('trans.apprentice') }}
          </span>
          <dl class="flex min-w-0 flex-wrap items-baseline">
            <template v-for="(fact, index) in apprenticeFacts" :key="`apprentice-${fact.label}`">
              <span
                v-if="index > 0"
                class="mx-1.5 text-muted-foreground/40"
                aria-hidden="true"
              >·</span>
              <div class="inline-flex min-w-0 max-w-full items-baseline gap-1">
                <dt class="shrink-0 text-muted-foreground">
                  {{ fact.label }}
                </dt>
                <dd class="min-w-0 font-bold wrap-break-word text-foreground">
                  {{ fact.value }}
                </dd>
              </div>
            </template>
          </dl>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
button:active {
  transform: scale(0.97);
}
</style>
