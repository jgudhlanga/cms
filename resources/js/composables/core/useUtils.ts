import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { ACADEMIC_YEAR_START } from '@/lib/constants';
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import moment from 'moment';
import { v4 as uuidv4 } from 'uuid';
import { h } from 'vue';
import { BreadcrumbItemInterface } from '@/types/ui';

export function useUtils() {
    const renderIcon = (icon: IconName, size: string = '15', color?: ColorVariant) => {
        return () =>
            h(BaseIcon, {
                name: icon,
                color: color,
                size: size,
            });
    };
    const formatDate = (date: Date | string, format = 'L') => {
        return moment(date).format(format);
    };
    const formatRelativeDate = (date: Date | string) => {
        return moment(date).fromNow();
    };
    const getAgeFromDate = (date: Date) => {
        return moment().diff(date, 'years');
    };

    const getStartOfNextMonth = () => {
        return moment().add(1, 'M').startOf('month').format('L');
    };

    const getAnniversaryDate = (date: Date | string, format = 'LL') => {
        return moment(date).add(1, 'year').format(format);
    };

    const getSomeYearsAgoDate = (yearsBack: number) => {
        const currentDate = new Date();
        return currentDate.setFullYear(currentDate.getFullYear() - yearsBack);
    };
    const getBirthdayFromID = (idNumber: any) => {
        const tempDate = new Date(idNumber.substring(0, 2), idNumber.substring(2, 4) - 1, idNumber.substring(4, 6));
        const id_date = pad(tempDate.getDate(), 2);
        const id_month = tempDate.getMonth();
        pad(tempDate.getFullYear(), 4);
        // Fix for id number in 2000+
        const min = 16;
        const date = new Date();
        const fullYear = date.getFullYear();

        // older than 16
        let age = fullYear - 1900 - idNumber.substring(0, 2);
        let year = fullYear - age;

        if (idNumber.substring(0, 2) <= min) {
            // 16 and younger
            age = fullYear - 2000 - idNumber.substring(0, 2);
            year = fullYear - age;
        }

        return year + '-' + pad(id_month + 1, 2) + '-' + id_date;
    };

    const getGenderFromID = (idNumber: any) => {
        const genderCode = idNumber.substring(6, 10);
        return parseInt(genderCode) < 5000 ? 'F' : 'M';
    };

    function numberInRange(value: number, first: number, second: number) {
        const max = Math.max(first, second);
        const min = Math.min(first, second);
        return value >= min && value <= max;
    }

    const formatCurrency = (amount?: string) => {
        if (!amount) return '---';
        const currency = Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
        });
        return currency.format(+amount!);
    };

    const baseInputNumberCurrencyFormat = {
        style: 'currency',
        currency: 'ZAR',
        currencyDisplay: 'narrowSymbol',
        currencySign: 'accounting',
    };

    function pad(str: any, max: any): any {
        str = str.toString();
        return str.length < max ? pad('0' + str, max) : str;
    }

    const isSACitizen = (idNumber: any) => {
        return parseInt(idNumber.substring(10, 11)) == 0 ? trans('trans.yes') : trans('trans.no');
    };

    const performAction = async (allowed: boolean, action: () => void) => {
        if (allowed) {
            action();
        } else {
        }
    };

    const yesOrNo = (value: boolean) => {
        return value ? trans('trans.yes') : trans('trans.no');
    };
    const isItTrue = (value: any): boolean => {
        return value?.toString() === '1' || value?.toString() === 'true' || value?.toString() === 'yes';
    };

    const extractInitials = (word: string, isNumber?: boolean) => {
        if (isNumber) return word;
        const strArray = word.split(' ');
        let initials = '';
        for (let i = 0; i < strArray.length; i++) {
            initials += strArray[i][0];
        }
        return initials.toUpperCase();
    };

    const goBack = () => {
        window.history.back();
    };

    const navigateTo = (url: string) => {
        router.get(url);
    };

    const formatZimIdNumber = (id_number: string) => {
        if (!id_number) return;

        let raw = id_number.replace(/-/g, '');

        if (raw.length > 1) {
            raw = raw.slice(0, 2) + '-' + raw.slice(2);
        }

        return raw;
    };

    const isNativeCitizen = (idType: string): boolean => {
        return idType.toLowerCase() == 'zimbabwean national id';
    };

    const generateRandomCode = (prefix: string): string => {
        return `${prefix}-${uuidv4().replace(/-/g, '').substring(0, 8).toUpperCase()}`;
    };

    const getQueryParams = () => {
        const url = new URL(usePage().url, window.location.origin);
        return Object.fromEntries(url.searchParams.entries());
    };

    const nextYear = new Date(ACADEMIC_YEAR_START, 0, 1).getFullYear() + 1;
    const numberOfYears = nextYear - ACADEMIC_YEAR_START;
    const academicYears = Array.from({ length: numberOfYears }, (_, i) => {
        const year = ACADEMIC_YEAR_START + i;
        return {
            id: `${year + 1}`,
            name: `${year + 1}`,
        };
    });

    const getTransFile = (breadcrumbItem: Pick<BreadcrumbItemInterface, 'transKey' | 'transChoiceKey'>): string => {
        const key = breadcrumbItem.transKey ?? breadcrumbItem.transChoiceKey;

        if (!key) return '';

        const [file, group] = key.split('.');

        return group ? `${file}.${group}` : `trans.${file}`;
    };

    return {
        extractInitials,
        formatCurrency,
        formatDate,
        formatRelativeDate,
        getAgeFromDate,
        getAnniversaryDate,
        getBirthdayFromID,
        getGenderFromID,
        getSomeYearsAgoDate,
        getStartOfNextMonth,
        isItTrue,
        isSACitizen,
        numberInRange,
        performAction,
        renderIcon,
        yesOrNo,
        baseInputNumberCurrencyFormat,
        goBack,
        navigateTo,
        formatZimIdNumber,
        isNativeCitizen,
        generateRandomCode,
        getQueryParams,
        academicYears,
        getTransFile,
    };
}
