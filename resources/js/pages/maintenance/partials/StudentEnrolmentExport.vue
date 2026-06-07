<script setup lang="ts">
import GenericButton from '@/components/core/button/GenericButton.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { successAlert, warningDialog } from '@/lib/alerts';
import { AuthObject } from '@/types/data-pagination';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const page = usePage<{ auth: AuthObject }>();
const defaultRecipientEmail = page.props.auth?.user?.attributes?.email ?? '';

const form = useForm({
    intake_year: '',
    recipient_emails: defaultRecipientEmail,
});

const recipientEmailsError = computed(() => {
    if (form.errors.recipient_emails) {
        return form.errors.recipient_emails;
    }

    return Object.entries(form.errors)
        .filter(([key]) => key.startsWith('recipient_emails.'))
        .map(([, message]) => message)
        .join(' ');
});

const submitEnrollmentExport = () => {
    form.post(route('maintenance.exports.student-enrollment'), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert(trans('trans.maintenance_export_queued_message'));
            form.reset('intake_year');
        },
    });
};

const submitApplicationExport = () => {
    form.post(route('maintenance.exports.application'), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert(trans('trans.maintenance_export_application_queued_message'));
            form.reset('intake_year');
        },
    });
};

const confirmEnrollmentExport = () => {
    warningDialog(
        () => {
            submitEnrollmentExport();
            return true;
        },
        trans('trans.maintenance_export_confirm_message'),
        trans('trans.warning'),
        trans('trans.maintenance_export_student_enrolments'),
    );
};

const confirmApplicationExport = () => {
    warningDialog(
        () => {
            submitApplicationExport();
            return true;
        },
        trans('trans.maintenance_export_application_confirm_message'),
        trans('trans.warning'),
        trans('trans.maintenance_export_applications'),
    );
};
</script>

<template>
    <div class="max-w-2xl space-y-6">
        <div class="space-y-2">
            <BaseInput
                v-model="form.recipient_emails"
                name="recipient_emails"
                :label="trans('trans.maintenance_export_recipient_emails_label')"
                :placeholder="trans('trans.maintenance_export_recipient_emails_placeholder')"
                :error="recipientEmailsError"
            />
            <p class="text-sm text-muted-foreground">
                {{ trans('trans.maintenance_export_recipient_emails_help') }}
            </p>
        </div>

        <BaseInput
            v-model="form.intake_year"
            name="intake_year"
            :label="trans('trans.maintenance_intake_year_label')"
            :placeholder="trans('trans.maintenance_intake_year_placeholder')"
            :error="form.errors.intake_year"
        />

        <HeadingSmall
            :title="trans('trans.maintenance_export_student_enrolments')"
            :description="trans('trans.maintenance_export_student_enrolments_description')"
        />

        <GenericButton
            :icon="IconName.export"
            :variant="ColorVariant.primary_outline"
            :title="trans('trans.maintenance_export_student_enrolments')"
            :disabled="form.processing"
            @click="confirmEnrollmentExport"
        />

        <CustomSeparator classes="h-1 my-5" />

        <HeadingSmall
            :title="trans('trans.maintenance_export_applications')"
            :description="trans('trans.maintenance_export_applications_description')"
        />

        <GenericButton
            :icon="IconName.export"
            :variant="ColorVariant.primary_outline"
            :title="trans('trans.maintenance_export_applications')"
            :disabled="form.processing"
            @click="confirmApplicationExport"
        />
    </div>
</template>
