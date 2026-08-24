<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DepartmentColorSwatch from '@/components/institution/DepartmentColorSwatch.vue';
import InstitutionDepartmentNameCell from '@/components/institution/InstitutionDepartmentNameCell.vue';
import EnrolmentSetupTabs from '@/pages/institution/enrolments/partials/EnrolmentSetupTabs.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { cn } from '@/lib/utils';
import type { Link } from '@/types/ui';
import { Head, Link as InertiaLink } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface LevelRow {
    id: number;
    name: string;
    configured: boolean;
}

interface CourseRow {
    id: number;
    name: string;
    hasEnrolmentRequirements: boolean;
    levels: Array<{ id: number; name: string }>;
}

interface NavDepartment {
    id: number;
    name: string;
    departmentCode: string;
    colorCode?: string | null;
}

interface Props {
    department: {
        id: number;
        name: string;
        departmentCode: string;
        colorCode?: string | null;
    };
    levels: LevelRow[];
    courses: CourseRow[];
    navigationDepartments: NavDepartment[];
}

const props = defineProps<Props>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'institution_setup', href: route('institution.setup') },
    { title: 'Enrolment setup', href: route('application-offerings.index') },
    { title: props.department.name },
];

const navSearch = reactive({ query: '' });

const filteredNavDepartments = computed(() => {
    const needle = navSearch.query.trim().toLowerCase();

    return [...props.navigationDepartments]
        .filter((department) => {
            if (needle === '') {
                return true;
            }

            return department.name.toLowerCase().includes(needle) || department.departmentCode.toLowerCase().includes(needle);
        })
        .sort((a, b) => a.name.localeCompare(b.name));
});
</script>

<template>
    <Head :title="`${$t('application_requirements.requirements_heading')} — ${department.name}`" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('application-offerings.index')">
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem] xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-4">
                <header class="rounded-lg border border-border bg-card px-3 py-2.5">
                    <InstitutionDepartmentNameCell :department-name="department.name" :color-code="department.colorCode" />
                    <p v-if="department.departmentCode" class="mt-0.5 text-[11px] text-muted-foreground">
                        {{ department.departmentCode }}
                    </p>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ $t('application_requirements.requirements_description') }}
                    </p>
                </header>

                <EnrolmentSetupTabs :department-id="department.id" active="requirements" />

                <section class="space-y-3 rounded-lg border border-border bg-card p-4">
                    <h2 class="text-sm font-semibold">{{ $t('application_requirements.level_requirements') }}</h2>
                    <div class="divide-y divide-border rounded-md border border-border">
                        <div
                            v-for="level in levels"
                            :key="level.id"
                            class="flex items-center justify-between gap-3 px-3 py-2.5"
                        >
                            <div>
                                <p class="text-sm font-medium">{{ level.name }}</p>
                                <p class="text-[11px] text-muted-foreground">
                                    {{ level.configured ? $t('application_requirements.configured') : $t('application_requirements.not_configured') }}
                                </p>
                            </div>
                            <InertiaLink
                                :href="
                                    route('application-requirements.level', {
                                        institution_department: department.id,
                                        department_level: level.id,
                                    })
                                "
                            >
                                <BaseButton
                                    :title="$t('application_requirements.configure_requirements')"
                                    :variant="ColorVariant.primary_outline"
                                    :size="ButtonSize.xs"
                                    classes="rounded-full"
                                />
                            </InertiaLink>
                        </div>
                        <p v-if="levels.length === 0" class="px-3 py-6 text-center text-xs text-muted-foreground">
                            {{ $t('application_offerings.empty_levels') }}
                        </p>
                    </div>
                </section>

                <section v-if="courses.length > 0" class="space-y-3 rounded-lg border border-border bg-card p-4">
                    <div>
                        <h2 class="text-sm font-semibold">{{ $t('application_requirements.course_requirements') }}</h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $t('application_requirements.course_requirements_description') }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="course in courses"
                            :key="course.id"
                            class="rounded-md border border-border px-3 py-2.5"
                        >
                            <p class="text-sm font-medium">{{ course.name }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <InertiaLink
                                    v-for="level in course.levels"
                                    :key="`${course.id}_${level.id}`"
                                    :href="
                                        route('application-requirements.course', {
                                            institution_department: department.id,
                                            department_course: course.id,
                                            department_level_id: level.id,
                                        })
                                    "
                                >
                                    <BaseButton
                                        :title="level.name"
                                        :variant="ColorVariant.shade"
                                        :size="ButtonSize.xs"
                                        classes="rounded-full"
                                    />
                                </InertiaLink>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="order-first lg:order-0 lg:sticky lg:top-20 lg:self-start">
                <div class="overflow-hidden rounded-lg border border-border bg-card">
                    <div class="border-b border-border/70 px-3 py-2.5">
                        <p class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                            {{ $t('application_offerings.departments_nav') }}
                        </p>
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
                            :href="route('application-requirements.department', navDepartment.id)"
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
                                <span v-if="navDepartment.departmentCode" class="mt-1 block text-[10px] text-muted-foreground">
                                    {{ navDepartment.departmentCode }}
                                </span>
                            </span>
                        </InertiaLink>
                    </nav>
                </div>
            </aside>
        </div>
    </PageContainer>
</template>
