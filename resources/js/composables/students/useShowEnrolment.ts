import StudentAddresses from '@/components/students/tabs/StudentAddresses.vue';
import StudentBasicInfo from '@/components/students/tabs/StudentBasicInfo.vue';
import StudentContacts from '@/components/students/tabs/StudentContacts.vue';
import StudentNextOfKin from '@/components/students/tabs/StudentNextOfKin.vue';
import StudentSponsors from '@/components/students/tabs/StudentSponsors.vue';
import { IconName } from '@/lib/icons';
import { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h } from 'vue';
import StudentPrograms from '@/components/students/tabs/StudentPrograms.vue';

export function useShowEnrolment() {
    const enrolmentTabs = (studentId: string): CustomTab[] => {
        return [
            {
                transLabel: () => trans('trans.basic_info'),
                value: 'basic_info',
                component: h(StudentBasicInfo, {url: route('v1.students.personal', studentId)}),
                icon: IconName.user,
            },
            {
                transLabel: () => trans_choice('trans.enrolment', 2),
                value: 'enrolments',
                component: h(StudentPrograms, {url: route('v1.students.programs', studentId)}),
                icon: IconName.folder,
            },
            {
                transLabel: () => trans_choice('trans.contact', 2),
                value: 'contacts',
                component: h(StudentContacts, {url: route('v1.students.contacts', studentId)}),
                icon: IconName.contact,
            },
            {
                transLabel: () => trans_choice('trans.address', 2),
                value: 'addresses',
                component: h(StudentAddresses, {url: route('v1.students.addresses', studentId)}),
                icon: IconName.address,
            },
            {
                transLabel: () => trans_choice('trans.sponsor', 2),
                value: 'sponsors',
                component: h(StudentSponsors, {url: route('v1.students.sponsors', studentId)}),
                icon: IconName.wallet_cards,
            },
            {
                transLabel: () => trans('trans.next_of_kin'),
                value: 'next_of_kin',
                component: h(StudentNextOfKin, {url: route('v1.students.next-of-kins', studentId)}),
                icon: IconName.open_link,
            },
        ];
    };

    return {
        enrolmentTabs,
    };
}
