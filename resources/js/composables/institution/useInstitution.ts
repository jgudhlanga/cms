import { errorAlert } from '@/lib/alerts';
import About from '@/pages/institution/departments/partials/view/About.vue';
import Announcements from '@/pages/institution/departments/partials/view/Announcements.vue';
import Applications from '@/pages/institution/departments/partials/view/Applications.vue';
import Calendar from '@/pages/institution/departments/partials/view/Calendar.vue';
import Courses from '@/pages/institution/departments/partials/view/Courses.vue';
import Levels from '@/pages/institution/departments/partials/view/Levels.vue';
import Staff from '@/pages/institution/departments/partials/view/Staff.vue';
import HttpService from '@/services/http.service';
import { DepartmentMetaData } from '@/types/department-meta-data';
import { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';

export const useInstitution = () => {
    const isLoading = ref(false);
    const departmentMetaData = ref<DepartmentMetaData | null>(null);

    const loadDepartmentMetaData = async (institutionDepartmentId: string) => {
        try {
            isLoading.value = true;
            departmentMetaData.value = await HttpService.get(`api/v1/departments/${institutionDepartmentId}/metadata`);
        } catch (error: any) {
            console.log(error);
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.department_meta_data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const departmentTabs = (departmentMetaData: DepartmentMetaData): Array<CustomTab> => {
        return [
            { transLabel: () => trans('trans.about'), value: 'about_us', component: h(About) },
            {
                transLabel: () => trans_choice('trans.course', 2),
                value: 'courses',
                component: h(Courses, {
                    institutionDepartmentId: '',
                    departmentCourses: departmentMetaData?.courses,
                    departmentCoursesIds: departmentMetaData?.departmentCoursesIds,
                }),
            },
            {
                transLabel: () => trans_choice('trans.level', 2),
                value: 'levels',
                component: h(Levels, {
                    institutionDepartmentId: '',
                    departmentLevels: departmentMetaData?.levels,
                    departmentLevelsIds: departmentMetaData?.departmentLevelsIds,
                }),
            },
            { transLabel: () => trans_choice('trans.application', 2), value: 'applications', component: Applications },
            { transLabel: () => trans('trans.staff'), value: 'staff', component: Staff },
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
        loadDepartmentMetaData,
        isLoading,
        departmentMetaData,
    };
};
