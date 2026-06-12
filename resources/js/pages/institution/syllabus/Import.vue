<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { TypeVariant } from '@/enums/type-variants';
import CourseSyllabusImportPanel from '@/pages/institution/syllabus/partials/CourseSyllabusImportPanel.vue';
import { useDepartmentMetaStore } from '@/store/institution/useDepartmentMetaStore';
import type { InstitutionDepartment } from '@/types/institution';
import type { SyllabusImportResult } from '@/types/syllabus-import';
import type { Link } from '@/types/ui';
import { getIdParams } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps<{
    institutionDepartment: InstitutionDepartment;
    syllabusImportResult?: SyllabusImportResult | null;
}>();

const institutionDepartmentId = computed(() => String(props.institutionDepartment?.id ?? ''));

const departmentShowUrl = computed(() =>
    route('institution-departments.show', getIdParams(institutionDepartmentId.value)),
);

const breadcrumbs = computed<Array<Link>>(() => [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    {
        transChoiceKey: 'department',
        href: route('institution-departments.index', { is_academic: props.institutionDepartment?.attributes?.isAcademic }),
    },
    {
        title: props.institutionDepartment?.attributes?.department,
        href: departmentShowUrl.value,
    },
    {
        transChoiceKey: 'syllabus',
        transChoiceKeyIndex: 1,
        href: departmentShowUrl.value,
    },
    { transChoiceKey: 'syllabus.import_title', transChoiceKeyIndex: 1 },
]);

onMounted(() => {
    useDepartmentMetaStore().activeTab = 'course_syllabuses';
});
</script>

<template>
    <Head :title="$tChoice('syllabus.import_title', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="departmentShowUrl">
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold uppercase">{{ $tChoice('syllabus.import_title', 1) }}</h2>
            </div>
        </template>

        <div class="space-y-4">
            <BaseAlert :type="TypeVariant.info" :description="$t('syllabus.import_description')" />

            <CourseSyllabusImportPanel
                :institution-department-id="institutionDepartmentId"
                :syllabus-import-result="syllabusImportResult"
            />
        </div>
    </PageContainer>
</template>
