<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useUtils } from '@/composables/core/useUtils';
import { useAddresses } from '@/composables/shared/useAddresses';
import { useContacts } from '@/composables/shared/useContacts';
import { useNextOfKin } from '@/composables/shared/useNextOfKin';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { errorAlert, successAlert } from '@/lib/alerts';
import { DISABILITY_OPTIONS } from '@/lib/constants';
import { icons } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { isValidZimbabweanIdNumber } from '@/lib/zimbabweanId';
import HttpService from '@/services/http.service';
import { Address, Contact } from '@/types/shared';
import { NextOfKin } from '@/types/next-of-kin';
import { PageProps } from '@/types';
import { Student } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

function formatAddressLines(...parts: Array<string | null | undefined>): string {
    const uniqueParts = [
        ...new Set(
            parts
                .filter((part) => part && part.trim() !== '')
                .map((part) => part?.trim() as string),
        ),
    ];

    return uniqueParts.join(', ');
}

function formatPrimaryAndAlt(
    primary: string | null | undefined,
    alt: string | null | undefined,
    altLabelKey: string,
): string {
    const primaryValue = primary?.trim() ?? '';
    const altValue = alt?.trim() ?? '';

    if (primaryValue && altValue) {
        return `${primaryValue}\n${trans(altLabelKey)}: ${altValue}`;
    }

    if (primaryValue) {
        return primaryValue;
    }

    if (altValue) {
        return `${trans(altLabelKey)}: ${altValue}`;
    }

    return '---';
}

interface Props {
    student: Student;
    context?: 'admin' | 'portal';
}

const props = withDefaults(defineProps<Props>(), {
    context: 'admin',
});
const student = computed(() => props.student);
const page = usePage<PageProps>();

const { isNativeCitizen, formatDate, formatZimIdNumber } = useUtils();
const { onOpenPersonalDetailsModal } = useStudentPortal();
const { onOpenModal: onOpenContactModal } = useContacts();
const { onOpenModal: onOpenAddressModal } = useAddresses();
const { onOpenModal: onOpenNextOfKinModal } = useNextOfKin();

const canEditPersonal = hasAbility('manageOwnStudentPersonalDetails:students');
const canUpdateStudents = hasAbility('update:students');
const canEditContactDetails = hasAbility([
    'create:contacts',
    'update:contacts',
    'manageOwnStudentContactDetails:students',
]);
const canEditAddress = hasAbility([
    'create:addresses',
    'update:addresses',
    'manageOwnStudentContactDetails:students',
]);
const canEditNextOfKin = hasAbility([
    'create:next-of-kins',
    'update:next-of-kins',
    'manageOwnStudentContactDetails:students',
]);

const isProfileOwner = computed(() => {
    const authStudentId = page.props.auth?.user?.attributes?.studentId;

    return authStudentId != null && String(authStudentId) === String(student.value?.id);
});

const canCorrectIdNumber = computed(
    () => canUpdateStudents || (isProfileOwner.value && canEditPersonal),
);

const canEditProfile = computed(
    () =>
        props.context === 'portal'
        && (canEditPersonal || canEditContactDetails || canEditAddress || canEditNextOfKin),
);

const isZimbabweanId = computed(() => isNativeCitizen(student.value?.attributes?.idType ?? ''));

const idNumberFixedLocally = ref(false);

const isIdNumberInvalid = computed(
    () =>
        isZimbabweanId.value
        && student.value?.attributes?.idNumberValid === false
        && !idNumberFixedLocally.value,
);

const suggestedIdNumber = computed(() => student.value?.attributes?.suggestedIdNumber ?? null);

const draftIdNumber = ref('');
const isSavingIdNumber = ref(false);

watch(
    () => student.value?.attributes?.idNumberValid,
    (valid) => {
        if (valid === true) {
            idNumberFixedLocally.value = true;
        } else if (valid === false) {
            idNumberFixedLocally.value = false;
        }
    },
    { immediate: true },
);

watch(
    () => [student.value?.attributes?.idNumber, suggestedIdNumber.value] as const,
    ([idNumber, suggested]) => {
        draftIdNumber.value = suggested ?? idNumber ?? '';
    },
    { immediate: true },
);

const onDraftIdNumberUpdate = (value: string | number) => {
    draftIdNumber.value = formatZimIdNumber(String(value)) ?? String(value);
};

const useSuggestedIdNumber = () => {
    if (suggestedIdNumber.value) {
        draftIdNumber.value = suggestedIdNumber.value;
    }
};

const saveIdNumber = async () => {
    if (isSavingIdNumber.value || !student.value?.id) {
        return;
    }

    const draft = draftIdNumber.value.trim();
    const original = (student.value?.attributes?.idNumber ?? '').trim();

    if (!draft || draft === original) {
        errorAlert(trans('trans.enrollment_invalid_national_id'));

        return;
    }

    if (!isValidZimbabweanIdNumber(draft)) {
        errorAlert(trans('trans.enrollment_invalid_national_id'));

        return;
    }

    isSavingIdNumber.value = true;

    try {
        await HttpService.patch(route('students.id-number.update', student.value.id), {
            id_number: draft,
        });
        idNumberFixedLocally.value = true;
        if (student.value.attributes) {
            student.value.attributes.idNumber = draft;
            student.value.attributes.idNumberValid = true;
            student.value.attributes.suggestedIdNumber = null;
        }
        successAlert(trans('trans.maintenance_faulty_data_fix_success'));
        router.visit(window.location.href, {
            replace: true,
            preserveScroll: true,
            preserveState: false,
        });
    } catch (error: unknown) {
        const axiosError = error as {
            response?: { data?: { message?: string; errors?: Record<string, string[]> } };
        };
        const message =
            axiosError.response?.data?.errors?.id_number?.[0]
            ?? axiosError.response?.data?.message
            ?? trans('trans.maintenance_faulty_data_fix_failure');
        errorAlert(message);
    } finally {
        isSavingIdNumber.value = false;
    }
};

const mainContact = computed((): Contact | undefined => {
    const contact = student.value?.relationships?.mainContact;
    return contact?.id ? contact : undefined;
});

const mainAddress = computed((): Address | undefined => {
    const address = student.value?.relationships?.mainAddress;
    return address?.id ? address : undefined;
});

const nextOfKin = computed((): NextOfKin | undefined => {
    const kin = student.value?.relationships?.nextOfKin;
    return kin?.id ? kin : undefined;
});

const personalDetails = computed<ValueAndLabel[]>(() => {
    const details: ValueAndLabel[] = [
        { transChoiceKey: 'trans.student_number', value: student.value?.attributes?.studentNumber ?? '' },
        { transChoiceKey: 'trans.title', value: student.value?.attributes?.title ?? '' },
        { transChoiceKey: 'trans.gender', value: student.value?.attributes?.gender ?? '' },
        { transChoiceKey: 'trans.marital_status', value: student.value?.attributes?.maritalStatus ?? '' },
        { transChoiceKey: 'trans.id_type', value: student.value?.attributes?.idType ?? '' },
    ];
    if (isZimbabweanId.value) {
        if (!isIdNumberInvalid.value || !canCorrectIdNumber.value) {
            details.push({
                transKey: 'trans.id_number',
                value: student.value?.attributes?.idNumber ?? '',
            });
        }
    } else {
        details.push(
            { transKey: 'trans.passport_number', value: student.value?.attributes?.passportNumber ?? '' },
            { transChoiceKey: 'trans.country', value: student.value?.attributes?.country ?? '' },
        );
    }
    details.push({
        transKey: 'trans.date_of_birth',
        value: formatDate(student.value?.attributes?.dateOfBirth ?? ''),
    });
    details.push({
        transKey: 'trans.disability',
        value:
            DISABILITY_OPTIONS.find((option) => option.value === student.value?.attributes?.disabilityStatus)?.label ??
            '',
    });
    details.push(
        { transChoiceKey: 'trans.race', value: student.value?.attributes?.race ?? '' },
        { transChoiceKey: 'trans.religion', value: student.value?.attributes?.religion ?? '' },
        { transChoiceKey: 'trans.denomination', value: student.value?.attributes?.denomination ?? '' },
        { transKey: 'trans.weight', value: student.value?.attributes?.weight ?? '' },
        { transKey: 'trans.height', value: student.value?.attributes?.height ?? '' },
    );

    return details;
});

const formattedMainAddress = computed(() => {
    const address = student.value?.relationships?.mainAddress?.attributes;

    if (!address) return '';

    return formatAddressLines(
        address.address1,
        address.address2,
        address.address3,
        address.address4,
        address.address5,
        address.address6,
    );
});

const formattedNextOfKinAddress = computed(() => {
    const address = student.value?.relationships?.nextOfKin?.attributes;

    if (!address) return '';

    return formatAddressLines(address.address1, address.address2, address.address3, address.address4);
});

const otherDetails = computed<ValueAndLabel[]>(() => {
    const contact = student.value?.relationships?.mainContact?.attributes;
    const userEmail = student.value?.relationships?.user?.attributes?.email;

    return [
        {
            transKey: 'students.phone',
            value: formatPrimaryAndAlt(contact?.phoneNumber, contact?.altPhoneNumber, 'trans.alt_phone_number'),
            icon: IconName.phone,
        },
        {
            transKey: 'students.email',
            value: formatPrimaryAndAlt(
                contact?.emailAddress ?? userEmail,
                contact?.altEmailAddress,
                'trans.alt_email_address',
            ),
            icon: IconName.mail,
        },
        {
            transKey: 'students.home_address',
            value: formattedMainAddress.value || '---',
            icon: IconName.house,
        },
        {
            transKey: 'students.guardian',
            value: student.value?.relationships?.nextOfKin?.attributes?.name ?? '---',
            icon: IconName.user,
        },
        {
            transKey: 'students.guardian_contact',
            value: student.value?.relationships?.nextOfKin?.attributes?.phoneNumber ?? '---',
            icon: IconName.phone,
        },
        {
            transKey: 'students.guardian_address',
            value: formattedNextOfKinAddress.value || '---',
            icon: IconName.house,
        },
    ];
});
</script>

<template>
    <div v-if="canEditProfile" class="flex justify-end pb-2">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <BaseButton :variant="ColorVariant.primary" :size="ButtonSize.sm">
                    {{ $t('trans.edit_profile') }}
                    <component :is="icons[IconName.chevron_down]" class="ml-1 size-4" />
                </BaseButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuGroup>
                    <DropdownMenuItem v-if="canEditPersonal" as-child>
                        <button
                            type="button"
                            class="w-full cursor-pointer"
                            @click="onOpenPersonalDetailsModal(student)"
                        >
                            {{ $t('trans.personal_details') }}
                        </button>
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="canEditContactDetails" as-child>
                        <button type="button" class="w-full cursor-pointer" @click="onOpenContactModal(mainContact)">
                            {{ $t('trans.contact_details') }}
                        </button>
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="canEditAddress" as-child>
                        <button type="button" class="w-full cursor-pointer" @click="onOpenAddressModal(mainAddress)">
                            {{ $t('students.home_address') }}
                        </button>
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="canEditNextOfKin" as-child>
                        <button type="button" class="w-full cursor-pointer" @click="onOpenNextOfKinModal(nextOfKin)">
                            {{ $t('trans.next_of_kin') }}
                        </button>
                    </DropdownMenuItem>
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>

    <div
        v-if="isIdNumberInvalid"
        class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-950 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100"
    >
        <p class="font-medium">{{ $t('trans.id_number_invalid_warning') }}</p>
        <p class="mt-1 font-mono text-sm font-semibold text-red-700 dark:text-red-300">
            {{ student?.attributes?.idNumber || '—' }}
        </p>
        <p class="mt-1 text-xs opacity-90">{{ $t('trans.enrollment_invalid_national_id') }}</p>
        <div v-if="canCorrectIdNumber" class="mt-3 flex flex-col gap-2">
            <p class="text-[0.65rem] font-semibold tracking-widest text-muted-foreground uppercase">
                {{ $t('trans.id_number') }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <Input
                    :model-value="draftIdNumber"
                    name="student_id_number_correction"
                    class="min-w-[140px] max-w-sm flex-1 bg-background"
                    :placeholder="$t('trans.ui_eg_63_1234567n63')"
                    :disabled="isSavingIdNumber"
                    @update:model-value="onDraftIdNumberUpdate"
                />
                <button
                    v-if="suggestedIdNumber && draftIdNumber.trim() !== suggestedIdNumber"
                    type="button"
                    class="shrink-0 text-xs font-medium text-primary cursor-pointer disabled:opacity-50"
                    :disabled="isSavingIdNumber"
                    @click="useSuggestedIdNumber"
                >
                    {{ $t('trans.maintenance_faulty_data_use_suggested') }}
                </button>
                <BaseButton
                    :title="$t('trans.save')"
                    :variant="ColorVariant.danger"
                    :size="ButtonSize.sm"
                    type="button"
                    classes="shrink-0 rounded-full capitalize"
                    :processing="isSavingIdNumber"
                    :disabled="isSavingIdNumber"
                    @click="saveIdNumber"
                />
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 py-4 sm:grid-cols-2 sm:gap-x-4 lg:grid-cols-3 lg:gap-x-6">
        <LabelValue
            v-for="(field, idx) in personalDetails"
            :key="idx"
            :label="field.transKey ? $t(field.transKey) : $tChoice(field.transChoiceKey ?? '', 1)"
            :value="field.value"
        />
    </div>
    <Separator class="my-6" />
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-x-4 lg:grid-cols-3 lg:gap-x-6">
        <InfoCard
            v-for="(field, idx) in otherDetails"
            :key="idx"
            :label="field.transKey ? $t(field.transKey) : $tChoice(field.transChoiceKey ?? '', 1)"
            :value="field.value"
            :icon="field.icon"
        />
    </div>
</template>
