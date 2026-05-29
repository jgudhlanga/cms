<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { BaseCheckbox } from '@/components/core/form';
import { useAcademicYearOptionsByCalendarType } from '@/composables/academicCalendars/useAcademicYearOptionsByCalendarType';
import { useCourseSyllabusModuleMove, MOVE_SYLLABUS_MODULES_MODAL } from '@/composables/institution/useCourseSyllabusModuleMove';
import { useCourseSyllabusModuleSelection } from '@/composables/institution/useCourseSyllabusModuleSelection';
import { useCourseSyllabusModules } from '@/composables/institution/useCourseSyllabusModules';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { CourseSyllabus, CourseSyllabusModule, InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';
import { ButtonSize } from '@/enums/buttons';
import BaseCard from '@/components/core/card/BaseCard.vue';
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
const canMoveModules = computed(() => hasAbility('update:course-syllabus-modules'));
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
            <BaseCard :title="$tChoice('syllabus.course_syllabus', 1)">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <LabelValue :label="$tChoice('trans.level', 1)" :value="courseSyllabus?.attributes?.level" />
                    <LabelValue :label="$tChoice('trans.course', 1)" :value="courseSyllabus?.attributes?.course" />
                    <LabelValue :label="$tChoice('trans.title', 1)" :value="courseSyllabus?.attributes?.title" />
                    <LabelValue :label="$tChoice('trans.code', 1)" :value="courseSyllabus?.attributes?.code" />
                    <LabelValue
                        :label="$tChoice('syllabus.implementation_year', 1)"
                        :value="String(courseSyllabus?.attributes?.implementationYear ?? '')"
                    />
                    <LabelValue :label="$t('syllabus.status')" :value="courseSyllabus?.attributes?.status" value-classes="capitalize" />
                    <div class="flex w-full items-start gap-3 text-sm text-accent-foreground" v-if="courseSyllabus?.attributes?.syllabusDocumentUrl">
                        <div class="shrink-0 font-medium whitespace-normal wrap-break-word">{{ $tChoice('syllabus.syllabus', 1) }}:</div>
                        <div :class="`min-w-0 flex-1 font-extralight whitespace-normal wrap-anywhere`">
                            <a
                                :href="courseSyllabus.attributes.syllabusDocumentUrl"
                                class="flex h-6 items-center rounded-full border border-primary bg-transparent px-2 py-1 text-xs font-semibold text-primary hover:border-primary hover:bg-primary/20"
                                target="_blank"
                            >
                                <BaseIcon :name="IconName.paperclip" class="size-4" />
                                <span class="ml-2 font-extrabold uppercase">{{ $tChoice('trans.download', 1) }}</span>
                            </a>
                        </div>
                    </div>
                    <div class="flex justify-end md:col-span-3">
                        <IconButton
                            :icon="IconName.edit"
                            :variant="ColorVariant.primary_outline"
                            @click="
                                () =>
                                    router.get(
                                        route('department-course-syllabuses.edit', {
                                            institution_department: institutionDepartment?.id,
                                            course_syllabus: courseSyllabus?.id,
                                        }),
                                    )
                            "
                        />
                    </div>
                </div>
            </BaseCard>
            <BaseCard :title="$tChoice('syllabus.module', 2)">
                <DataLoadingSpinner v-if="isLoading" />
                <template v-else>
                    <DataTable
                        :data="modulesList"
                        :columns="moduleColumns"
                        :show-archived-filter="false"
                        :on-create="() => onOpenModal(canCreateModule, { courseSyllabusId: courseSyllabus.id ?? '' })"
                        :disable-create="!canCreateModule"
                        :pagination="{ ...(courseSyllabusModules?.links ?? {}), ...(courseSyllabusModules?.meta ?? {}) }"
                        :search-url="listUrl"
                        :use-api="true"
                        :api-fetch-action="loadSyllabusModules" 
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
                </template> 
            </BaseCard>
        </div>
        <CreateEditModule
            :course-syllabus-id="Number(courseSyllabus?.id ?? 0)"
            :course-syllabus-title="courseSyllabus?.attributes?.title ?? ''"
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
