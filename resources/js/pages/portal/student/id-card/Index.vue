<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { TextFieldType } from '@/enums/inputs';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert } from '@/lib/alerts';
import { formatFeeClearanceUsd } from '@/lib/feeClearanceMoney';
import IdCardPreviewStack from '@/pages/portal/student/id-card/partials/IdCardPreviewStack.vue';
import type { AuthObject } from '@/types/data-pagination';
import type { StudentIdCardFace, StudentIdCardRequestPayload } from '@/types/id-cards';
import type { Student } from '@/types/students';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { SelectOption } from '@/types/utils';
import axios from 'axios';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

interface Props {
    student: Student;
    cardFace: StudentIdCardFace;
    requests: StudentIdCardRequestPayload[];
    latestRequest: StudentIdCardRequestPayload | null;
    latestPhotoUrl: string | null;
    hasPhoto: boolean;
    hasStudentNumber: boolean;
    canSubmit: boolean;
    reasons: Array<{ value: string; label: string }>;
    feeAmount: number;
    feeTypeId: number | null;
    auth: AuthObject;
    errors: Record<string, string>;
}

const props = defineProps<Props>();
const { generateRandomCode, formatDate } = useUtils();
const isPaying = ref(false);
const photoPreview = ref<string | null>(null);

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transChoiceKey: 'dashboard', href: route('portal.dashboard') },
    { transKey: 'trans.student_id_card' },
];

const photoForm = useForm({
    photo: null as File | null,
});

const requestForm = useForm({
    reason: 'new',
    notes: '',
});

const reasonOptions = computed<SelectOption[]>(() =>
    props.reasons.map((reason) => ({
        label: reason.label,
        value: reason.value,
    })),
);

const requiresFee = computed(() => requestForm.reason !== 'new');
const previewPhotoUrl = computed(() => photoPreview.value ?? props.latestPhotoUrl);
const latest = computed(() => props.latestRequest);
const awaitingPayment = computed(() => latest.value?.status === 'awaiting_payment');
const history = computed(() => props.requests ?? []);

const handlePhotoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
        return;
    }

    photoForm.photo = file;
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }
    photoPreview.value = URL.createObjectURL(file);
};

const uploadPhoto = () => {
    photoForm.post(route('portal.id-card.photo'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            photoForm.reset();
        },
    });
};

const submitRequest = () => {
    requestForm.post(route('portal.id-card.store'), {
        preserveScroll: true,
    });
};

const paymentErrorMessage = (error: unknown): string => {
    const data = axios.isAxiosError(error) ? error.response?.data : null;
    if (data && typeof data === 'object') {
        const message = (data as { responseMessage?: string; message?: string }).responseMessage
            ?? (data as { message?: string }).message;
        if (typeof message === 'string' && message.trim() !== '') {
            return message;
        }
    }

    return trans('trans.payment_error_description');
};

const payFee = async () => {
    if (!props.feeTypeId || !latest.value) {
        return;
    }

    try {
        isPaying.value = true;
        const response = await axios.post(route('integrations.payments.initiate'), {
            orderReference: generateRandomCode('ORD'),
            feeTypeId: props.feeTypeId,
            ledgerableId: latest.value.id,
            amount: String(props.feeAmount),
            itemName: trans('trans.student_id_card'),
            itemDescription: trans('trans.student_id_card'),
            currencyCode: 'USD',
            firstName: props.auth.user.attributes.firstname ?? '',
            lastName: props.auth.user.attributes.lastname ?? '',
            email: props.auth.user.attributes.email ?? '',
        });

        if (response.data.paymentUrl) {
            window.location.href = response.data.paymentUrl;
        } else {
            errorAlert(response.data.responseMessage || trans('trans.payment_error_description'));
        }
    } catch (error: unknown) {
        errorAlert(paymentErrorMessage(error));
    } finally {
        isPaying.value = false;
    }
};
</script>

<template>
    <Head :title="$t('trans.student_id_card')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col gap-6 px-4 py-4">
            <div class="grid w-full items-start gap-6 lg:grid-cols-[minmax(0,1fr)_auto]">
                <div class="space-y-6">
                <div>
                    <h1 class="text-xl font-semibold text-foreground">
                        {{ $t('trans.student_id_card') }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ $t('trans.student_id_card_photo_help') }}
                    </p>
                </div>

                <BaseAlert
                    v-if="!hasStudentNumber"
                    :type="TypeVariant.warning"
                    :description="$t('trans.student_id_card_no_student_number')"
                />
                <BaseAlert
                    v-else-if="!canSubmit && latest && latest.status !== 'issued' && latest.status !== 'rejected'"
                    :type="TypeVariant.info"
                    :description="$t('trans.student_id_card_active_exists')"
                />
                <BaseAlert
                    v-else-if="canSubmit && !hasPhoto"
                    :type="TypeVariant.info"
                    :description="$t('trans.student_id_card_upload_before_submit')"
                />

                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <HeadingSmall :title="$t('trans.student_id_card_photo')" />
                    <div class="mt-4 grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]">
                        <div class="aspect-35/45 overflow-hidden rounded-lg border border-border bg-muted">
                            <img
                                v-if="previewPhotoUrl"
                                :src="previewPhotoUrl"
                                alt=""
                                class="h-full w-full object-cover"
                            >
                        </div>
                        <div class="space-y-3">
                            <BaseInput
                                input-id="id-card-photo"
                                :label="$t('trans.student_id_card_upload_photo')"
                                :type="TextFieldType.file"
                                :error="photoForm.errors.photo || errors.photo"
                                accept="image/jpeg,image/png"
                                @change="handlePhotoChange"
                            />
                            <BaseButton
                                :title="hasPhoto ? $t('trans.student_id_card_replace_photo') : $t('trans.student_id_card_upload_photo')"
                                :processing="photoForm.processing"
                                :disabled="!photoForm.photo || !hasStudentNumber"
                                :size="ButtonSize.sm"
                                @click="uploadPhoto"
                            />
                        </div>
                    </div>
                </section>

                <section v-if="awaitingPayment && latest" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30">
                    <HeadingSmall :title="$t('students.id_card_status_awaiting_payment')" />
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('students.id_card_request_awaiting_payment') }}
                    </p>
                    <p class="mt-2 text-sm font-medium">
                        {{ formatFeeClearanceUsd(latest.feeAmount || feeAmount) }}
                    </p>
                    <BaseButton
                        class="mt-4"
                        :title="$t('trans.student_id_card_pay')"
                        :processing="isPaying"
                        :disabled="!feeTypeId"
                        :size="ButtonSize.sm"
                        @click="payFee"
                    />
                </section>

                <section v-else-if="canSubmit" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <HeadingSmall :title="$t('trans.student_id_card_submit')" />
                    <div class="mt-4 grid gap-4">
                        <BaseSelect
                            v-model="requestForm.reason"
                            :label="$t('trans.student_id_card_reason')"
                            :options="reasonOptions"
                            :is-clearable="false"
                            :error="requestForm.errors.reason"
                        />
                        <BaseAlert
                            v-if="requiresFee"
                            :type="TypeVariant.warning"
                            :description="$t('trans.student_id_card_fee_notice', { amount: formatFeeClearanceUsd(feeAmount) })"
                        />
                        <BaseAlert
                            v-else
                            :type="TypeVariant.info"
                            :description="$t('trans.student_id_card_first_free')"
                        />
                        <div>
                            <Label>{{ $t('trans.student_id_card_notes') }}</Label>
                            <Textarea
                                v-model="requestForm.notes"
                                class="mt-2"
                                :placeholder="$t('trans.student_id_card_notes_placeholder')"
                            />
                            <p v-if="requestForm.errors.notes" class="mt-1 text-sm text-destructive">
                                {{ requestForm.errors.notes }}
                            </p>
                        </div>
                        <BaseButton
                            :title="$t('trans.student_id_card_submit')"
                            :processing="requestForm.processing"
                            :disabled="!hasPhoto || !hasStudentNumber"
                            :size="ButtonSize.sm"
                            @click="submitRequest"
                        />
                        <p v-if="!hasPhoto" class="text-sm text-muted-foreground">
                            {{ $t('students.id_card_photo_required') }}
                        </p>
                    </div>
                </section>
            </div>

            <aside class="w-full min-w-0 space-y-3 lg:sticky lg:top-24 lg:w-85">
                <p class="text-sm font-medium text-muted-foreground">
                    {{ $t('trans.student_id_card_preview') }}
                </p>
                <IdCardPreviewStack
                    :student-name="cardFace.studentName"
                    :student-number="cardFace.studentNumber"
                    :department="cardFace.department"
                    :level="cardFace.level"
                    :course="cardFace.course"
                    :mode="cardFace.mode"
                    :sdp="cardFace.sdp"
                    :residence="cardFace.residence"
                    :expiry-date="cardFace.expiryDate"
                    :photo-url="previewPhotoUrl"
                    :serial-number="latest?.serialNumber"
                    :national-id="cardFace.nationalId"
                    :return-name="cardFace.returnName"
                    :return-address="cardFace.returnAddress"
                    :return-phone="cardFace.returnPhone"
                    :logo-url="cardFace.logoUrl"
                    :institution-name="cardFace.institutionName"
                    :website="cardFace.website"
                    :signature-url="cardFace.signatureUrl"
                />
            </aside>
            </div>

            <section class="w-full rounded-2xl border border-border bg-card p-5 shadow-sm">
                <HeadingSmall :title="$t('trans.student_id_card_history')" />
                <p v-if="history.length === 0" class="mt-3 text-sm text-muted-foreground">
                    {{ $t('trans.student_id_card_empty_history') }}
                </p>
                <ul v-else class="mt-3 divide-y divide-border">
                    <li v-for="item in history" :key="item.id" class="flex items-start justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-medium">{{ item.reasonLabel }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.createdAt ? formatDate(item.createdAt, 'LL') : '' }}
                                <span v-if="item.serialNumber"> · {{ item.serialNumber }}</span>
                            </p>
                            <p v-if="item.rejectionReason" class="mt-1 text-xs text-destructive">
                                {{ item.rejectionReason }}
                            </p>
                        </div>
                        <Badge>
                            {{ item.statusLabel }}
                        </Badge>
                    </li>
                </ul>
            </section>
        </div>
    </PageContainer>
</template>
