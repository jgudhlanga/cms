<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import InputError from '@/components/core/form/InputError.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import SelectSitting from '@/components/students/update/SelectSitting.vue';
import SelectYear from '@/components/students/update/SelectYear.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { errorAlert, successAlert } from '@/lib/alerts';
import { AuthObject } from '@/types/data-pagination';
import { OLevelSubjectResult } from '@/types/enrolments';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, useForm } from '@inertiajs/vue3';

interface Props {
    auth: AuthObject;
    errors: object;
    oLevelSubjectResults?: OLevelSubjectResult[];
    student: Student;
}

const props = defineProps<Props>();
const { oLevelSubjectResults, student } = props;
const { navigateTo, isItTrue } = useUtils();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transChoiceKey: 'dashboard', href: route('portal.dashboard') },
    { title: 'O Level', href: route('portal.list-o-levels') },
    { title: 'Manage O-Level' },
];

const { subjectForms, subjectErrors, getOptionsForSubject, isLoading, SubjectResultSchema } = useOLevelResults(oLevelSubjectResults || []);
const saveSubjectResult = (subjectId: string) => {
    const payload = subjectForms.value[subjectId];
    const parsed = SubjectResultSchema.safeParse(payload);

    subjectErrors.value[subjectId] = {};
    if (!parsed.success) {
        parsed.error.errors.forEach((err) => {
            const path = err.path[0] ?? '_form';
            subjectErrors.value[subjectId][String(path)] = err.message;
        });
        return;
    }

    const form = useForm({
        subject_id: subjectId,
        exam_year: parsed.data.exam_year,
        exam_sitting: parsed.data.exam_sitting?.value ?? null,
        grade_id: parsed.data.grade_id,
    });

    form.post(route('portal.store-o-level-results', String(student.id)), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert('Result successfully created');
            subjectErrors.value[subjectId] = {};
        },
        onError: (errors: any) => {
            if (errors) {
                Object.entries(errors).forEach(([key, value]) => {
                    subjectErrors.value[subjectId][key] = Array.isArray(value) ? value.join(', ') : String(value);
                });
                errorAlert(Object.values(errors).flat().join('\n'));
            } else {
                errorAlert('An unexpected error occurred.');
            }
        },
    });
};
const verificationMode = isItTrue(import.meta.env.VITE_VERIFICATION_MODE);
</script>
<template>
    <Head :title="$t('trans.ui_manage_o_level')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="my-6 flex items-center justify-between">
            <HeadingSmall
                :title="$t('trans.ui_o_level_results')"
                :description="$t('trans.ui_list_of_o_level_subjects_and_grades_attained_by_a_student')"
            />
            <BaseButton classes="rounded-full" :variant="ColorVariant.primary_outline" @click="navigateTo(route('portal.list-o-levels'))">
                <BaseIcon :name="IconName.back" />
                <span>{{ $t('trans.back') }}</span>
            </BaseButton>
        </div>
        <div class="flex flex-col space-y-4" v-if="oLevelSubjectResults && oLevelSubjectResults?.length > 0">
            <form v-for="(row, index) in oLevelSubjectResults" :key="`mobile_${row?.id ?? ''}`" @submit.prevent="saveSubjectResult(String(row.id))">
                <div class="overflow-hidden rounded-lg border border-border bg-card text-card-foreground shadow">
                    <!-- Card Header -->
                    <div class="border-b border-border bg-muted/30 px-4 py-2">
                        <div class="flex items-center space-x-1 text-xs font-semibold text-foreground uppercase">
                            <span>{{ `${index + 1}.` }}</span>
                            <h3>
                                {{ row?.attributes?.subject }}
                            </h3>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4">
                        <div class="grid grid-cols-1 items-center gap-4 md:grid-cols-4">
                            <div>
                                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{{ $tChoice('trans.year', 1) }}</p>
                                <div class="mt-1">
                                    <SelectYear
                                        :input-id="`year_${row.id}`"
                                        :model-value="subjectForms[row.id as string]?.exam_year"
                                        @update:model-value="
                                            (value) => (subjectForms[row.id as string].exam_year = value !== null ? String(value) : '')
                                        "
                                    />
                                    <InputError
                                        v-if="subjectErrors[row.id]?.exam_year"
                                        :message="subjectErrors[row.id].exam_year"
                                        class="mt-1 flex w-full lowercase"
                                    />
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{{ $tChoice('trans.sitting', 1) }}</p>
                                <div class="mt-1 flex w-full flex-col">
                                    <SelectSitting
                                        class="flex w-full"
                                        :model-value="subjectForms[row.id as string]?.exam_sitting"
                                        @update:modelValue="(option: SelectOption) => (subjectForms[row.id as string].exam_sitting = option)"
                                    />
                                    <InputError
                                        v-if="subjectErrors[row.id]?.exam_sitting"
                                        :message="subjectErrors[row.id].exam_sitting"
                                        class="mt-1 flex w-full lowercase"
                                    />
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{{ $tChoice('trans.grade', 1) }}</p>
                                <div class="mt-1">
                                    <SpinnerComponent class="flex w-full items-center justify-center" v-if="isLoading" />
                                    <template v-else>
                                        <BaseRadioGroup
                                            class="flex items-center"
                                            :options="getOptionsForSubject(row)"
                                            :model-value="
                                                subjectForms[row.id as string]?.grade_id
                                                    ? `${row.id}|${subjectForms[row.id as string]?.grade_id}`
                                                    : null
                                            "
                                            :label-uppercase="true"
                                            :is-required="true"
                                            orientation="horizontal"
                                            :vertical-layout="false"
                                            @update:modelValue="
                                                (value) => {
                                                    const parts = value.split('|');
                                                    subjectForms[row.id as string].grade_id = parts[1];
                                                }
                                            "
                                        />
                                    </template>
                                    <InputError
                                        v-if="subjectErrors[row.id]?.grade_id"
                                        :message="subjectErrors[row.id].grade_id"
                                        class="mt-1 flex w-full lowercase"
                                    />
                                </div>
                            </div>
                            <div class="justify-self-end-safe" v-if="!verificationMode">
                                <BaseButton
                                    :size="ButtonSize.sm"
                                    :variant="ColorVariant.primary"
                                    class="w-full rounded-full md:w-fit"
                                    :title="$t('trans.save')"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <Empty v-else />
    </PageContainer>
</template>
