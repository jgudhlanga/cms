import About from '@/pages/institution/departments/partials/view/About.vue';
import Announcements from '@/pages/institution/departments/partials/view/Announcements.vue';
import Workflows from '@/pages/institution/departments/partials/view/Workflows.vue';
import Calendar from '@/pages/institution/departments/partials/view/Calendar.vue';
import Courses from '@/pages/institution/departments/partials/view/Courses.vue';
import Levels from '@/pages/institution/departments/partials/view/Levels.vue';
import Staff from '@/pages/institution/departments/partials/view/Staff.vue';
import { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h } from 'vue';
import { InstitutionDepartment } from '@/types/institution';

export const useInstitution = () => {
    const departmentTabs = (department: InstitutionDepartment): Array<CustomTab> => {
        return [
            { transLabel: () => trans('trans.about'), value: 'about_us', component: h(About, {department}) },
            {
                transLabel: () => trans_choice('trans.course', 2),
                value: 'courses',
                component: h(Courses, { department }),
            },
            {
                transLabel: () => trans_choice('trans.level', 2),
                value: 'levels',
                component: h(Levels, { department }),
            },
            {
                transLabel: () => trans('trans.staff'),
                value: 'staff',
                component: h(Staff, { department }),
            },
            {
                transLabel: () => trans_choice('trans.workflow_config', 2),
                value: 'workflow_config',
                component: h(Workflows, { department }),
            },
            { transLabel: () => trans_choice('trans.calendar', 2), value: 'calendar', component: Calendar },
            {
                transLabel: () => trans_choice('trans.announcement', 2),
                value: 'announcements',
                component: Announcements,
            },
        ];
    };

    return {
        departmentTabs,
    };
};
