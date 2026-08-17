<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { hasAbility } from '@/lib/permissions';
import { buildStudentShowUrl } from '@/lib/studentShowNavigation';
import IdCardPreviewStack from '@/pages/portal/student/id-card/partials/IdCardPreviewStack.vue';
import type { AuthObject } from '@/types/data-pagination';
import type { StudentIdCardRequestPayload } from '@/types/id-cards';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface Props {
    idCardRequest: StudentIdCardRequestPayload;
    auth: AuthObject;
    errors: Record<string, string>;
}

const props = defineProps<Props>();
const { formatDate } = useUtils();
const { open: openConfirm } = useCustomConfirmDialog();

const rejectForm = useForm({
    rejection_reason: '',
});

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'trans.student_id', href: route('admin.students.id-card-requests.index') },
    { title: props.idCardRequest.student.name ?? trans('trans.student_id_card') },
]);

const request = computed(() => props.idCardRequest);
const canReview = computed(
    () => request.value.status === 'pending' && hasAbility('review:student-id-card-requests'),
);
const canPrint = computed(
    () => ['approved', 'printed'].includes(request.value.status) && hasAbility('print:student-id-card-requests'),
);
const canIssue = computed(
    () => request.value.status === 'printed' && hasAbility('issue:student-id-card-requests'),
);
const studentUrl = computed(() => {
    if (!request.value.student.id || !hasAbility('view:students')) {
        return null;
    }

    return buildStudentShowUrl(request.value.student.id, { from: 'students' });
});

const approve = async () => {
    const confirmed = await openConfirm({
        title: trans('trans.student_id_card_approve'),
        message: trans('trans.student_id_card_approve_confirm'),
        note: '',
        confirmText: trans('trans.student_id_card_approve'),
    });

    if (!confirmed) {
        return;
    }

    router.post(route('admin.students.id-card-requests.approve', request.value.id), {}, { preserveScroll: true });
};

const reject = () => {
    rejectForm.post(route('admin.students.id-card-requests.reject', request.value.id), {
        preserveScroll: true,
    });
};

const printCard = () => {
    window.open(route('admin.students.id-card-requests.print', request.value.id), '_blank');
};

const issue = async () => {
    const confirmed = await openConfirm({
        title: trans('trans.student_id_card_issue'),
        message: trans('trans.student_id_card_issue_confirm'),
        note: '',
        confirmText: trans('trans.student_id_card_issue'),
    });

    if (!confirmed) {
        return;
    }

    router.post(route('admin.students.id-card-requests.issue', request.value.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="request.student.name ?? $t('trans.student_id_card')" />

    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('admin.students.id-card-requests.index')"
    >
        <div class="grid w-full items-start gap-6 px-4 py-4 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div class="space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold">
                            {{ request.student.name ?? '—' }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ request.student.studentNumber }}
                            <span v-if="request.student.course"> · {{ request.student.course }}</span>
                        </p>
                    </div>
                    <Badge>{{ request.statusLabel }}</Badge>
                </div>

                <BaseAlert
                    v-if="request.status === 'awaiting_payment'"
                    :type="TypeVariant.warning"
                    :description="$t('students.id_card_request_awaiting_payment')"
                />
                <BaseAlert
                    v-if="request.rejectionReason"
                    :type="TypeVariant.danger"
                    :description="request.rejectionReason"
                />

                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <HeadingSmall :title="$t('trans.student_id_card_photo')" />
                    <div class="mt-4 flex justify-center">
                        <div class="aspect-35/45 w-56 overflow-hidden rounded-lg border border-border bg-muted">
                            <img
                                v-if="request.photoUrl"
                                :src="request.photoUrl"
                                alt=""
                                class="h-full w-full object-cover"
                            >
                            <p v-else class="flex h-full items-center justify-center px-3 text-center text-sm text-muted-foreground">
                                {{ $t('trans.student_id_card_no_photo_admin') }}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.student_id_card_reason') }}</dt>
                            <dd class="font-medium">{{ request.reasonLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.student_id_card_serial') }}</dt>
                            <dd class="font-medium">{{ request.serialNumber || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.student_id_card_reviewed_by') }}</dt>
                            <dd class="font-medium">
                                {{ request.reviewerName || '—' }}
                                <span v-if="request.reviewedAt" class="text-muted-foreground">
                                    · {{ formatDate(request.reviewedAt, 'LL') }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.student_id_card_printed_by') }}</dt>
                            <dd class="font-medium">
                                {{ request.printerName || '—' }}
                                <span v-if="request.printedAt" class="text-muted-foreground">
                                    · {{ formatDate(request.printedAt, 'LL') }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.student_id_card_issued_by') }}</dt>
                            <dd class="font-medium">
                                {{ request.issuerName || '—' }}
                                <span v-if="request.issuedAt" class="text-muted-foreground">
                                    · {{ formatDate(request.issuedAt, 'LL') }}
                                </span>
                            </dd>
                        </div>
                        <div v-if="request.notes" class="sm:col-span-2">
                            <dt class="text-muted-foreground">{{ $t('trans.student_id_card_notes') }}</dt>
                            <dd class="font-medium">{{ request.notes }}</dd>
                        </div>
                    </dl>
                    <BaseButton
                        v-if="studentUrl"
                        class="mt-4"
                        :title="$t('students.view_student')"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.sm"
                        @click="router.visit(studentUrl)"
                    />
                </section>

                <section v-if="canReview || canPrint || canIssue" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex flex-wrap gap-3">
                        <BaseButton
                            v-if="canReview"
                            :title="$t('trans.student_id_card_approve')"
                            :variant="ColorVariant.success"
                            :size="ButtonSize.sm"
                            @click="approve"
                        />
                        <BaseButton
                            v-if="canPrint"
                            :title="$t('trans.student_id_card_print')"
                            :size="ButtonSize.sm"
                            @click="printCard"
                        />
                        <BaseButton
                            v-if="canIssue"
                            :title="$t('trans.student_id_card_issue')"
                            :variant="ColorVariant.success"
                            :size="ButtonSize.sm"
                            @click="issue"
                        />
                    </div>

                    <div v-if="canReview" class="mt-6 space-y-2">
                        <Label>{{ $t('trans.student_id_card_rejection_reason') }}</Label>
                        <Textarea v-model="rejectForm.rejection_reason" />
                        <p v-if="rejectForm.errors.rejection_reason" class="text-sm text-destructive">
                            {{ rejectForm.errors.rejection_reason }}
                        </p>
                        <BaseButton
                            :title="$t('trans.student_id_card_reject')"
                            :variant="ColorVariant.danger_outline"
                            :size="ButtonSize.sm"
                            :processing="rejectForm.processing"
                            :disabled="!rejectForm.rejection_reason"
                            @click="reject"
                        />
                    </div>
                </section>
            </div>

            <aside class="w-full min-w-0 lg:sticky lg:top-24 lg:w-85">
                <IdCardPreviewStack
                    :student-name="request.student.studentName"
                    :student-number="request.student.studentNumber"
                    :department="request.student.department"
                    :level="request.student.level"
                    :course="request.student.course"
                    :mode="request.student.mode"
                    :sdp="request.student.sdp"
                    :residence="request.student.residence"
                    :expiry-date="request.student.expiryDate"
                    :photo-url="request.photoUrl"
                    :serial-number="request.serialNumber"
                    :national-id="request.student.nationalId"
                    :return-name="request.student.returnName"
                    :return-address="request.student.returnAddress"
                    :return-phone="request.student.returnPhone"
                    :logo-url="request.student.logoUrl"
                    :institution-name="request.student.institutionName"
                    :website="request.student.website"
                    :signature-url="request.student.signatureUrl"
                />
            </aside>
        </div>
    </PageContainer>
</template>
