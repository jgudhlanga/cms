import { IconName } from '@/lib/icons';
import { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import Info from '@/pages/students/components/profile/Info.vue';
import Programs from '@/pages/students/components/profile/Programs.vue';
import Financials from '@/pages/students/components/profile/Financials.vue';
import Hostels from '@/pages/students/components/profile/Hostels.vue';
import Applications from '@/pages/students/components/profile/Applications.vue';
import Authentication from '@/components/users/Authentication.vue';
import Documents from '@/pages/students/components/profile/Documents.vue';
import { h } from 'vue';
import { Student } from '@/types/students';

export const useStudentProfile = () => {
    const profileTabs = (student: Student): Array<CustomTab> => {
        return [
            {
                transLabel: () => trans('students.personal_information'),
                value: 'basic_info',
                component: h(Info, { student }),
                icon: IconName.user,
            },
            {
                transLabel: () => trans_choice('students.program', 2),
                value: 'programs',
                component: h(Programs, { student }),
                icon: IconName.graduation_cape,
            },
            {
                transLabel: () => trans_choice('students.application', 2),
                value: 'applications',
                component: h(Applications),
                icon: IconName.application,
            },
            {
                transLabel: () => trans_choice('students.financial', 2),
                value: 'financials',
                component: h(Financials),
                icon: IconName.money,
                disabled: true,
            },
            {
                transLabel: () => trans_choice('students.accommodation', 2),
                value: 'accommodations',
                component: h(Hostels),
                icon: IconName.bed,
                disabled: true,
            },
            {
                transLabel: () => trans_choice('students.document', 2),
                value: 'documents',
                component: h(Documents),
                icon: IconName.files,
                disabled: true,
            },
            {
                transLabel: () => trans_choice('students.authentication', 1),
                value: 'authentication',
                component: h(Authentication, {user: student?.relationships?.user}),
                icon: IconName.shield,
            },
        ];
    };
    return {
        profileTabs,
    };
};
