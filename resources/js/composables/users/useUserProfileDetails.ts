import type { Address, Contact } from '@/types/shared';
import type { User } from '@/types/users';
import { IconName } from '@/enums/icons';
import { computed, type MaybeRefOrGetter, toValue } from 'vue';

export type ProfileContactRow = {
    transKey: string;
    value: string;
    icon: IconName;
    isEmpty: boolean;
};

export type ProfileFieldRow = {
    transKey: string;
    value: string;
    isEmpty: boolean;
};

const joinAddressParts = (address?: Address | null): string => {
    if (!address?.attributes) {
        return '';
    }

    const parts = [
        address.attributes.address1,
        address.attributes.address2,
        address.attributes.address3,
        address.attributes.address4,
        address.attributes.address5,
        address.attributes.address6,
    ]
        .filter((part) => part && part.trim() !== '')
        .map((part) => part?.trim());

    return [...new Set(parts)].join(', ');
};

export function useUserProfileDetails(user: MaybeRefOrGetter<User>) {
    const location = computed(() => joinAddressParts(toValue(user).relationships?.mainAddress ?? null));

    const personalFields = computed<ProfileFieldRow[]>(() => {
        const currentUser = toValue(user);
        const { attributes } = currentUser;

        return [
            {
                transKey: 'trans.first_name',
                value: attributes.firstname ?? '',
                isEmpty: !attributes.firstname,
            },
            {
                transKey: 'trans.last_name',
                value: attributes.lastname ?? '',
                isEmpty: !attributes.lastname,
            },
            {
                transKey: 'trans.middle_name',
                value: attributes.middleName ?? '',
                isEmpty: !attributes.middleName,
            },
            {
                transKey: 'trans.location',
                value: location.value,
                isEmpty: !location.value,
            },
        ];
    });

    const contactRows = computed<ProfileContactRow[]>(() => {
        const currentUser = toValue(user);
        const mainContact = currentUser.relationships?.mainContact;
        const email = currentUser.attributes.email ?? mainContact?.attributes?.emailAddress ?? '';
        const phone =
            currentUser.attributes.phoneNumber ??
            mainContact?.attributes?.phoneNumber ??
            '';
        const address = location.value;

        return [
            {
                transKey: 'trans.email_address',
                value: email,
                icon: IconName.mail,
                isEmpty: !email,
            },
            {
                transKey: 'trans.phone_number',
                value: phone,
                icon: IconName.phone,
                isEmpty: !phone,
            },
            {
                transKey: 'trans.address',
                value: address,
                icon: IconName.location,
                isEmpty: !address,
            },
        ];
    });

    return {
        personalFields,
        contactRows,
        location,
    };
}
