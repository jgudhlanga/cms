import { IconName } from '@/enums/icons';
import StaffTab from '@/pages/maintenance/partials/StaffTab.vue';
import StudentEnrolmentExport from '@/pages/maintenance/partials/StudentEnrolmentExport.vue';
import UsersTab from '@/pages/maintenance/partials/UsersTab.vue';
import { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h } from 'vue';

export function useMaintenance() {
    const maintenanceTabs = (): CustomTab[] => [
        {
            transLabel: () => trans_choice('trans.user', 2),
            value: 'users',
            component: h(UsersTab),
            icon: IconName.users,
            show: true,
        },
        {
            transLabel: () => trans('trans.staff'),
            value: 'staff',
            component: h(StaffTab),
            icon: IconName.briefcase,
            show: true,
        },
        {
            transLabel: () => trans_choice('trans.student', 2),
            value: 'students',
            component: h(StudentEnrolmentExport),
            icon: IconName.user_check,
            show: true,
        },
    ];

    return { maintenanceTabs };
}
