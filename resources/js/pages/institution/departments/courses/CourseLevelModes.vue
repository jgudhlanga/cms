<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { canReassignProgramme, useReassignProgramme } from '@/composables/students/useReassignProgramme';
import ReassignProgrammeDialog from '@/components/students/programme/ReassignProgrammeDialog.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { getIdParams } from '@/lib/utils';
import { AuthObject } from '@/types/data-pagination';
import { CourseLevelMode, DepartmentCourse, DepartmentCourseModeParams, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted } from 'vue';
import CourseHero from './partials/CourseHero.vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    departmentCourse: DepartmentCourse;
    departmentLevels: DepartmentLevel[];
    courseLevelModes: CourseLevelMode[];
    modesOfStudy: ModeOfStudy[];
    linkedUsage?: Record<string, { applications: number; enrolments: number; modeIds: number[] }>;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { institutionDepartment, departmentCourse, departmentLevels, courseLevelModes, modesOfStudy } = props;
const linkedUsage = computed(() => props.linkedUsage ?? {});

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', transChoiceKeyIndex: 2, href: route('institution-departments.index') },
    {
        title: institutionDepartment?.attributes.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? '')),
    },
    {
        title: departmentCourse?.attributes.course,
        href: route('department-courses.show', getIdParams(departmentCourse?.id?.toString() ?? '')),
    },
    { title: trans_choice('general.mode', 2) },
];
const { navigateTo } = useUtils();

const form = useForm<DepartmentCourseModeParams>({
    department_course_id: String(departmentCourse?.id),
    mode_ids: {} as Record<string, (string | number)[]>,
});

const { saveCourseLevelModes } = useDepartmentCourses();
const canMoveLinked = computed(() => canReassignProgramme());
const { form: reassignForm, records, loadingRecords, selectedApplicationIds, hydratingDefaults, openReassignProgrammeDialog, submitReassignProgramme } =
    useReassignProgramme();

const usageForLevel = (levelId: string | number | undefined) => linkedUsage.value[String(levelId)] ?? { applications: 0, enrolments: 0, modeIds: [] };

const openMoveLinked = (levelId: string | number | undefined) => {
    const usage = usageForLevel(levelId);
    void openReassignProgrammeDialog({
        source: {
            departmentCourseId: departmentCourse?.id,
            departmentLevelId: levelId,
            modeOfStudyIds: usage.modeIds,
        },
    });
};

const buildModeMatrix = () => {
    const linkedLevelIds = new Set(departmentLevels.map((level) => String(level.id)));
    const matrix: Record<string, (string | number)[]> = {};
    courseLevelModes.forEach((clm) => {
        const levelId = String(clm.attributes.departmentLevelId);
        if (!linkedLevelIds.has(levelId)) {
            return;
        }
        matrix[levelId] = clm.relationships.modes.map((m) => m.id).filter((id): id is string => id !== undefined);
    });
    departmentLevels.forEach((level) => {
        const id = String(level.id);
        if (!matrix[id]) {
            matrix[id] = [];
        }
    });
    return matrix;
};

onMounted(() => {
    form.mode_ids = buildModeMatrix();
});

const isModeSelected = (levelId: string | number | undefined, modeId: string | number | undefined): boolean =>
    (form.mode_ids[String(levelId)] ?? []).some((id) => String(id) === String(modeId));

const toggleMode = (levelId: string | number | undefined, modeId: string | number | undefined): void => {
    const key = String(levelId);
    const current = form.mode_ids[key] ?? [];
    form.mode_ids = {
        ...form.mode_ids,
        [key]: isModeSelected(levelId, modeId) ? current.filter((id) => String(id) !== String(modeId)) : [...current, modeId as string | number],
    };
};

const selectedCountForLevel = (levelId: string | number | undefined): number => (form.mode_ids[String(levelId)] ?? []).length;

const levelsConfiguredCount = computed(() => departmentLevels.filter((level) => selectedCountForLevel(level.id) > 0).length);

const modesInUseCount = computed(() => {
    const ids = new Set<string>();
    Object.values(form.mode_ids).forEach((modeIds) => modeIds.forEach((id) => ids.add(String(id))));
    return ids.size;
});

const heroMetrics = computed(() => [
    {
        labelKey: 'ui_levels_configured',
        icon: IconName.school,
        value: `${levelsConfiguredCount.value}/${departmentLevels.length}`,
        valueClass: 'text-indigo-600',
    },
    {
        labelKey: 'ui_modes_in_use',
        icon: IconName.briefcase,
        value: `${modesInUseCount.value}/${modesOfStudy.length}`,
        valueClass: 'text-emerald-600',
    },
]);

const updateCourse = () => {
    saveCourseLevelModes(departmentCourse?.id?.toString() ?? '', form, institutionDepartment?.attributes?.departmentId.toString() ?? '');
};
</script>

<template>
    <Head :title="`${trans_choice('general.mode', 2)} — ${departmentCourse?.attributes.course}`" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('institution-departments.show', getIdParams(institutionDepartment?.attributes?.departmentId.toString() ?? ''))"
    >
        <form @submit.prevent="() => updateCourse()" class="flex flex-col space-y-4">
            <CourseHero :course="departmentCourse" :department="institutionDepartment" :metrics="heroMetrics" />

            <section class="border-border bg-card overflow-hidden rounded-2xl border">
                <header class="border-border bg-muted/30 flex flex-wrap items-center gap-2.5 border-b px-4 py-3 sm:px-5">
                    <span class="bg-primary/10 text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
                        <BaseIcon :name="IconName.settings" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-foreground truncate text-sm font-bold tracking-tight">
                            {{ `${$t('trans.config')} ${$tChoice('trans.course', 1)} ${$tChoice('general.mode', 2)}` }}
                        </h2>
                        <p class="text-muted-foreground mt-0.5 text-xs leading-relaxed">{{ $t('trans.ui_mode_config_description') }}</p>
                    </div>
                </header>

                <div class="px-4 py-3 sm:px-5">
                    <template v-if="departmentLevels && departmentLevels.length > 0 && modesOfStudy && modesOfStudy.length > 0">
                        <div class="divide-border divide-y">
                            <div
                                v-for="level in departmentLevels"
                                :key="`level_key_${level['id']}`"
                                class="flex flex-col gap-2 py-3 first:pt-0 last:pb-0"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-foreground text-sm font-semibold">{{ level.attributes.level }}</span>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            v-if="usageForLevel(level.id).applications > 0 || usageForLevel(level.id).enrolments > 0"
                                            class="text-[11px] font-medium text-amber-700"
                                        >
                                            {{
                                                trans('students.reassign_programme_linked', {
                                                    applications: usageForLevel(level.id).applications,
                                                    enrolments: usageForLevel(level.id).enrolments,
                                                })
                                            }}
                                        </span>
                                        <BaseButton
                                            v-if="canMoveLinked && (usageForLevel(level.id).applications > 0 || usageForLevel(level.id).enrolments > 0)"
                                            type="button"
                                            :size="ButtonSize.xs"
                                            :variant="ColorVariant.primary_outline"
                                            classes="rounded-full"
                                            @click="openMoveLinked(level.id)"
                                        >
                                            {{ $t('students.reassign_programme_move_linked') }}
                                        </BaseButton>
                                        <span
                                            class="text-[11px] font-medium"
                                            :class="selectedCountForLevel(level.id) > 0 ? 'text-emerald-600' : 'text-muted-foreground'"
                                        >
                                            {{
                                                selectedCountForLevel(level.id) > 0
                                                    ? `${selectedCountForLevel(level.id)} ${$t('trans.ui_modes_selected')}`
                                                    : $t('trans.ui_no_modes_selected')
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="mode in modesOfStudy"
                                        :key="`course_mode_${level.id}_${mode.id}`"
                                        type="button"
                                        class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                        :class="
                                            isModeSelected(level.id, mode.id)
                                                ? 'border-primary bg-primary/10 text-primary'
                                                : 'border-border text-muted-foreground hover:bg-muted/50'
                                        "
                                        @click="toggleMode(level.id, mode.id)"
                                    >
                                        {{ mode.attributes.name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <Empty />
                    </template>
                </div>
            </section>

            <div class="flex items-center justify-end gap-3">
                <BaseButton
                    type="button"
                    :size="ButtonSize.sm"
                    :variant="ColorVariant.shade"
                    @click="
                        () =>
                            navigateTo(
                                route('institution-departments.show', getIdParams(institutionDepartment?.attributes?.departmentId.toString() ?? '')),
                            )
                    "
                >
                    {{ $t('trans.back') }}
                </BaseButton>
                <BaseButton :size="ButtonSize.sm" :processing="form.processing" :disabled="form.processing">
                    {{ $t('trans.save') }}
                </BaseButton>
            </div>
        </form>
        <ReassignProgrammeDialog
            v-if="canMoveLinked"
            :form="reassignForm"
            :records="records"
            :loading-records="loadingRecords"
            :hydrating-defaults="hydratingDefaults"
            v-model:selected-application-ids="selectedApplicationIds"
            :on-form-action="submitReassignProgramme"
        />
    </PageContainer>
</template>
