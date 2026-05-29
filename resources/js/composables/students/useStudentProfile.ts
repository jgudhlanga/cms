import Authentication from '@/components/users/Authentication.vue';
import { IconName } from '@/lib/icons';
import { hasAbility, hasStudentProfile } from '@/lib/permissions';
import Applications from '@/pages/students/components/profile/Applications.vue';
import Documents from '@/pages/students/components/profile/Documents.vue';
import Financials from '@/pages/students/components/profile/Financials.vue';
import Hostels from '@/pages/students/components/profile/Hostels.vue';
import Info from '@/pages/students/components/profile/Info.vue';
import Programs from '@/pages/students/components/profile/Programs.vue';
import type { Student } from '@/types/students';
import type { CustomTab } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, type Component } from 'vue';

export type StudentProfileTabValue =
    | 'basic_info'
    | 'programs'
    | 'applications'
    | 'financials'
    | 'accommodations'
    | 'documents'
    | 'authentication';

export type StudentProfileTabDefinition = {
    value: StudentProfileTabValue;
    icon: IconName;
    /** Disables the tab on the admin student profile only. */
    adminDisabled?: boolean;
    routeName?: string;
    transLabel: () => string;
    portalShow?: () => boolean;
};

const portalRouteNames: Record<StudentProfileTabValue, string> = {
    basic_info: 'portal.profile.personal-information',
    programs: 'portal.profile.programs',
    applications: 'portal.profile.applications',
    financials: 'portal.profile.financials',
    accommodations: 'portal.profile.accommodations',
    documents: 'portal.profile.documents',
    authentication: 'portal.profile.authentication',
};

export const studentProfileTabDefinitions = (): StudentProfileTabDefinition[] => [
    {
        value: 'basic_info',
        icon: IconName.user,
        routeName: portalRouteNames.basic_info,
        transLabel: () => trans('students.personal_information'),
        portalShow: () => hasAbility('manageOwnStudentPersonalDetails:students') && hasStudentProfile(),
    },
    {
        value: 'programs',
        icon: IconName.graduation_cape,
        routeName: portalRouteNames.programs,
        transLabel: () => trans_choice('students.program', 2),
        portalShow: () => hasAbility('manageOwnStudentProgramDetails:students') && hasStudentProfile(),
    },
    {
        value: 'applications',
        icon: IconName.application,
        routeName: portalRouteNames.applications,
        transLabel: () => trans_choice('students.application', 2),
        portalShow: () => hasAbility('manageOwnStudentPersonalDetails:students') && hasStudentProfile(),
    },
    {
        value: 'financials',
        icon: IconName.money,
        routeName: portalRouteNames.financials,
        transLabel: () => trans_choice('students.financial', 2),
        portalShow: () => hasAbility('manageOwnStudentFinancialDetails:students') && hasStudentProfile(),
    },
    {
        value: 'accommodations',
        icon: IconName.bed,
        routeName: portalRouteNames.accommodations,
        transLabel: () => trans_choice('students.accommodation', 2),
        portalShow: () => hasAbility('manageOwnStudentPersonalDetails:students') && hasStudentProfile(),
    },
    {
        value: 'documents',
        icon: IconName.files,
        routeName: portalRouteNames.documents,
        transLabel: () => trans_choice('students.document', 2),
        portalShow: () => hasAbility('manageOwnStudentPersonalDetails:students') && hasStudentProfile(),
    },
    {
        value: 'authentication',
        icon: IconName.shield,
        routeName: portalRouteNames.authentication,
        transLabel: () => trans_choice('students.authentication', 1),
        portalShow: () => hasAbility('manageOwnStudentPersonalDetails:students') && hasStudentProfile(),
    },
];

const tabComponents: Record<StudentProfileTabValue, (student: Student) => Component> = {
    basic_info: (student) => h(Info, { student }),
    programs: (student) => h(Programs, { student }),
    applications: (student) => h(Applications, { student }),
    financials: (student) => h(Financials, { student }),
    accommodations: () => h(Hostels),
    documents: () => h(Documents),
    authentication: (student) => h(Authentication, { user: student?.relationships?.user }),
};

export const useStudentProfile = () => {
    const profileTabDefinitions = studentProfileTabDefinitions;

    const profileTabs = (student: Student): CustomTab[] =>
        profileTabDefinitions().map((definition) => ({
            value: definition.value,
            icon: definition.icon,
            disabled: definition.adminDisabled ?? false,
            transLabel: definition.transLabel,
            component: tabComponents[definition.value](student),
        }));

    const portalProfileTabs = (): StudentProfileTabDefinition[] =>
        profileTabDefinitions().filter((tab) => tab.portalShow?.() !== false && tab.portalShow?.());

    const portalSidebarProfileTabs = (): StudentProfileTabDefinition[] => {
        const tabs = portalProfileTabs().filter((tab) => tab.routeName);
        const authentication = tabs.find((tab) => tab.value === 'authentication');
        const beforeAuthentication = tabs.filter((tab) => tab.value !== 'authentication');

        return authentication ? [...beforeAuthentication, authentication] : beforeAuthentication;
    };

    return {
        profileTabDefinitions,
        profileTabs,
        portalProfileTabs,
        portalSidebarProfileTabs,
    };
};
