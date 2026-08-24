<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import IdNumber from '@/components/core/form/text/IdNumber.vue';
import RegistrationIntentSummary from '@/components/portal/RegistrationIntentSummary.vue';
import RegistrationStepper from '@/components/portal/RegistrationStepper.vue';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { StepperVariant } from '@/components/portal/RegistrationStepper.vue';
import { useRegistrationStepNavigation } from '@/composables/students/useRegistrationStepNavigation';
import RegistrationBrandHeader from '@/pages/portal/guest/components/RegistrationBrandHeader.vue';
import RegistrationGuide from '@/pages/portal/guest/RegistrationGuide.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type ModeNode = { id: number; name: string; available: boolean };
type CourseNode = {
    id: number;
    departmentCourseId: number;
    name: string;
    available: boolean;
    modes: ModeNode[];
};
type LevelNode = {
    id: number;
    levelId: number;
    name: string;
    available: boolean;
    courses: CourseNode[];
};
type DepartmentNode = {
    id: number;
    name: string;
    available: boolean;
    levels: LevelNode[];
};

type ProgrammeTree = {
    available: boolean;
    departments: DepartmentNode[];
    unavailableReason: string | null;
};

type IntentSummary = {
    track?: string | null;
    trackLabel?: string | null;
    continuousFocus?: string | null;
    levelName?: string | null;
    intakeName?: string | null;
};

type OjetIdentityType = 'zimbabwean' | 'international';

const props = withDefaults(
    defineProps<{
        programmes: ProgrammeTree;
        applicationTrack?: string | null;
        applicationTrackLabel?: string | null;
        continuousFocus?: string | null;
        selectedLevelId?: number | null;
        selectedLevelName?: string | null;
        intentSummary?: IntentSummary | null;
        stepperVariant?: StepperVariant;
        requiresFee?: boolean;
        currentSelection?: {
            departmentId?: number | null;
            departmentLevelId?: number | null;
            courseId?: number | null;
            modeOfStudyId?: number | null;
        };
        ojetIdentity?: {
            identityType?: OjetIdentityType | null;
            idNumber?: string | null;
            passportNumber?: string | null;
        };
    }>(),
    {
        stepperVariant: 'regular',
        requiresFee: false,
    },
);

const page = usePage();
const { navigateToRegistrationStep } = useRegistrationStepNavigation();

const isSdpExpress = computed(() => props.stepperVariant === 'sdp');

const departmentId = ref<number | null>(props.currentSelection?.departmentId ?? null);
const departmentLevelId = ref<number | null>(props.currentSelection?.departmentLevelId ?? null);
const courseId = ref<number | null>(props.currentSelection?.courseId ?? null);
const modeOfStudyId = ref<number | null>(props.currentSelection?.modeOfStudyId ?? null);
const identityType = ref<OjetIdentityType>(
    props.ojetIdentity?.identityType === 'international' ? 'international' : 'zimbabwean',
);
const idNumber = ref(props.ojetIdentity?.idNumber ?? '');
const passportNumber = ref(props.ojetIdentity?.passportNumber ?? '');
const submitting = ref(false);

const departments = computed(() => props.programmes?.departments ?? []);

const selectedDepartment = computed(() => departments.value.find((d) => d.id === departmentId.value) ?? null);

const levels = computed(() => selectedDepartment.value?.levels ?? []);

const selectedLevel = computed(() => levels.value.find((l) => l.id === departmentLevelId.value) ?? null);

const courses = computed(() => selectedLevel.value?.courses ?? []);

const selectedCourse = computed(
    () =>
        courses.value.find((c) => c.departmentCourseId === courseId.value || c.id === courseId.value) ?? null,
);

const modes = computed(() => selectedCourse.value?.modes ?? []);

const selectedMode = computed(() => modes.value.find((m) => m.id === modeOfStudyId.value) ?? null);

const isOjetMode = computed(() => {
    const name = selectedMode.value?.name ?? '';
    return name.toLowerCase() === 'ojet';
});

const formErrors = computed(() => (page.props.errors ?? {}) as Record<string, string | undefined>);

const identityError = computed(() =>
    identityType.value === 'international' ? formErrors.value.passport_number : formErrors.value.id_number,
);

const identityFilled = computed(() => {
    if (!isOjetMode.value) {
        return true;
    }

    if (identityType.value === 'international') {
        return passportNumber.value.trim().length >= 5;
    }

    return idNumber.value.trim().length > 0;
});

const canContinue = computed(
    () =>
        props.programmes.available &&
        departmentId.value !== null &&
        departmentLevelId.value !== null &&
        courseId.value !== null &&
        modeOfStudyId.value !== null &&
        identityFilled.value,
);

watch(departmentId, () => {
    departmentLevelId.value = null;
    courseId.value = null;
    modeOfStudyId.value = null;
});

watch(departmentLevelId, () => {
    courseId.value = null;
    modeOfStudyId.value = null;
});

watch(courseId, () => {
    modeOfStudyId.value = null;
});

watch(
    modes,
    (nextModes) => {
        if (nextModes.length === 1) {
            modeOfStudyId.value = nextModes[0].id;
        }
    },
    { immediate: true },
);

const submit = () => {
    if (!canContinue.value) {
        return;
    }

    submitting.value = true;

    const payload: Record<string, string | number | null> = {
        department_id: departmentId.value,
        department_level_id: departmentLevelId.value,
        course_id: courseId.value,
        mode_of_study_id: modeOfStudyId.value,
    };

    if (isOjetMode.value) {
        payload.identity_type = identityType.value;
        if (identityType.value === 'international') {
            payload.passport_number = passportNumber.value;
        } else {
            payload.id_number = idNumber.value;
        }
    }

    router.post(route('portal.register.select-programme'), payload, {
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <Head :title="$t('trans.registration_programme_finder_title')" />
    <div class="min-h-svh bg-background">
        <div class="flex min-h-svh flex-col lg:flex-row">
            <div class="flex w-full flex-1 flex-col p-4 pt-2 sm:p-6 md:pt-6 lg:w-[62%] lg:min-w-0 lg:p-10">
                <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
                    <RegistrationBrandHeader />
                    <RegistrationStepper
                        active-path="zimbabwean"
                        highlighted-step="choose-programme"
                        :stepper-variant="stepperVariant"
                        :requires-fee="requiresFee"
                        @navigate="navigateToRegistrationStep"
                    />
                    <RegistrationIntentSummary :summary="intentSummary" />

                    <div class="rounded-2xl border border-border bg-card p-5 text-card-foreground shadow-md sm:p-6">
                        <div class="mb-4 text-center">
                            <h1 class="text-lg font-semibold text-foreground">
                                {{ $t('trans.registration_programme_finder_title') }}
                            </h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ $t('trans.registration_programme_finder_description') }}
                            </p>
                            <p v-if="selectedLevelName" class="mt-1 text-xs font-medium text-primary">
                                {{ selectedLevelName }}
                            </p>
                        </div>

                        <BaseAlert
                            v-if="!programmes.available"
                            class="mb-4"
                            :title="$t('trans.portal_no_levels_available')"
                            :description="programmes.unavailableReason ?? $t('trans.registration_programme_none_available', { level: selectedLevelName ?? '' })"
                            :type="TypeVariant.warning"
                        />

                        <div v-else class="space-y-3">
                            <label class="block space-y-1">
                                <span class="text-xs font-medium text-foreground">{{ $tChoice('trans.department', 1) }}</span>
                                <select
                                    v-model.number="departmentId"
                                    class="h-9 w-full rounded-lg border border-border bg-background px-2.5 text-sm"
                                >
                                    <option :value="null">{{ $t('trans.select_one') }}</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </option>
                                </select>
                            </label>

                            <label class="block space-y-1">
                                <span class="text-xs font-medium text-foreground">{{ $t('trans.programme_level') }}</span>
                                <select
                                    v-model.number="departmentLevelId"
                                    class="h-9 w-full rounded-lg border border-border bg-background px-2.5 text-sm disabled:opacity-50"
                                    :disabled="!departmentId"
                                >
                                    <option :value="null">{{ $t('trans.select_one') }}</option>
                                    <option v-for="level in levels" :key="level.id" :value="level.id">
                                        {{ level.name }}
                                    </option>
                                </select>
                            </label>

                            <label class="block space-y-1">
                                <span class="text-xs font-medium text-foreground">{{ $tChoice('trans.course', 1) }}</span>
                                <select
                                    v-model.number="courseId"
                                    class="h-9 w-full rounded-lg border border-border bg-background px-2.5 text-sm disabled:opacity-50"
                                    :disabled="!departmentLevelId"
                                >
                                    <option :value="null">{{ $t('trans.select_one') }}</option>
                                    <option
                                        v-for="course in courses"
                                        :key="course.departmentCourseId"
                                        :value="course.departmentCourseId"
                                    >
                                        {{ course.name }}
                                    </option>
                                </select>
                            </label>

                            <label class="block space-y-1">
                                <span class="text-xs font-medium text-foreground">{{ $tChoice('trans.mode_of_study', 1) }}</span>
                                <select
                                    v-model.number="modeOfStudyId"
                                    class="h-9 w-full rounded-lg border border-border bg-background px-2.5 text-sm disabled:opacity-50"
                                    :disabled="!courseId"
                                >
                                    <option :value="null">{{ $t('trans.select_one') }}</option>
                                    <option v-for="mode in modes" :key="mode.id" :value="mode.id">
                                        {{ mode.name }}
                                    </option>
                                </select>
                            </label>

                            <div v-if="isOjetMode" class="space-y-3 rounded-xl border border-border bg-muted/30 p-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-foreground">
                                        {{ $t('trans.ojet_former_student_identity_title') }}
                                    </p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ $t('trans.ojet_former_student_identity_description') }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg border px-2 py-2 text-xs font-medium uppercase"
                                        :class="
                                            identityType === 'zimbabwean'
                                                ? 'border-primary bg-primary/5 text-primary'
                                                : 'border-border text-muted-foreground'
                                        "
                                        @click="identityType = 'zimbabwean'"
                                    >
                                        {{ $t('trans.id_number') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border px-2 py-2 text-xs font-medium uppercase"
                                        :class="
                                            identityType === 'international'
                                                ? 'border-primary bg-primary/5 text-primary'
                                                : 'border-border text-muted-foreground'
                                        "
                                        @click="identityType = 'international'"
                                    >
                                        {{ $t('trans.passport_number') }}
                                    </button>
                                </div>

                                <IdNumber
                                    v-if="identityType === 'zimbabwean'"
                                    :model-value="idNumber"
                                    :placeholder="$t('trans.enrollment_enter_national_id')"
                                    :vertical-layout="false"
                                    :label-uppercase="true"
                                    :is-required="true"
                                    :error="identityError"
                                    @update:model-value="idNumber = $event"
                                />
                                <BaseInput
                                    v-else
                                    input-id="ojet_passport_number"
                                    :label="$t('trans.passport_number')"
                                    :model-value="passportNumber"
                                    :placeholder="$t('trans.enrollment_enter_passport')"
                                    :vertical-layout="false"
                                    :label-uppercase="true"
                                    :is-required="true"
                                    :error="identityError"
                                    @update:model-value="passportNumber = $event"
                                />

                                <BaseAlert
                                    v-if="identityError"
                                    :type="TypeVariant.danger"
                                    :title="$t('trans.ojet_former_student_identity_title')"
                                    :description="identityError"
                                />
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <Link
                                v-if="!isSdpExpress"
                                :href="route('portal.register.level')"
                                class="text-center text-sm font-medium text-primary underline-offset-4 hover:underline sm:text-left"
                            >
                                {{ $t('trans.portal_change_level') }}
                            </Link>
                            <Link
                                v-else
                                :href="route('portal.register.track')"
                                class="text-center text-sm font-medium text-primary underline-offset-4 hover:underline sm:text-left"
                            >
                                {{ $t('trans.registration_step_path') }}
                            </Link>
                            <BaseButton
                                type="button"
                                :variant="ColorVariant.primary"
                                :disabled="!canContinue || submitting"
                                classes="min-h-10 rounded-xl"
                                @click="submit"
                            >
                                {{ $t('trans.registration_programme_continue') }}
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </div>
            <RegistrationGuide
                active-path="zimbabwean"
                highlighted-step="choose-programme"
                :stepper-variant="stepperVariant"
                :requires-fee="requiresFee"
            />
        </div>
    </div>
</template>
