import AboutUs from '@/pages/institution/departments/partials/view/AboutUs.vue';
import Announcements from '@/pages/institution/departments/partials/view/Announcements.vue';
import Calendar from '@/pages/institution/departments/partials/view/Calendar.vue';
import Classes from '@/pages/institution/departments/partials/view/Classes.vue';
import Courses from '@/pages/institution/departments/partials/view/Courses.vue';
import Levels from '@/pages/institution/departments/partials/view/Levels.vue';
import ProvisionalClasses from '@/pages/institution/departments/partials/view/ProvisionalClasses.vue';
import Staff from '@/pages/institution/departments/partials/view/Staff.vue';
import { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h } from 'vue';

export const useInstitution = () => {
    const constDepartmentTabs: Array<CustomTab> = [
        { transLabel: () => trans('trans.about'), value: 'about_us', component: h(AboutUs) },
        { transLabel: () => trans_choice('trans.course', 2), value: 'courses', component: Courses },
        { transLabel: () => trans_choice('trans.level', 2), value: 'levels', component: Levels },
        { transLabel: () => trans('trans.staff'), value: 'staff', component: Staff },
        {
            transLabel: () => trans_choice('trans.provisional_class', 2),
            value: 'provisional_classes',
            component: ProvisionalClasses,
        },
        { transLabel: () => trans_choice('trans.class', 2), value: 'classes', component: Classes },
        { transLabel: () => trans_choice('trans.calendar', 2), value: 'calendar', component: Calendar },
        { transLabel: () => trans_choice('trans.announcement', 2), value: 'announcements', component: Announcements },
    ];
    return {
        constDepartmentTabs,
    };
};
