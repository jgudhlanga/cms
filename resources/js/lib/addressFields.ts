import { trans } from 'laravel-vue-i18n';

export const ADDRESS_FIELDS = [
    {
        key: 'address_1',
        labelKey: 'trans.address_house_number',
        placeholderKey: 'trans.ui_eg_house_number',
        required: true,
    },
    {
        key: 'address_2',
        labelKey: 'trans.address_street_name',
        placeholderKey: 'trans.ui_eg_street_name',
        required: true,
    },
    {
        key: 'address_3',
        labelKey: 'trans.address_suburb',
        placeholderKey: 'trans.ui_eg_suburb',
        required: true,
    },
    {
        key: 'address_4',
        labelKey: 'trans.address_city_town',
        placeholderKey: 'trans.ui_eg_city_town',
        required: false,
    },
] as const;

export type AddressFieldIndex = 1 | 2 | 3 | 4;

export function addressLabel(index: AddressFieldIndex): string {
    return trans(ADDRESS_FIELDS[index - 1].labelKey);
}

export function addressPlaceholder(index: AddressFieldIndex): string {
    return trans(ADDRESS_FIELDS[index - 1].placeholderKey);
}
