<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { getIdParams } from '@/lib/utils';
import { CourseSyllabus, InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';

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
        <div class="space-y-4 rounded-lg border p-4">
            <h3 class="text-sm font-semibold uppercase">Course Syllabus</h3>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs uppercase">{{ $tChoice('trans.title', 1) }}</label>
                    <p class="hava-input min-h-[42px]">{{ courseSyllabus?.attributes?.title }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs uppercase">{{ $tChoice('trans.code', 1) }}</label>
                    <p class="hava-input min-h-[42px]">{{ courseSyllabus?.attributes?.code }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs uppercase">{{ $tChoice('trans.year', 1) }}</label>
                    <p class="hava-input min-h-[42px]">{{ courseSyllabus?.attributes?.implementationYear }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs uppercase">{{ $t('syllabus.status') }}</label>
                    <p class="hava-input min-h-[42px] capitalize">{{ courseSyllabus?.attributes?.status ?? '---' }}</p>
                </div>
                <div v-if="courseSyllabus?.attributes?.syllabusDocumentUrl" class="md:col-span-2">
                    <label class="mb-1 block text-xs uppercase">{{ $t('syllabus.syllabus_document') }}</label>
                    <p class="hava-input min-h-[42px]">
                        <a
                            :href="courseSyllabus.attributes.syllabusDocumentUrl"
                            class="font-medium text-primary underline"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ $t('trans.view') }}
                        </a>
                    </p>
                </div>
                <div>
                    <label class="mb-1 block text-xs uppercase">{{ $tChoice('trans.level', 1) }}</label>
                    <p class="hava-input min-h-[42px]">{{ courseSyllabus?.attributes?.level }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs uppercase">{{ $tChoice('trans.course', 1) }}</label>
                    <p class="hava-input min-h-[42px]">{{ courseSyllabus?.attributes?.course ?? '---' }}</p>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <BaseButton
                    @click="
                        () =>
                            router.get(
                                route('department-course-syllabuses.edit', {
                                    institution_department: institutionDepartment?.id,
                                    course_syllabus: courseSyllabus?.id,
                                }),
                            )
                    "
                >
                    {{ $t('trans.edit') }}
                </BaseButton>
            </div>
        </div>
    </PageContainer>
</template>
