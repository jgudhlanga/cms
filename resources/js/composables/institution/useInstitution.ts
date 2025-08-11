import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/lib/icons';
import About from '@/pages/institution/departments/partials/view/About.vue';
import Announcements from '@/pages/institution/departments/partials/view/Announcements.vue';
import Calendar from '@/pages/institution/departments/partials/view/Calendar.vue';
import DepartmentSetup from '@/pages/institution/departments/partials/view/DepartmentSetup.vue';
import Enrolments from '@/pages/institution/departments/partials/view/Enrolments.vue';
import Staff from '@/pages/institution/departments/partials/view/Staff.vue';
import { InstitutionDepartment } from '@/types/institution';
import { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h } from 'vue';

export const useInstitution = () => {
    const { isItTrue } = useUtils();
    const departmentTabs = (department: InstitutionDepartment): Array<CustomTab> => {
        return [
            {
                transLabel: () => trans('trans.about'),
                value: 'about_us',
                component: h(About, { department }),
                show: true,
                icon: IconName.info,
            },
            {
                transLabel: () => trans_choice('trans.enrolment', 2),
                value: 'enrolments',
                component: h(Enrolments, { department }),
                show: isItTrue(department?.attributes?.isAcademic),
                icon: IconName.user_add,
            },
            {
                transLabel: () => trans('trans.staff'),
                value: 'staff',
                component: h(Staff, { department }),
                show: true,
                icon: IconName.user_search,
            },
            {
                transLabel: () => trans_choice('trans.calendar', 1),
                value: 'calendar',
                component: Calendar,
                show: true,
                icon: IconName.calendar,
            },
            {
                transLabel: () => trans_choice('trans.announcement', 2),
                value: 'announcements',
                component: Announcements,
                show: true,
                icon: IconName.megaphone,
            },
            {
                transLabel: () => trans('trans.setup'),
                value: 'setup',
                component: h(DepartmentSetup, { department }),
                show: isItTrue(department?.attributes?.isAcademic),
                icon: IconName.settings,
            },
        ];
    };

    return {
        departmentTabs,
    };
};
