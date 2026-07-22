<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import { Separator } from '@/components/ui/separator';
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
import { DISABILITY_OPTIONS } from '@/lib/constants';
import { icons } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { Address, Contact } from '@/types/shared';
import { NextOfKin } from '@/types/next-of-kin';
import { Student } from '@/types/students';
import { ValueAndLabel } from '@/types/utils';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

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

const { isNativeCitizen, formatDate } = useUtils();
const { onOpenPersonalDetailsModal } = useStudentPortal();
const { onOpenModal: onOpenContactModal } = useContacts();
const { onOpenModal: onOpenAddressModal } = useAddresses();
const { onOpenModal: onOpenNextOfKinModal } = useNextOfKin();

const canEditPersonal = hasAbility('manageOwnStudentPersonalDetails:students');
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

const canEditProfile = computed(
    () =>
        props.context === 'portal'
        && (canEditPersonal || canEditContactDetails || canEditAddress || canEditNextOfKin),
);

const isZimbabweanId = computed(() => isNativeCitizen(student.value?.attributes?.idType ?? ''));

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
        details.push({
            transKey: 'trans.id_number',
            value: student.value?.attributes?.idNumber ?? '',
        });
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
