<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseCard from '@/components/core/card/BaseCard.vue';
import InputError from '@/components/core/form/InputError.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { useErrorDialog } from '@/composables/core/useErrorDialog';
import { TypeVariant } from '@/enums/type-variants';
import { clearFormErrors } from '@/lib/forms';
import { errorAlert } from '@/lib/alerts';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Enrolment } from '@/types/enrolments';
import { CreateApplicationParams, ProgramParams } from '@/types/portal';
import { Level } from '@/types/institution';
import { InertiaForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';

interface Props {
    form: InertiaForm<CreateApplicationParams | ProgramParams>;
    application?: Enrolment | null;
    selectedLevelName?: string | null;
    hasPaidApplicationFee?: boolean | null;
    levelsWithPayment?: Level[];
    bare?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    bare: false,
});

const emit = defineEmits<{
    levelAvailabilityChange: [available: boolean];
}>();

const { form, application } = props;
const { isItTrue, navigateTo } = useUtils();

const isEditing = Number(String(application?.id)) > 0;

const store = isEditing ? useUpdateProgramFormStore() : useCreateApplicationFormStore();
let skipFirstDepartmentWatch = isEditing;
let skipFirstLevelWatch = isEditing;

const { department, level, course, modeOfStudy } = storeToRefs(store);

const { listLevelRequirements, levelRequirements, isLoading: levelRequirementsLoading } = useDepartmentLevels(isEditing);
const { listCourseRequirements, courseRequirements, isLoading: courseRequirementsLoading } = useDepartmentCourses(isEditing);

const previousDepartment = ref(department.value);
const previousLevel = ref(level.value);
const feeWarningVisible = ref(false);
const selectedLevelUnavailable = ref(false);
const hasShownLevelUnavailableToast = ref(false);

const normalizeLevelName = (name?: string | null) => name?.trim().toLowerCase() ?? '';

const clearStaleLevelSelection = () => {
    if (!normalizeLevelName(props.selectedLevelName) || !level.value?.label) {
        return;
    }

    if (normalizeLevelName(level.value.label) !== normalizeLevelName(props.selectedLevelName)) {
        level.value = null;
    }
};

watch(() => props.selectedLevelName, clearStaleLevelSelection, { immediate: true });

const onAllowedLevelStatus = ({ available }: { available: boolean }) => {
    const wasUnavailable = selectedLevelUnavailable.value;
    selectedLevelUnavailable.value = !available;

    if (!available && !wasUnavailable && !hasShownLevelUnavailableToast.value) {
        hasShownLevelUnavailableToast.value = true;
        errorAlert(trans('trans.portal_selected_level_not_active_toast'));
    }

    if (available) {
        hasShownLevelUnavailableToast.value = false;
    }

    emit('levelAvailabilityChange', available);
};

const requirements = computed(() => {
    return courseRequirements.value && Number(String(courseRequirements.value?.id)) > 0 ? courseRequirements.value : levelRequirements.value;
});

const resetProgrammeSelections = () => {
    level.value = null;
    course.value = null;
    levelRequirements.value = null;
    courseRequirements.value = null;
    modeOfStudy.value = null;
    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
    clearFormErrors(form, 'modeOfStudy');
};

const confirmCascadeReset = async (messageKey: string): Promise<boolean> => {
    if (!course.value && !modeOfStudy.value) {
        return true;
    }
    return useErrorDialog().open({
        title: trans('trans.confirm_change'),
        message: trans(messageKey),
        confirmText: trans('trans.continue'),
        cancelText: trans('trans.cancel'),
        showCancelBtn: true,
    });
};

watch(department, async (newVal, oldVal) => {
    if (skipFirstDepartmentWatch) {
        skipFirstDepartmentWatch = false;
        previousDepartment.value = newVal;
        return;
    }
    if (oldVal && newVal?.value !== oldVal?.value) {
        const confirmed = await confirmCascadeReset('trans.programme_department_change_warning');
        if (!confirmed) {
            department.value = previousDepartment.value;
            return;
        }
    }
    previousDepartment.value = newVal;
    resetProgrammeSelections();
});

watch(level, async (newVal, oldVal) => {
    if (skipFirstLevelWatch) {
        skipFirstLevelWatch = false;
        previousLevel.value = newVal;
        return;
    }

    if (oldVal && newVal?.value !== oldVal?.value) {
        const selectedInstitutionLevel = props.levelsWithPayment?.find(
            (lv: Level) => Number(lv.id) === Number(newVal?.relationshipOneValue),
        );
        if (
            selectedInstitutionLevel &&
            isItTrue(selectedInstitutionLevel.attributes.hasApplicationFeePayment) &&
            !isItTrue(props.hasPaidApplicationFee)
        ) {
            feeWarningVisible.value = true;
            level.value = previousLevel.value;
            return;
        }

        const confirmed = await confirmCascadeReset('trans.programme_level_change_warning');
        if (!confirmed) {
            level.value = previousLevel.value;
            return;
        }
    }

    previousLevel.value = newVal;
    course.value = null;
    levelRequirements.value = null;
    courseRequirements.value = null;
    modeOfStudy.value = null;
    clearFormErrors(form, 'level');
    clearFormErrors(form, 'course');
});

watch(course, async () => {
    levelRequirements.value = null;
    courseRequirements.value = null;
    if (skipFirstLevelWatch) {
        skipFirstLevelWatch = false;
        return;
    }
    if (Number(course.value?.value) > 0) {
        if (course.value?.triggerActionValue) {
            await listCourseRequirements(level.value?.value?.toString() ?? '', course.value?.value?.toString() ?? '');
            if (Number(requirements.value?.attributes?.departmentLeveId) !== Number(level.value?.value)) {
                await listLevelRequirements(level.value?.value?.toString() ?? '');
            }
        } else {
            if (Number(level.value?.value) > 0) await listLevelRequirements(level.value?.value?.toString() ?? '');
        }
    }
    clearFormErrors(form, 'course');
});

const goToPayment = () => {
    navigateTo(route('portal.application.fee-payment'));
};
</script>

<template>
    <component
        :is="bare ? 'div' : BaseCard"
        :class="bare ? 'space-y-6' : undefined"
        :title="bare ? undefined : $t('trans.programs')"
        :description="bare ? undefined : $t('trans.programme_step_description')"
    >
        <p v-if="selectedLevelName" class="mb-4 text-sm text-muted-foreground">
            {{ $t('trans.programme_level_context', { level: selectedLevelName }) }}
        </p>

        <BaseAlert
            v-if="feeWarningVisible"
            class="mb-4"
            :title="$t('trans.application_fee_required_title')"
            :description="$t('trans.application_fee_required_description')"
            :type="TypeVariant.warning"
        >
            <button type="button" class="mt-2 text-sm font-medium text-primary underline" @click="goToPayment">
                {{ $t('trans.go_to_payment') }}
            </button>
        </BaseAlert>

        <BaseAlert
            v-if="selectedLevelUnavailable"
            class="mb-4"
            :title="$t('trans.portal_selected_level_not_active_title')"
            :description="$t('trans.portal_selected_level_not_active_description', { level: selectedLevelName ?? '' })"
            :type="TypeVariant.danger"
        />

        <div class="grid grid-cols-1 gap-4">
            <InstitutionDepartmentComboSelect
                :form="form"
                v-model="department"
                data-field="department"
                :error="form.errors.department"
                :is-required="true"
            />
            <DepartmentLevelComboSelect
                :form="form"
                :institution-department-id="department?.value?.toString() ?? ''"
                :selected-level-name="selectedLevelName"
                v-model="level"
                data-field="level"
                :error="form.errors.level"
                :is-required="true"
                :label="$t('trans.programme_level')"
                @allowed-level-status="onAllowedLevelStatus"
            />
            <DepartmentCourseComboSelect
                :form="form"
                :department-level-id="level?.value?.toString() ?? ''"
                v-model="course"
                data-field="course"
                :error="form.errors.course"
                :is-required="true"
            />
            <ModeOfStudyComboSelect
                :department-course-id="course?.value?.toString() ?? ''"
                :department-level-id="level?.value?.toString() ?? ''"
                :form="form"
                v-model="modeOfStudy"
                data-field="modeOfStudy"
                :error="form.errors.modeOfStudy"
                :is-required="true"
            />
        </div>
        <div class="my-4 flex w-full flex-col">
            <template v-if="levelRequirementsLoading || courseRequirementsLoading">
                <SpinnerComponent class="flex w-full items-center justify-center" />
            </template>
            <template v-else>
                <template v-if="Number(String(requirements?.id)) > 0">
                    <template v-if="isItTrue(requirements?.attributes?.isOLevelRequired)">
                        <OLevelRequirements :application="application" :requirements="requirements" />
                        <InputError v-if="form.errors.o_level" :message="form.errors.o_level" />
                    </template>
                    <template v-if="Number(String(requirements?.attributes?.requiredLevelId)) > 0">
                        <LevelRequirements :requirements="requirements" :application="application" />
                        <InputError v-if="form.errors.required_level_completed" :message="form.errors.required_level_completed" />
                    </template>
                    <template v-if="isItTrue(requirements?.attributes?.onlyReadWriteRequired)">
                        <SDPRequirements :application="application" />
                        <InputError v-if="form.errors.read_write_acknowledged" :message="form.errors.read_write_acknowledged" />
                    </template>
                </template>
            </template>
        </div>
    </component>
</template>
