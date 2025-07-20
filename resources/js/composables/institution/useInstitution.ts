import { useUtils } from '@/composables/core/useUtils';
import About from '@/pages/institution/departments/partials/view/About.vue';
import Announcements from '@/pages/institution/departments/partials/view/Announcements.vue';
import Calendar from '@/pages/institution/departments/partials/view/Calendar.vue';
import Courses from '@/pages/institution/departments/partials/view/Courses.vue';
import Levels from '@/pages/institution/departments/partials/view/Levels.vue';
import Staff from '@/pages/institution/departments/partials/view/Staff.vue';
import Workflows from '@/pages/institution/departments/partials/view/Workflows.vue';
import { InstitutionDepartment } from '@/types/institution';
import { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h } from 'vue';
import { IconName } from '@/lib/icons';

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
                transLabel: () => trans_choice('trans.course', 2),
                value: 'courses',
                component: h(Courses, { department }),
                show: isItTrue(department?.attributes?.isAcademic),
                icon: IconName.bookmark,
            },
            {
                transLabel: () => trans_choice('trans.level', 2),
                value: 'levels',
                component: h(Levels, { department }),
                show: isItTrue(department?.attributes?.isAcademic),
                icon: IconName.route,
            },
            {
                transLabel: () => trans('trans.staff'),
                value: 'staff',
                component: h(Staff, { department }),
                show: true,
                icon: IconName.user_search,
            },
            {
                transLabel: () => trans_choice('trans.workflow_config', 2),
                value: 'workflow_config',
                component: h(Workflows, { department }),
                show: isItTrue(department?.attributes?.isAcademic),
                icon: IconName.cogs,
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
        ];
    };

    return {
        departmentTabs,
    };
};
