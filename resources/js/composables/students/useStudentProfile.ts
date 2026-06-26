import Authentication from '@/components/users/Authentication.vue';
import Applications from '@/pages/students/components/profile/Applications.vue';
import Documents from '@/pages/students/components/profile/Documents.vue';
import Financials from '@/pages/students/components/profile/Financials.vue';
import Hostels from '@/pages/students/components/profile/Hostels.vue';
import Info from '@/pages/students/components/profile/Info.vue';
import Programs from '@/pages/students/components/profile/Programs.vue';
import type { Student } from '@/types/students';
import type { CustomTab } from '@/types/utils';
import { h, type Component } from 'vue';
import {
    portalProfileTabs,
    portalSidebarProfileTabs,
    studentProfileTabDefinitions,
    type StudentProfileTabValue,
} from '@/composables/students/useStudentProfileTabs';

export type {
    StudentProfileTabContext,
    StudentProfileTabDefinition,
    StudentProfileTabValue,
} from '@/composables/students/useStudentProfileTabs';

export {
    isStudentProfileTabVisible,
    portalSidebarProfileTabs,
    studentProfileTabDefinitions,
} from '@/composables/students/useStudentProfileTabs';

const tabComponents: Record<StudentProfileTabValue, (student: Student, options?: { activeIntakePeriodIds?: Array<string | number> }) => Component> = {
    basic_info: (student) => h(Info, { student }),
    programs: (student) => h(Programs, { student }),
    applications: (student, options) => h(Applications, { student, activeIntakePeriodIds: options?.activeIntakePeriodIds }),
    financials: (student) => h(Financials, { student }),
    accommodations: (student) => h(Hostels, { student }),
    documents: () => h(Documents),
    authentication: (student) => h(Authentication, { user: student?.relationships?.user }),
};

export const useStudentProfile = () => {
    const profileTabs = (
        student: Student,
        options?: { activeIntakePeriodIds?: Array<string | number> },
    ): CustomTab[] =>
        studentProfileTabDefinitions('admin')
            .filter((definition) => definition.show ?? false)
            .map((definition) => ({
                value: definition.value,
                icon: definition.icon,
                show: definition.show ?? false,
                transLabel: definition.transLabel,
                component: tabComponents[definition.value](student, options),
            }));

    return {
        profileTabs,
        portalProfileTabs,
        portalSidebarProfileTabs,
    };
};
