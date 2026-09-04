<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import BaseTag from '@/components/core/util/BaseTag.vue';
import StudentDisabilityIcon from '@/components/students/StudentDisabilityIcon.vue';
import { useDefaults } from '@/composables/core/useDefaults';
import { useInitials } from '@/composables/core/useInitials';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { applicationStatusVariant } from '@/lib/applicationStatusPresentation';
import { hasAbility } from '@/lib/permissions';
import { StudentHeader } from '@/types/students';
import { trans, trans_choice } from 'laravel-vue-i18n';

interface Props {
  data: StudentHeader | null;
}

const emit = defineEmits<{
  (event: 'edit-student-number'): void;
  (event: 'edit-status'): void;
}>();

interface HeaderFact {
  label: string;
  value: string;
}

const props = defineProps<Props>();

const yearSemesterDisplay = computed(() => {
  const calendar = props.data?.academicCalendar?.trim();
  const yearOption = props.data?.semester?.trim();

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

const applicationStatus = computed(() => props.data?.applicationStatus?.trim() || '');
const statusVariant = computed(() => applicationStatusVariant(applicationStatus.value));

const { getInitials } = useInitials();
const { defaultAvatarImage } = useDefaults();
const hasRealAvatar = computed(() => Boolean(props.data?.avatarUrl?.trim?.()));
const avatarSrc = computed(() => props.data?.avatarUrl?.trim?.() || defaultAvatarImage.value);
const canEditStudentNumber = computed(() => Boolean(props.data?.studentId) && hasAbility('change-student-number:students'));
const canEditStatus = computed(() => Boolean(props.data?.studentId) && hasAbility('change-student-status:students'));

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

const transferFacts = computed<HeaderFact[]>(() => {
  if (!props.data?.isTransferAtCurrentLevel) {
    return [];
  }

  const collegeName = props.data?.transferCollegeName?.trim();
  if (!collegeName) {
    return [];
  }

  return [
    {
      label: trans('trans.application_transfer_college_label'),
      value: collegeName,
    },
  ];
});

const sponsorName = computed(() => {
  if (!props.data?.isSponsoredThisYear) {
    return '';
  }

  return props.data?.sponsor?.trim() || '';
});

const hasProgrammeFacts = computed(() => programmeFacts.value.length > 0);
const hasApprenticeFacts = computed(() => apprenticeFacts.value.length > 0);
const hasTransferFacts = computed(() => transferFacts.value.length > 0);
const hasSponsoredFacts = computed(() => sponsorName.value !== '');
</script>

<template>
  <section class="w-full min-w-0 border-b border-border px-2 py-1.5 sm:px-3">
    <div class="flex w-full min-w-0 items-center gap-2 sm:gap-3">
      <Avatar class="size-11 shrink-0 self-center border border-border sm:size-12">
        <AvatarImage v-if="hasRealAvatar" :src="avatarSrc" :alt="data?.studentName" />
        <AvatarFallback class="bg-primary/10 text-[11px] font-bold text-primary sm:text-xs">
          {{ getInitials(data?.studentName) }}
        </AvatarFallback>
      </Avatar>

      <div class="flex min-w-0 flex-1 flex-col gap-1.5">
        <div class="flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-0.5">
          <h1 class="min-w-0 text-sm font-extrabold uppercase leading-tight tracking-tight wrap-break-word text-foreground sm:text-base">
            {{ data?.studentName }}
          </h1>
          <StudentDisabilityIcon :status="data?.disabilityStatus" />
          <span class="inline-flex shrink-0 items-center gap-0.5">
            <BaseTag
              v-if="applicationStatus"
              :title="applicationStatus"
              :variant="statusVariant"
              classes="cursor-default text-[10px] font-semibold leading-none py-px"
            />
            <button
              v-if="canEditStatus"
              type="button"
              class="inline-flex size-5 shrink-0 items-center justify-center rounded-full leading-none text-muted-foreground hover:bg-accent hover:text-foreground"
              :title="$t('students.change_status_action')"
              :aria-label="$t('students.change_status_action')"
              @click="emit('edit-status')"
            >
              <BaseIcon :name="IconName.edit" size="14" class="block" />
            </button>
          </span>
          <span class="inline-flex shrink-0 items-center gap-0.5">
            <span
              v-if="trackingBadge"
              class="shrink-0 rounded-full bg-muted px-1.5 py-px font-mono text-[10px] leading-none tracking-wide text-foreground"
            >
              {{ trackingBadge }}
            </span>
            <button
              v-if="canEditStudentNumber"
              type="button"
              class="inline-flex size-5 shrink-0 items-center justify-center rounded-full leading-none text-muted-foreground hover:bg-accent hover:text-foreground"
              :title="$t('students.change_student_number_action')"
              :aria-label="$t('students.change_student_number_action')"
              @click="emit('edit-student-number')"
            >
              <BaseIcon :name="IconName.edit" size="14" class="block" />
            </button>
          </span>
        </div>

        <p
          v-if="levelCourseDisplay"
          class="w-full min-w-0 text-[11px] font-semibold leading-tight wrap-break-word uppercase text-foreground/90 sm:text-xs"
        >
          {{ levelCourseDisplay }}
        </p>

        <div
          v-if="hasProgrammeFacts || hasApprenticeFacts || hasTransferFacts || hasSponsoredFacts"
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
            class="flex min-w-0 max-w-full flex-wrap items-center gap-x-1.5 gap-y-0.5 border-foreground/15 text-[10px] leading-snug sm:text-[11px]"
          >
            <BaseTag
              :title="$t('trans.apprentice')"
              :variant="ColorVariant.primary_outline"
              classes="cursor-default shrink-0 text-[9px] font-bold uppercase tracking-wide px-1.5 py-0 leading-tight sm:text-[10px]"
            />
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

          <div
            v-if="hasTransferFacts"
            class="flex min-w-0 max-w-full flex-wrap items-center gap-x-1.5 gap-y-0.5 border-foreground/15 text-[10px] leading-snug sm:text-[11px]"
          >
            <BaseTag
              :title="$t('trans.transfer')"
              :variant="ColorVariant.primary_outline"
              classes="cursor-default shrink-0 text-[9px] font-bold uppercase tracking-wide px-1.5 py-0 leading-tight sm:text-[10px]"
            />
            <dl class="flex min-w-0 flex-wrap items-baseline">
              <template v-for="(fact, index) in transferFacts" :key="`transfer-${fact.label}`">
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

          <div
            v-if="hasSponsoredFacts"
            class="flex min-w-0 max-w-full flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[10px] leading-snug sm:text-[11px]"
          >
            <BaseTag
              :title="$t('trans.sponsored_by')"
              :variant="ColorVariant.primary_outline"
              classes="cursor-default shrink-0 text-[9px] font-bold uppercase tracking-wide px-1.5 py-0 leading-tight sm:text-[10px]"
            />
            <span class="min-w-0 font-bold wrap-break-word text-foreground">
              {{ sponsorName }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="$slots.actions" class="shrink-0 self-start">
        <slot name="actions" />
      </div>
    </div>
  </section>
</template>

<style scoped>
button:active {
  transform: scale(0.97);
}
</style>
