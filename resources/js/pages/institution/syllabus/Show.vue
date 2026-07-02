<script setup lang="ts">
import { BaseButton, IconButton } from '@/components/core/button';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { BaseCheckbox } from '@/components/core/form';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { Badge } from '@/components/ui/badge';
import { useAcademicYearOptionsByCalendarType } from '@/composables/academicCalendars/useAcademicYearOptionsByCalendarType';
import { useCourseSyllabusModuleMove, MOVE_SYLLABUS_MODULES_MODAL } from '@/composables/institution/useCourseSyllabusModuleMove';
import { useCourseSyllabusModuleSelection } from '@/composables/institution/useCourseSyllabusModuleSelection';
import { useCourseSyllabusModules } from '@/composables/institution/useCourseSyllabusModules';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { CourseSyllabus, CourseSyllabusModule, InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';
import { ButtonSize } from '@/enums/buttons';
import BaseCard from '@/components/core/card/BaseCard.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { computed, onMounted, ref, watch } from 'vue';
import CreateEditModule from '@/pages/institution/syllabus/partials/CreateEditModule.vue';
import MoveCourseSyllabusModulesModal from '@/pages/institution/syllabus/partials/MoveCourseSyllabusModulesModal.vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    courseSyllabus: CourseSyllabus;
}

const props = defineProps<Props>();
const { institutionDepartment, courseSyllabus } = props;
const canViewModules = hasAbility(['viewAny:course-syllabus-modules', 'view:course-syllabus-modules']);
const canCreateModule = hasAbility('create:course-syllabus-modules');
const canUpdateModule = hasAbility('update:course-syllabus-modules');
const canUpdateSyllabus = hasAbility('update:course-syllabuses');
const canMoveModules = computed(() => hasAbility('update:course-syllabus-modules'));
const syllabusStatus = computed(() => courseSyllabus?.attributes?.status ?? '');
const syllabusStatusLabel = computed(() =>
    syllabusStatus.value === 'terminated' ? trans('syllabus.status_terminated') : trans('syllabus.status_active'),
);
const syllabusStatusBadgeClass = computed(() =>
    syllabusStatus.value === 'terminated'
        ? 'w-fit border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200'
        : 'w-fit border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200',
);
const syllabusCode = computed(() => courseSyllabus?.attributes?.code ?? '');
const modulesList = ref<CourseSyllabusModule[]>([]);
const {
    isLoading,
    courseSyllabusModules,
    listCourseSyllabusModules,
    createCourseSyllabusModuleColumns,
    onOpenModal,
} = useCourseSyllabusModules();
const institutionDepartmentId = computed(() => String(institutionDepartment?.id ?? ''));
const courseSyllabusId = computed(() => String(courseSyllabus?.id ?? ''));
const calendarType = computed(() => courseSyllabus?.attributes?.calendarType ?? 'semester');

const { yearOptions: moveTargetOptions, loadYearOptions } = useAcademicYearOptionsByCalendarType();

const modulesListComputed = computed(() => modulesList.value);

const { selectedModuleIds, selectAllMoveModel, toggleSelectAllMoveFromRow, onSelectAllRowKeydown } =
    useCourseSyllabusModuleSelection(modulesListComputed);

const moveModulesUrl = computed(() =>
    route('course-syllabus-modules.move', {
        institution_department: institutionDepartmentId.value,
        course_syllabus: courseSyllabusId.value,
    }),
);

const { moveForm, openMoveModulesModal, submitMoveModules, resetMoveFormOnModalClose } = useCourseSyllabusModuleMove(
    moveModulesUrl,
    moveTargetOptions,
    selectedModuleIds,
    () => loadSyllabusModules(),
);

const listUrl = computed(() =>
    route('course-syllabus-modules.index', {
        institution_department: institutionDepartmentId.value,
        course_syllabus: courseSyllabusId.value,
    }),
);

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    {
        transChoiceKey: 'department',
        href: route('institution-departments.index', { is_academic: institutionDepartment?.attributes?.isAcademic }),
    },
    {
        title: institutionDepartment?.attributes?.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? '')),
    },
    {
        transChoiceKey: 'syllabus',
        transChoiceKeyIndex: 1,
        href: route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? '')),
    },
    { title: courseSyllabus?.attributes?.title },
];

const loadSyllabusModules = async () => {
    if (!canViewModules || !institutionDepartmentId.value || !courseSyllabusId.value) {
        modulesList.value = [];
        return;
    }

    await listCourseSyllabusModules(institutionDepartmentId.value, courseSyllabusId.value);
    modulesList.value = (courseSyllabusModules.value?.data ?? []) as CourseSyllabusModule[];
};

const loadSyllabusModulesFromUrl = async (url: string) => {
    if (!canViewModules || !institutionDepartmentId.value || !courseSyllabusId.value) {
        modulesList.value = [];
        return;
    }

    await listCourseSyllabusModules(institutionDepartmentId.value, courseSyllabusId.value, url);
    modulesList.value = (courseSyllabusModules.value?.data ?? []) as CourseSyllabusModule[];
};

const moduleColumns = computed(() =>
    createCourseSyllabusModuleColumns({
        onEdit: (module) => onOpenModal(canUpdateModule, { courseSyllabusId: courseSyllabus.id ?? '' }, module),
        canUpdate: canUpdateModule,
        canMoveModules: canMoveModules.value,
        selectedModuleIds,
    }),
);

onMounted(() => {
    if (canMoveModules.value) {
        loadYearOptions(calendarType.value);
    }
});

watch([institutionDepartmentId, courseSyllabusId], () => loadSyllabusModules(), { immediate: true });
</script>

<template>
    <Head :title="courseSyllabus?.attributes?.title ?? 'Syllabus'" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? ''))"
    >
        <div class="flex flex-col space-y-5">
            <BaseCard>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 flex-col gap-1.5">
                        <HeadingSmall :title="courseSyllabus?.attributes?.title ?? '---'" />
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge v-if="syllabusCode" variant="outline" class="w-fit font-mono text-xs">
                                {{ syllabusCode }}
                            </Badge>
                            <Badge variant="outline" :class="syllabusStatusBadgeClass">
                                {{ syllabusStatusLabel }}
                            </Badge>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <BaseTooltip
                            v-if="courseSyllabus?.attributes?.syllabusDocumentUrl"
                            :content="$tChoice('syllabus.syllabus', 1)"
                        >
                            <a
                                :href="courseSyllabus.attributes.syllabusDocumentUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex"
                            >
                                <IconButton :icon="IconName.paperclip" tone="header-primary" />
                            </a>
                        </BaseTooltip>
                        <BaseTooltip v-if="canUpdateSyllabus" :content="$t('trans.edit')">
                            <IconButton
                                :icon="IconName.edit"
                                tone="header-primary"
                                @click="
                                    router.get(
                                        route('department-course-syllabuses.edit', {
                                            institution_department: institutionDepartment?.id,
                                            course_syllabus: courseSyllabus?.id,
                                        }),
                                    )
                                "
                            />
                        </BaseTooltip>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <LabelValue :label="$tChoice('trans.level', 1)" :value="courseSyllabus?.attributes?.level" />
                    <LabelValue :label="$tChoice('trans.course', 1)" :value="courseSyllabus?.attributes?.course" />
                    <LabelValue
                        :label="$tChoice('syllabus.implementation_year', 1)"
                        :value="String(courseSyllabus?.attributes?.implementationYear ?? '')"
                    />
                </div>
            </BaseCard>
            <div class="flex flex-col space-y-4">
                <HeadingSmall :title="$tChoice('syllabus.module', 2)" />
                <DataLoadingSpinner v-if="isLoading" />
                <DataTable
                    v-else
                    :data="modulesList"
                    :columns="moduleColumns"
                    :show-archived-filter="false"
                    :on-create="() => onOpenModal(canCreateModule, { courseSyllabusId: courseSyllabus.id ?? '' })"
                    :disable-create="!canCreateModule"
                    :pagination="{ ...(courseSyllabusModules?.links ?? {}), ...(courseSyllabusModules?.meta ?? {}) }"
                    :search-url="listUrl"
                    :use-api="true"
                    :api-fetch-action="loadSyllabusModulesFromUrl"
                >
                    <template #head-left v-if="canMoveModules && modulesList.length > 0">
                        <div
                            class="flex shrink-0 flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 px-4 py-2 dark:border-gray-700"
                            role="button"
                            tabindex="0"
                            :aria-label="$t('trans.select_all')"
                            @click="toggleSelectAllMoveFromRow"
                            @keydown="onSelectAllRowKeydown"
                        >
                            <div class="flex items-center gap-2" @click.stop>
                                <BaseCheckbox v-model="selectAllMoveModel" input-id="select_all_move_modules" :label="''" />
                                <span class="text-xs font-semibold uppercase">{{ $t('trans.select_all') }}</span>
                            </div>
                            <BaseButton
                                v-if="selectedModuleIds.length > 0 && moveTargetOptions.length > 0"
                                :size="ButtonSize.xs"
                                :variant="ColorVariant.danger"
                                type="button"
                                classes="rounded-full"
                                @click.stop="openMoveModulesModal"
                            >
                                {{ $t('syllabus.move_modules') }}
                            </BaseButton>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
        <CreateEditModule
            :course-syllabus-id="Number(courseSyllabus?.id ?? 0)"
            :course-syllabus-title="courseSyllabus?.attributes?.title ?? ''"
            :institution-department-id="Number(institutionDepartment?.id ?? 0)"
            :calendar-type="calendarType"
        />
        <MoveCourseSyllabusModulesModal
            v-if="canMoveModules"
            v-model:form="moveForm"
            :modal-name="MOVE_SYLLABUS_MODULES_MODAL"
            :move-target-options="moveTargetOptions"
            :on-form-action="submitMoveModules"
            :on-close-modal="resetMoveFormOnModalClose"
        />
    </PageContainer>
</template>
