<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { getIdParams } from '@/lib/utils';
import { CourseSyllabus, InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';
interface Props {
    institutionDepartment: InstitutionDepartment;
    courseSyllabus: CourseSyllabus;
}

const props = defineProps<Props>();
const { institutionDepartment, courseSyllabus } = props;

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
    { title: courseSyllabus?.attributes?.title },
];
</script>

<template>
    <Head :title="courseSyllabus?.attributes?.title ?? 'Syllabus'" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? ''))"
    >
        <BaseCard :title="$tChoice('syllabus.course_syllabus', 1)">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <LabelValue :label="$tChoice('trans.title', 1)" :value="courseSyllabus?.attributes?.title" />
                <LabelValue :label="$tChoice('trans.code', 1)" :value="courseSyllabus?.attributes?.code" />
                <LabelValue
                    :label="$tChoice('syllabus.implementation_year', 1)"
                    :value="String(courseSyllabus?.attributes?.implementationYear ?? '')"
                />
                <LabelValue :label="$t('syllabus.status')" :value="courseSyllabus?.attributes?.status ?? '---'" value-classes="capitalize" />
                <div class="flex w-full items-start gap-3 text-sm text-accent-foreground" v-if="courseSyllabus?.attributes?.syllabusDocumentUrl">
                    <div :class="`shrink-0 font-medium whitespace-normal wrap-break-word`">{{ $tChoice('syllabus.syllabus', 1) }}:</div>
                    <div :class="`min-w-0 flex-1 font-extralight whitespace-normal wrap-anywhere`">
                        <a
                            :href="courseSyllabus.attributes.syllabusDocumentUrl"
                            class="border-persian-600 text-persian-600 hover:bg-persian-200 hover:border-persian-200 flex h-6 items-center rounded-full bg-transparent px-2 py-1 text-xs"
                            target="_blank"
                        >
                            <BaseIcon :name="IconName.paperclip" class="size-4" />
                            <span class="ml-2 font-extrabold uppercase">{{ $tChoice('trans.download', 1) }}</span>
                        </a>
                    </div>
                </div>
                <LabelValue :label="$tChoice('trans.level', 1)" :value="courseSyllabus?.attributes?.level" />
                <div class="md:col-span-2">
                    <LabelValue :label="$tChoice('trans.course', 1)" :value="courseSyllabus?.attributes?.course ?? '---'" />
                </div>
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
        </BaseCard>
    </PageContainer>
</template>
