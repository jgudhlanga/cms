<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import PortalOLevelHonestyBanner from '@/components/portal/PortalOLevelHonestyBanner.vue';
import OLevelResultFields from '@/components/students/oLevels/OLevelResultFields.vue';
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
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const { subjectForms, subjectErrors, getOptionsForSubject, isLoading, formsReady, SubjectResultSchema } = useOLevelResults(
    oLevelSubjectResults || [],
);

const isSubjectComplete = (row: OLevelSubjectResult): boolean => {
    const subjectId = String(row.id);
    const form = subjectForms.value[subjectId];
    const hasExistingResult = Number(row.attributes?.resultId) > 0;

    if (!form) {
        return hasExistingResult;
    }

    return Boolean(
        form.exam_year &&
            form.exam_sitting?.value &&
            form.grade_id,
    );
};

const sortedSubjects = computed(() => {
    const subjects = [...(oLevelSubjectResults ?? [])];
    return subjects.sort((a, b) => {
        const aComplete = isSubjectComplete(a);
        const bComplete = isSubjectComplete(b);
        if (aComplete !== bComplete) {
            return aComplete ? 1 : -1;
        }
        const aName = String(a.attributes?.subject ?? '');
        const bName = String(b.attributes?.subject ?? '');
        return aName.localeCompare(bName);
    });
});

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
        <div class="my-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <HeadingSmall :title="$t('trans.ui_manage_o_level')" :description="$t('trans.ui_manage_o_level_description')" />
            <BaseButton
                classes="w-full rounded-full sm:w-auto"
                :variant="ColorVariant.primary_outline"
                @click="navigateTo(route('portal.list-o-levels'))"
            >
                <BaseIcon :name="IconName.back" />
                <span>{{ $t('trans.back') }}</span>
            </BaseButton>
        </div>
        <PortalOLevelHonestyBanner v-if="!verificationMode" />
        <SpinnerComponent v-if="!formsReady || isLoading" class="flex w-full items-center justify-center py-8" />
        <div class="flex flex-col space-y-4" v-else-if="sortedSubjects.length > 0">
            <form
                v-for="(row, index) in sortedSubjects"
                :key="`subject_${row?.id ?? ''}`"
                @submit.prevent="saveSubjectResult(String(row.id))"
            >
                <div class="overflow-hidden rounded-lg border border-border bg-card text-card-foreground shadow">
                    <div class="border-b border-border bg-muted/30 px-4 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-1 text-xs font-semibold text-foreground uppercase">
                                <span>{{ `${index + 1}.` }}</span>
                                <h3 class="truncate">{{ row?.attributes?.subject }}</h3>
                            </div>
                            <span
                                v-if="isSubjectComplete(row)"
                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                            >
                                <BaseIcon :name="IconName.check" class="size-3.5" />
                                {{ $t('trans.ui_saved') }}
                            </span>
                        </div>
                    </div>
                    <div class="space-y-4 p-4">
                        <OLevelResultFields
                            v-if="subjectForms[String(row.id)]"
                            :subject-id="String(row.id)"
                            :grade-options="getOptionsForSubject(row)"
                            :errors="subjectErrors[String(row.id)] ?? {}"
                            :is-loading="isLoading"
                            v-model:exam-year="subjectForms[String(row.id)].exam_year"
                            v-model:exam-sitting="subjectForms[String(row.id)].exam_sitting"
                            v-model:grade-id="subjectForms[String(row.id)].grade_id"
                        />
                        <div v-if="!verificationMode" class="pt-1">
                            <BaseButton
                                :size="ButtonSize.sm"
                                :variant="ColorVariant.primary"
                                class="w-full rounded-full sm:w-auto"
                                :title="$t('trans.save')"
                            />
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <Empty v-else-if="formsReady && !isLoading" />
    </PageContainer>
</template>
