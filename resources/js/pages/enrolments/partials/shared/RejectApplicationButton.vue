<script setup lang="ts">
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, forbiddenAlert, successAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import type { ClassListAttributeParams } from '@/types/enrolments';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

const props = defineProps<{
    studentProgramId: string;
    form: InertiaForm<ClassListAttributeParams>;
    requiredAbility: string;
    nextHref: string | null;
}>();

const { navigateTo } = useUtils();

const rejectApplication = async () => {
    if (!hasAbility(props.requiredAbility)) {
        forbiddenAlert();
        return;
    }
    const confirmed = await useCustomConfirmDialog().open({
        title: trans('enrolments.reject_dialog_title'),
        message: trans('enrolments.reject_dialog_message'),
        confirmText: trans('enrolments.confirm_action'),
    });
    if (confirmed) {
        props.form.put(route('enrolments.reject-application', { student_program: props.studentProgramId }), {
            onSuccess: () => {
                successAlert(trans('enrolments.success_rejected'));
                if (props.nextHref) {
                    navigateTo(props.nextHref);
                }
            },
            onError: (errors: Record<string, string | string[]>) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert(trans('enrolments.error_reject_unexpected'));
                }
            },
        });
    }
};
</script>

<template>
    <BaseButton :variant="ColorVariant.danger" :title="$t('enrolments.reject_button_title')" @click="rejectApplication" />
</template>
