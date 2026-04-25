<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import Code from '@/components/core/form/text/Code.vue';
import DepartmentLevelCourseComboSelect from '@/components/core/form/combobox/DepartmentLevelCourseComboSelect.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { Label } from '@/components/ui/label';
import { useCourseSyllabuses } from '@/composables/institution/useCourseSyllabuses';
import { clearFormErrors } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { CourseSyllabus, CourseSyllabusParams, InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { trans, trans_choice } from 'laravel-vue-i18n';

interface Props {
    institutionDepartment: InstitutionDepartment;
    courseSyllabus?: CourseSyllabus | null;
}

const props = defineProps<Props>();
const { institutionDepartment } = props;
const institutionDepartmentId = Number(props.institutionDepartment?.id ?? 0);
const selectedInstitutionDepartment = ref<SelectOption>({
    value: institutionDepartmentId,
    label: props.institutionDepartment?.attributes?.department ?? '',
});
const selectedDepartmentLevelCourse = ref<SelectOption | undefined>(
    props.courseSyllabus?.id
        ? {
              value: Number(props.courseSyllabus?.attributes?.departmentLevelCourseId ?? 0),
              label: `${props.courseSyllabus?.attributes?.level ?? ''} - ${props.courseSyllabus?.attributes?.course ?? ''}`,
          }
        : undefined,
);

const form = useForm<CourseSyllabusParams>({
    institution_department_id: institutionDepartmentId || null,
    department_level_course_id: Number(props.courseSyllabus?.attributes?.departmentLevelCourseId ?? 0) || null,
    title: props.courseSyllabus?.attributes?.title ?? '',
    code: props.courseSyllabus?.attributes?.code ?? '',
    implementation_year: props.courseSyllabus?.attributes?.implementationYear ?? '',
    status: (props.courseSyllabus?.attributes?.status as 'active' | 'terminated') ?? 'active',
    syllabus_document: null,
});

const { formSchema, saveCourseSyllabus } = useCourseSyllabuses();

const isEditMode = computed(() => !!props.courseSyllabus?.id);
const pageTitle = computed(() =>
    isEditMode.value
        ? `${trans('trans.update')} ${trans_choice('syllabus.course_syllabus', 1)}`
        : `${trans('trans.create')} ${trans_choice('syllabus.course_syllabus', 1)}`,
);

const breadcrumbs = computed<Array<Link>>(() => [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    {
        transChoiceKey: 'department',
        href: route('institution-departments.index', { is_academic: props.institutionDepartment?.attributes?.isAcademic }),
    },
    {
        title: props.institutionDepartment?.attributes?.department,
        href: route('institution-departments.show', getIdParams(props.institutionDepartment?.id?.toString() ?? '')),
    },
    {
        transChoiceKey: 'syllabus',
        transChoiceKeyIndex: 1,
        href: route('institution-departments.show', getIdParams(props.institutionDepartment?.id?.toString() ?? '')),
    },
    ...(isEditMode.value
        ? [
              { title: props.courseSyllabus?.attributes?.title },
              { title: `${trans('trans.update')} ${trans_choice('syllabus.course_syllabus', 1)}` },
          ]
        : [{ title: `${trans('trans.create')} ${trans_choice('syllabus.course_syllabus', 1)}` }]),
]);

const onSyllabusDocumentChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    form.syllabus_document = file ?? null;
    clearFormErrors(form, 'syllabus_document');
};

const save = async () => {
    form.institution_department_id = Number(selectedInstitutionDepartment.value?.value ?? 0) || null;
    form.department_level_course_id = Number(selectedDepartmentLevelCourse.value?.value ?? 0) || null;

    const result = formSchema().safeParse(form.data());
    if (!result.success) {
        const fieldErrors = result.error.flatten().fieldErrors;
        Object.entries(fieldErrors).forEach(([field, errors]) => {
            if (errors && errors.length > 0) {
                form.setError(field as keyof CourseSyllabusParams, errors[0]);
            }
        });
        return;
    }

    saveCourseSyllabus(form, props.courseSyllabus?.id);
};
</script>

<template>
    <Head :title="pageTitle" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? ''))"
    >
        <BaseCard :title="pageTitle">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <InstitutionDepartmentComboSelect
                    :form="form"
                    v-model="selectedInstitutionDepartment"
                    :is-required="true"
                    :disabled="true"
                    :error="form.errors.institution_department_id"
                />
                <DepartmentLevelCourseComboSelect
                    :form="form"
                    v-model="selectedDepartmentLevelCourse"
                    :institution-department-id="selectedInstitutionDepartment?.value ?? institutionDepartmentId"
                    :is-required="true"
                    :error="form.errors.department_level_course_id"
                />
                <BaseInput
                    input-id="title"
                    :label="$t('syllabus.title')"
                    v-model="form.title"
                    :error="form.errors.title"
                    :is-required="true"
                    @input="clearFormErrors(form, 'title')"
                />
                <Code v-model="form.code" :error="form.errors.code" :is-required="true" @input="clearFormErrors(form, 'code')" />
                <BaseInput
                    input-id="implementation_year"
                    :label="$t('syllabus.implementation_year')"
                    v-model="form.implementation_year"
                    :error="form.errors.implementation_year"
                    :is-required="true"
                    @input="clearFormErrors(form, 'implementation_year')"
                />
                <div class="flex flex-col gap-2">
                    <Label for="course_syllabus_status" class="text-xs uppercase">{{ $t('syllabus.status') }}</Label>
                    <select
                        id="course_syllabus_status"
                        v-model="form.status"
                        class="hava-input min-h-[42px] w-full rounded-md border bg-background px-3 py-2 text-sm"
                        @change="clearFormErrors(form, 'status')"
                    >
                        <option value="active">{{ $t('syllabus.status_active') }}</option>
                        <option value="terminated">{{ $t('syllabus.status_terminated') }}</option>
                    </select>
                    <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
                </div>
                <div class="flex flex-col gap-2 md:col-span-2">
                    <Label for="syllabus_document" class="text-xs uppercase">{{ $t('syllabus.syllabus_document') }}</Label>
                    <input
                        id="syllabus_document"
                        type="file"
                        accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        class="block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium"
                        @change="onSyllabusDocumentChange"
                    />
                    <p v-if="form.errors.syllabus_document" class="text-sm text-destructive">{{ form.errors.syllabus_document }}</p>
                    <p v-if="courseSyllabus?.attributes?.syllabusDocumentUrl" class="text-sm">
                        <span class="text-muted-foreground">{{ $t('syllabus.current_document') }}:</span>
                        <a
                            :href="courseSyllabus.attributes.syllabusDocumentUrl"
                            class="ml-1 font-medium text-primary underline"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ $t('trans.view') }}
                        </a>
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <BaseButton :processing="form.processing" @click="save">
                    {{ $t('trans.save') }}
                </BaseButton>
            </div>
        </BaseCard>
    </PageContainer>
</template>
