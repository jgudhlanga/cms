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
    routeName?: string;
    transLabel: () => string;
    show?:  boolean;
};

const adminStudentAbilities = ['view:students', 'manageStudentMetadata:admin'] as const;
const adminFinanceAbilities = ['view:finances', 'viewAny:finances'] as const;

export type StudentProfileTabContext = 'admin' | 'portal';

const portalRouteNames: Record<StudentProfileTabValue, string> = {
    basic_info: 'portal.profile.personal-information',
    programs: 'portal.profile.programs',
    applications: 'portal.profile.applications',
    financials: 'portal.profile.financials',
    accommodations: 'portal.profile.accommodations',
    documents: 'portal.profile.documents',
    authentication: 'portal.profile.authentication',
};

const tabShowChecks: Record<
    StudentProfileTabValue,
    Record<StudentProfileTabContext, () => boolean>
> = {
    basic_info: {
        admin: () => hasAbility([...adminStudentAbilities]),
        portal: () => hasAbility('manageOwnStudentPersonalDetails:students'),
    },
    programs: {
        admin: () => hasAbility([...adminStudentAbilities]),
        portal: () => hasAbility('manageOwnStudentProgramDetails:students'),
    },
    applications: {
        admin: () => hasAbility([...adminStudentAbilities]),
        portal: () => hasAbility('manageOwnStudentPersonalDetails:students'),
    },
    financials: {
        admin: () => hasAbility([...adminFinanceAbilities]),
        portal: () => hasAbility('manageOwnStudentFinancialDetails:students'),
    },
    accommodations: {
        admin: () => hasAbility([...adminStudentAbilities]),
        portal: () =>
            hasStudentProfile()
            && (
                hasAbility('manageOwnStudentAccommodationDetails:students')
                || hasAbility('manageOwnStudentPersonalDetails:students')
            ),
    },
    documents: {
        admin: () => hasAbility([...adminStudentAbilities]),
        portal: () => hasAbility('manageOwnStudentPersonalDetails:students'),
    },
    authentication: {
        admin: () => hasAbility(['manageOwnStudentPersonalDetails:students', 'root:manage']),
        portal: () => hasAbility('manageOwnStudentPersonalDetails:students'),
    },
};

const studentProfileTabCatalog: Omit<StudentProfileTabDefinition, 'show'>[] = [
    {
        value: 'basic_info',
        icon: IconName.user,
        routeName: portalRouteNames.basic_info,
        transLabel: () => trans('students.personal_information'),
    },
    {
        value: 'programs',
        icon: IconName.graduation_cape,
        routeName: portalRouteNames.programs,
        transLabel: () => trans_choice('students.program', 2),
    },
    {
        value: 'applications',
        icon: IconName.application,
        routeName: portalRouteNames.applications,
        transLabel: () => trans_choice('students.application', 2),
    },
    {
        value: 'financials',
        icon: IconName.money,
        routeName: portalRouteNames.financials,
        transLabel: () => trans_choice('students.financial', 2),
    },
    {
        value: 'accommodations',
        icon: IconName.bed,
        routeName: portalRouteNames.accommodations,
        transLabel: () => trans_choice('students.accommodation', 2),
    },
    {
        value: 'documents',
        icon: IconName.files,
        routeName: portalRouteNames.documents,
        transLabel: () => trans_choice('students.document', 2),
    },
    {
        value: 'authentication',
        icon: IconName.shield,
        routeName: portalRouteNames.authentication,
        transLabel: () => trans_choice('students.authentication', 1),
    },
];

export function isStudentProfileTabVisible(
    value: StudentProfileTabValue,
    context: StudentProfileTabContext,
): boolean {
    return tabShowChecks[value][context]();
}

export const studentProfileTabDefinitions = (
    context: StudentProfileTabContext = 'portal',
): StudentProfileTabDefinition[] =>
    studentProfileTabCatalog.map((definition) => ({
        ...definition,
        show: isStudentProfileTabVisible(definition.value, context),
    }));

const tabComponents: Record<StudentProfileTabValue, (student: Student) => Component> = {
    basic_info: (student) => h(Info, { student }),
    programs: (student) => h(Programs, { student }),
    applications: (student) => h(Applications, { student }),
    financials: (student) => h(Financials, { student }),
    accommodations: (student) => h(Hostels, { student }),
    documents: () => h(Documents),
    authentication: (student) => h(Authentication, { user: student?.relationships?.user }),
};

export const useStudentProfile = () => {
    const profileTabs = (student: Student): CustomTab[] =>
        studentProfileTabDefinitions('admin')
            .filter((definition) => definition.show ?? false)
            .map((definition) => ({
                value: definition.value,
                icon: definition.icon,
                show: definition.show ?? false,
                transLabel: definition.transLabel,
                component: tabComponents[definition.value](student),
            }));

    const portalProfileTabs = (): StudentProfileTabDefinition[] =>
        studentProfileTabCatalog
            .map((definition) => ({
                ...definition,
                show: isStudentProfileTabVisible(definition.value, 'portal'),
            }))
            .filter((tab) => tab.show ?? false);

    const portalSidebarProfileTabs = (): StudentProfileTabDefinition[] => {
        const tabs = studentProfileTabCatalog
            .filter((tab) => tab.routeName)
            .map((definition) => ({
                ...definition,
                show: isStudentProfileTabVisible(definition.value, 'portal'),
            }));
        const authentication = tabs.find((tab) => tab.value === 'authentication');
        const beforeAuthentication = tabs.filter((tab) => tab.value !== 'authentication');

        return authentication ? [...beforeAuthentication, authentication] : beforeAuthentication;
    };

    return {
        profileTabs,
        portalProfileTabs,
        portalSidebarProfileTabs,
    };
};
