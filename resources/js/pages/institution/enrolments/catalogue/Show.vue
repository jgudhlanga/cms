<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import BaseSwitch from '@/components/core/form/radio/BaseSwitch.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DepartmentColorSwatch from '@/components/institution/DepartmentColorSwatch.vue';
import InstitutionDepartmentNameCell from '@/components/institution/InstitutionDepartmentNameCell.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { cn } from '@/lib/utils';
import EnrolmentSetupTabs from '@/pages/institution/enrolments/partials/EnrolmentSetupTabs.vue';
import type { Link } from '@/types/ui';
import { Head, Link as InertiaLink, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

interface ModeOption {
    id: number;
    name: string;
}

interface LevelOption {
    id: number;
    name: string;
}

interface CourseOption {
    id: number;
    name: string;
    levels: LevelOption[];
}

interface OfferingCourse {
    department_course_id: number;
    mode_of_study_ids: number[];
}

interface OfferingLevel {
    department_level_id: number;
    courses: OfferingCourse[];
}

interface NavDepartment {
    id: number;
    name: string;
    departmentCode: string;
    colorCode?: string | null;
    enabled: boolean;
}

interface Props {
    department: {
        id: number;
        name: string;
        departmentCode: string;
        colorCode?: string | null;
    };
    offering: {
        enabled: boolean;
        has_apprentice_programmes: boolean;
        levels: OfferingLevel[];
    };
    availableCourses: CourseOption[];
    modesOfStudy: ModeOption[];
    navigationDepartments: NavDepartment[];
}

const props = defineProps<Props>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'institution_setup', href: route('institution.setup') },
    { title: 'Enrolment setup', href: route('application-offerings.index') },
    { title: props.department.name },
];

const form = useForm({
    enabled: props.offering.enabled,
    has_apprentice_programmes: props.offering.has_apprentice_programmes,
    levels: [] as OfferingLevel[],
});

const selectedCourseIds = reactive<number[]>([]);
const selectedLevelIdsByCourse = reactive<Record<number, number[]>>({});
const modeIdsByPair = reactive<Record<string, number[]>>({});

const pairKey = (courseId: number, levelId: number): string => `${courseId}:${levelId}`;

const ensureLevelBucket = (courseId: number) => {
    if (!selectedLevelIdsByCourse[courseId]) {
        selectedLevelIdsByCourse[courseId] = [];
    }
};

const ensureModeBucket = (courseId: number, levelId: number) => {
    const key = pairKey(courseId, levelId);
    if (!modeIdsByPair[key]) {
        modeIdsByPair[key] = [];
    }
};

const isCourseSelected = (courseId: number): boolean => {
    if (!selectedCourseIds.includes(courseId)) {
        return false;
    }
    ensureLevelBucket(courseId);
    return true;
};

const isLevelSelected = (courseId: number, levelId: number): boolean => {
    if (!(selectedLevelIdsByCourse[courseId] ?? []).includes(levelId)) {
        return false;
    }
    ensureModeBucket(courseId, levelId);
    return true;
};

const hydrateFromOffering = () => {
    selectedCourseIds.splice(0, selectedCourseIds.length);
    Object.keys(selectedLevelIdsByCourse).forEach((key) => delete selectedLevelIdsByCourse[Number(key)]);
    Object.keys(modeIdsByPair).forEach((key) => delete modeIdsByPair[key]);

    const courseIds = new Set<number>();

    props.offering.levels.forEach((level) => {
        level.courses.forEach((course) => {
            courseIds.add(course.department_course_id);
            if (!selectedLevelIdsByCourse[course.department_course_id]) {
                selectedLevelIdsByCourse[course.department_course_id] = [];
            }
            if (!selectedLevelIdsByCourse[course.department_course_id].includes(level.department_level_id)) {
                selectedLevelIdsByCourse[course.department_course_id].push(level.department_level_id);
            }
            modeIdsByPair[pairKey(course.department_course_id, level.department_level_id)] = [
                ...course.mode_of_study_ids,
            ];
        });
    });

    selectedCourseIds.push(...courseIds);
};

hydrateFromOffering();

watch(
    () => form.enabled,
    (enabled) => {
        if (!enabled) {
            selectedCourseIds.splice(0, selectedCourseIds.length);
            Object.keys(selectedLevelIdsByCourse).forEach((key) => delete selectedLevelIdsByCourse[Number(key)]);
            Object.keys(modeIdsByPair).forEach((key) => delete modeIdsByPair[key]);
        }
    },
);

watch(
    selectedCourseIds,
    (courseIds) => {
        courseIds.forEach((courseId) => {
            if (!selectedLevelIdsByCourse[courseId]) {
                selectedLevelIdsByCourse[courseId] = [];
            }
        });
        Object.keys(selectedLevelIdsByCourse).forEach((key) => {
            if (!courseIds.includes(Number(key))) {
                delete selectedLevelIdsByCourse[Number(key)];
            }
        });
    },
    { deep: true },
);

watch(
    selectedLevelIdsByCourse,
    (byCourse) => {
        Object.entries(byCourse).forEach(([courseId, levelIds]) => {
            levelIds.forEach((levelId) => ensureModeBucket(Number(courseId), levelId));
        });
    },
    { deep: true },
);

const buildPayload = (): OfferingLevel[] => {
    const byLevel = new Map<number, OfferingCourse[]>();

    selectedCourseIds.forEach((courseId) => {
        (selectedLevelIdsByCourse[courseId] ?? []).forEach((levelId) => {
            const courses = byLevel.get(levelId) ?? [];
            courses.push({
                department_course_id: courseId,
                mode_of_study_ids: modeIdsByPair[pairKey(courseId, levelId)] ?? [],
            });
            byLevel.set(levelId, courses);
        });
    });

    return Array.from(byLevel.entries()).map(([department_level_id, courses]) => ({
        department_level_id,
        courses,
    }));
};

const save = () => {
    form.levels = buildPayload();
    form.put(route('application-offerings.update', props.department.id), {
        preserveScroll: true,
    });
};

const flashSuccess = computed(() => (usePage().props.flash as { success?: string } | undefined)?.success);

const navSearch = reactive({ query: '' });

const filteredNavDepartments = computed(() => {
    const needle = navSearch.query.trim().toLowerCase();
    const rows = props.navigationDepartments.filter((department) => {
        if (needle === '') {
            return true;
        }

        return (
            department.name.toLowerCase().includes(needle)
            || department.departmentCode.toLowerCase().includes(needle)
        );
    });

    return [...rows].sort((a, b) => {
        if (a.enabled !== b.enabled) {
            return a.enabled ? -1 : 1;
        }
        return a.name.localeCompare(b.name);
    });
});

const selectedCourseCount = computed(() => selectedCourseIds.length);

const selectedLevelOfferingCount = computed(() =>
    selectedCourseIds.reduce((total, courseId) => total + (selectedLevelIdsByCourse[courseId]?.length ?? 0), 0),
);

const courseLevelSummary = (courseId: number): string => {
    const count = selectedLevelIdsByCourse[courseId]?.length ?? 0;
    if (count === 0) {
        return '';
    }
    return String(count);
};

const incompleteCourseIds = computed(() => {
    if (!form.enabled) {
        return [] as number[];
    }

    return selectedCourseIds.filter((courseId) => {
        const levels = selectedLevelIdsByCourse[courseId] ?? [];
        if (levels.length === 0) {
            return true;
        }

        return levels.some((levelId) => (modeIdsByPair[pairKey(courseId, levelId)] ?? []).length === 0);
    });
});
</script>

<template>
    <Head :title="`${$t('application_offerings.configure')} — ${department.name}`" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('application-offerings.index')">
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem] xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-3">
                <header class="rounded-lg border border-border bg-card px-3 py-2.5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <InstitutionDepartmentNameCell
                                :department-name="department.name"
                                :color-code="department.colorCode"
                            />
                            <p v-if="department.departmentCode" class="mt-0.5 text-[11px] text-muted-foreground">
                                {{ department.departmentCode }}
                            </p>
                            <p class="mt-1.5 max-w-2xl text-xs leading-relaxed text-muted-foreground">
                                {{ $t('application_offerings.courses_description') }}
                            </p>
                        </div>
                        <div
                            v-if="form.enabled"
                            class="flex flex-wrap items-center gap-1.5 text-[11px]"
                        >
                            <span class="rounded-full bg-primary/10 px-2 py-0.5 font-medium text-primary tabular-nums">
                                {{ selectedCourseCount }} {{ $tChoice('trans.course', selectedCourseCount) }}
                            </span>
                            <span class="rounded-full bg-muted px-2 py-0.5 font-medium text-muted-foreground tabular-nums">
                                {{ selectedLevelOfferingCount }} {{ $tChoice('trans.level', selectedLevelOfferingCount) }}
                            </span>
                        </div>
                    </div>
                </header>

                <EnrolmentSetupTabs :department-id="department.id" active="offerings" />

                <BaseAlert
                    v-if="flashSuccess"
                    :type="TypeVariant.success"
                    :description="flashSuccess"
                />

                <BaseAlert
                    v-if="form.errors.levels"
                    :type="TypeVariant.danger"
                    :description="String(form.errors.levels)"
                />

                <BaseAlert
                    v-if="form.enabled && incompleteCourseIds.length > 0"
                    :type="TypeVariant.warning"
                    :description="$t('application_offerings.no_modes_selected')"
                />

                <form class="space-y-3" @submit.prevent="save">
                    <div class="grid gap-2 rounded-lg border border-border bg-card p-3 sm:grid-cols-2">
                        <div class="rounded-md bg-muted/25 px-2.5 py-2">
                            <BaseSwitch
                                input-id="offering_enabled"
                                v-model="form.enabled"
                                :label="$t('application_offerings.enable_department')"
                                :on-update="(value) => (form.enabled = value)"
                            />
                        </div>
                        <div
                            class="rounded-md px-2.5 py-2"
                            :class="form.enabled ? 'bg-muted/25' : 'bg-muted/10 opacity-60'"
                        >
                            <BaseSwitch
                                input-id="has_apprentice_programmes"
                                v-model="form.has_apprentice_programmes"
                                :label="$t('application_offerings.has_apprentice_programmes')"
                                :disabled="!form.enabled"
                                :on-update="(value) => (form.has_apprentice_programmes = value)"
                            />
                            <p class="mt-1 text-[11px] leading-snug text-muted-foreground">
                                {{ $t('application_offerings.has_apprentice_programmes_description') }}
                            </p>
                        </div>
                    </div>

                    <template v-if="form.enabled">
                        <div class="flex items-end justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-semibold text-foreground">
                                    {{ $t('application_offerings.courses_heading') }}
                                </h2>
                                <p class="text-[11px] text-muted-foreground">
                                    {{ $t('application_offerings.modes_description') }}
                                </p>
                            </div>
                            <span class="text-[11px] text-muted-foreground tabular-nums">
                                {{ availableCourses.length }} {{ $tChoice('trans.course', availableCourses.length) }}
                            </span>
                        </div>

                        <BaseAlert
                            v-if="availableCourses.length === 0"
                            :title="$t('trans.no_data')"
                            :description="$t('application_offerings.empty_courses')"
                        />

                        <div v-else class="space-y-2">
                            <section
                                v-for="course in availableCourses"
                                :key="course.id"
                                class="overflow-hidden rounded-lg border transition-colors"
                                :class="
                                    selectedCourseIds.includes(course.id)
                                        ? 'border-primary/35 bg-card shadow-sm'
                                        : 'border-border/80 bg-card/60'
                                "
                            >
                                <div
                                    class="flex items-center gap-2 px-3 py-2"
                                    :class="selectedCourseIds.includes(course.id) ? 'bg-primary/5' : 'bg-muted/15'"
                                >
                                    <BaseCheckbox
                                        :input-id="`course_${course.id}`"
                                        :value="course.id"
                                        v-model="selectedCourseIds"
                                        :label="course.name"
                                    />
                                    <span
                                        v-if="courseLevelSummary(course.id)"
                                        class="ml-auto shrink-0 rounded-full bg-background px-2 py-0.5 text-[10px] font-medium text-muted-foreground tabular-nums ring-1 ring-border"
                                    >
                                        {{ courseLevelSummary(course.id) }}
                                        {{ $tChoice('trans.level', Number(courseLevelSummary(course.id))) }}
                                    </span>
                                </div>

                                <div
                                    v-if="isCourseSelected(course.id)"
                                    class="space-y-2 border-t border-border/50 px-3 py-2.5"
                                >
                                    <p class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                                        {{ $t('application_offerings.levels_heading') }}
                                    </p>

                                    <div class="grid gap-2">
                                        <div
                                            v-for="level in course.levels"
                                            :key="`${course.id}_${level.id}`"
                                            class="rounded-md border px-2.5 py-2"
                                            :class="
                                                (selectedLevelIdsByCourse[course.id] ?? []).includes(level.id)
                                                    ? 'border-border bg-muted/20'
                                                    : 'border-dashed border-border/70 bg-background'
                                            "
                                        >
                                            <BaseCheckbox
                                                :input-id="`level_${course.id}_${level.id}`"
                                                :value="level.id"
                                                v-model="selectedLevelIdsByCourse[course.id]"
                                                :label="level.name"
                                            />

                                            <div
                                                v-if="isLevelSelected(course.id, level.id)"
                                                class="mt-2 border-t border-border/40 pt-2"
                                            >
                                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                                    <p class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                                                        {{ $t('application_offerings.modes_heading') }}
                                                    </p>
                                                    <span
                                                        v-if="(modeIdsByPair[pairKey(course.id, level.id)] ?? []).length === 0"
                                                        class="text-[10px] font-medium text-amber-600"
                                                    >
                                                        {{ $t('application_offerings.no_modes_selected') }}
                                                    </span>
                                                </div>
                                                <div class="flex flex-wrap gap-x-3 gap-y-1">
                                                    <BaseCheckbox
                                                        v-for="mode in modesOfStudy"
                                                        :key="`mode_${course.id}_${level.id}_${mode.id}`"
                                                        :input-id="`mode_${course.id}_${level.id}_${mode.id}`"
                                                        :value="mode.id"
                                                        v-model="modeIdsByPair[pairKey(course.id, level.id)]"
                                                        :label="mode.name"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </template>

                    <div
                        v-else
                        class="rounded-lg border border-dashed border-border bg-muted/10 px-4 py-8 text-center"
                    >
                        <p class="text-sm font-medium text-foreground">
                            {{ $t('application_offerings.not_enabled') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $t('application_offerings.enable_department') }}
                        </p>
                    </div>

                    <div
                        class="sticky bottom-3 z-10 flex items-center justify-between gap-3 rounded-lg border border-border bg-card/95 px-3 py-2 shadow-sm backdrop-blur"
                    >
                        <p class="text-[11px] text-muted-foreground">
                            <template v-if="form.enabled">
                                {{ selectedCourseCount }} {{ $tChoice('trans.course', selectedCourseCount) }}
                                ·
                                {{ selectedLevelOfferingCount }} {{ $tChoice('trans.level', selectedLevelOfferingCount) }}
                            </template>
                            <template v-else>
                                {{ $t('application_offerings.not_enabled') }}
                            </template>
                        </p>
                        <div class="flex gap-2">
                            <BaseButton
                                type="button"
                                :variant="ColorVariant.shade"
                                :size="ButtonSize.sm"
                                @click="$inertia.visit(route('application-offerings.index'))"
                            >
                                {{ $t('trans.back') }}
                            </BaseButton>
                            <BaseButton
                                type="submit"
                                :processing="form.processing"
                                :disabled="form.processing"
                                :size="ButtonSize.sm"
                            >
                                {{ $t('trans.save') }}
                            </BaseButton>
                        </div>
                    </div>
                </form>
            </div>

            <aside class="order-first lg:order-0 lg:sticky lg:top-20 lg:self-start">
                <div class="overflow-hidden rounded-lg border border-border bg-card">
                    <div class="border-b border-border/70 px-3 py-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                                {{ $t('application_offerings.departments_nav') }}
                            </p>
                            <span class="text-[10px] tabular-nums text-muted-foreground">
                                {{ filteredNavDepartments.length }}
                            </span>
                        </div>
                        <input
                            v-model="navSearch.query"
                            type="search"
                            :placeholder="$t('application_offerings.search_departments')"
                            class="mt-2 h-8 w-full rounded-md border border-border bg-background px-2.5 text-xs outline-none ring-primary/30 placeholder:text-muted-foreground focus:ring-2"
                        />
                    </div>
                    <nav class="max-h-[min(72vh,36rem)] overflow-y-auto p-1.5">
                        <InertiaLink
                            v-for="navDepartment in filteredNavDepartments"
                            :key="navDepartment.id"
                            :href="route('application-offerings.show', navDepartment.id)"
                            preserve-scroll
                            class="mb-0.5 flex items-start gap-2.5 rounded-md px-2.5 py-2 transition-colors last:mb-0"
                            :class="
                                cn(
                                    navDepartment.id === department.id
                                        ? 'bg-primary/10 text-primary ring-1 ring-primary/20'
                                        : 'text-foreground hover:bg-muted/60',
                                )
                            "
                        >
                            <DepartmentColorSwatch
                                :color-code="navDepartment.colorCode"
                                :department-name="navDepartment.name"
                                size-class="mt-1 h-3 w-3"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-semibold leading-snug wrap-break-word">
                                    {{ navDepartment.name }}
                                </span>
                                <span class="mt-1 flex flex-wrap items-center gap-1.5 text-[10px] text-muted-foreground">
                                    <span v-if="navDepartment.departmentCode" class="font-medium tracking-wide">
                                        {{ navDepartment.departmentCode }}
                                    </span>
                                    <span
                                        class="inline-flex rounded-full px-1.5 py-px font-medium"
                                        :class="
                                            navDepartment.enabled
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{ navDepartment.enabled ? $t('trans.yes') : $t('trans.no') }}
                                    </span>
                                </span>
                            </span>
                        </InertiaLink>
                        <p
                            v-if="filteredNavDepartments.length === 0"
                            class="px-2 py-6 text-center text-xs text-muted-foreground"
                        >
                            {{ $t('trans.no_data') }}
                        </p>
                    </nav>
                </div>
            </aside>
        </div>
    </PageContainer>
</template>
