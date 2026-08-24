<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import InstitutionDepartmentNameCell from '@/components/institution/InstitutionDepartmentNameCell.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { hasAbility } from '@/lib/permissions';
import type { Link } from '@/types/ui';
import { Head, Link as InertiaLink } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface DepartmentRow {
    id: number;
    name: string;
    departmentCode: string;
    colorCode?: string | null;
    enabled: boolean;
    hasApprenticeProgrammes: boolean;
    levelsCount: number;
}

const props = defineProps<{
    departments: DepartmentRow[];
}>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'institution_setup', href: route('institution.setup') },
    { title: 'Enrolment setup' },
];

const search = ref('');
const offeredOnly = ref(false);

const filteredDepartments = computed(() => {
    const needle = search.value.trim().toLowerCase();

    return props.departments.filter((department) => {
        if (offeredOnly.value && !department.enabled) {
            return false;
        }

        if (needle === '') {
            return true;
        }

        return (
            department.name.toLowerCase().includes(needle)
            || department.departmentCode.toLowerCase().includes(needle)
        );
    });
});

const offeredCount = computed(() => props.departments.filter((d) => d.enabled).length);

const levelDots = (count: number): boolean[] => {
    const total = Math.max(5, count);
    return Array.from({ length: Math.min(total, 5) }, (_, index) => index < count);
};
</script>

<template>
    <Head :title="$t('application_offerings.title')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('institution.setup')">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <HeadingSmall
                :title="$t('application_offerings.title')"
                :description="$t('application_offerings.catalogue_description')"
            />
            <div class="flex shrink-0 flex-wrap items-center gap-2 text-xs text-muted-foreground">
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700 tabular-nums">
                    {{ $t('application_offerings.on_applications_count', { count: offeredCount }) }}
                </span>
                <span class="rounded-full bg-muted px-2.5 py-1 font-medium tabular-nums">
                    {{ $tChoice('application_offerings.departments_count', departments.length, { count: departments.length }) }}
                </span>
            </div>
        </div>

        <BaseAlert
            v-if="!hasAbility('manage:online-application-catalogue')"
            :title="$t('trans.forbidden')"
            :description="$t('trans.forbidden_message')"
            :type="TypeVariant.danger"
            class="mt-3"
        />

        <BaseAlert
            v-else-if="departments.length === 0"
            :title="$t('trans.no_data')"
            :description="$t('application_offerings.empty_departments')"
            class="mt-3"
        />

        <template v-else>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative min-w-0 flex-1 sm:max-w-sm">
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="$t('application_offerings.search_departments')"
                        class="h-8 w-full rounded-md border border-border bg-background px-3 text-xs outline-none ring-primary/30 placeholder:text-muted-foreground focus:ring-2"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex h-8 items-center gap-1.5 rounded-md border px-2.5 text-xs font-medium transition-colors"
                        :class="
                            offeredOnly
                                ? 'border-primary bg-primary/5 text-primary'
                                : 'border-border text-muted-foreground hover:bg-muted/50'
                        "
                        @click="offeredOnly = !offeredOnly"
                    >
                        {{ $t('application_offerings.offered_only') }}
                    </button>
                    <span class="text-[11px] text-muted-foreground">{{ $t('application_offerings.sorted_az') }}</span>
                </div>
            </div>

            <div class="mt-3 overflow-hidden rounded-lg border border-border">
                <table class="w-full text-left text-xs">
                    <thead class="bg-muted/40 text-[10px] uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2 font-semibold">{{ $tChoice('trans.department', 1) }}</th>
                            <th class="px-3 py-2 font-semibold">{{ $t('application_offerings.enable_department') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ $tChoice('trans.level', 2) }}</th>
                            <th class="px-3 py-2 font-semibold">{{ $t('application_offerings.has_apprentice_programmes') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ $tChoice('trans.action', 1) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="department in filteredDepartments"
                            :key="department.id"
                            class="border-t border-border/60 hover:bg-muted/20"
                        >
                            <td class="px-3 py-2">
                                <InstitutionDepartmentNameCell
                                    :department-name="department.name"
                                    :color-code="department.colorCode"
                                />
                                <p v-if="department.departmentCode" class="mt-0.5 pl-5 text-[10px] text-muted-foreground">
                                    {{ department.departmentCode }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="
                                        department.enabled
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    <span aria-hidden="true">{{ department.enabled ? '✓' : '–' }}</span>
                                    {{ department.enabled ? $t('trans.yes') : $t('trans.no') }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div v-if="department.enabled && department.levelsCount > 0" class="flex items-center gap-2">
                                    <div class="flex items-center gap-0.5" aria-hidden="true">
                                        <span
                                            v-for="(filled, index) in levelDots(department.levelsCount)"
                                            :key="index"
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="filled ? 'bg-primary' : 'bg-muted-foreground/25'"
                                        />
                                    </div>
                                    <span class="tabular-nums text-muted-foreground">
                                        {{ $tChoice('application_offerings.levels_count', department.levelsCount, { count: department.levelsCount }) }}
                                    </span>
                                </div>
                                <span v-else class="text-muted-foreground">{{ $t('application_offerings.levels_none') }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <span
                                    v-if="department.hasApprenticeProgrammes"
                                    class="inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                                >
                                    {{ $t('trans.yes') }}
                                </span>
                                <span v-else class="text-muted-foreground">{{ $t('trans.no') }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <InertiaLink :href="route('application-offerings.show', department.id)">
                                    <BaseButton
                                        :title="$t('application_offerings.configure')"
                                        :size="ButtonSize.xs"
                                        :variant="ColorVariant.primary_outline"
                                        classes="rounded-full"
                                    />
                                </InertiaLink>
                            </td>
                        </tr>
                        <tr v-if="filteredDepartments.length === 0">
                            <td colspan="5" class="px-3 py-6 text-center text-muted-foreground">
                                {{ $t('trans.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </PageContainer>
</template>
