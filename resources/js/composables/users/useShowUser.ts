import UserAcl from '@/components/users/UserAcl.vue';
import UserActivity from '@/components/users/UserActivity.vue';
import UserInfo from '@/components/users/UserInfo.vue';
import UserPreferences from '@/components/users/UserPreferences.vue';
import UserSecurity from '@/components/users/UserSecurity.vue';
import { User } from '@/types/users';
import { CustomTab } from '@/types/utils';
import { h } from 'vue';

export const useShowUser = () => {
    const userProfileTabs = (user: User): Array<CustomTab> => {
        return [
            {
                transLabel: () => 'Basic Info',
                value: 'info',
                component: h(UserInfo, {user}),
            },
            {
                transLabel: () => 'Roles & Permissions',
                value: 'acl',
                component: h(UserAcl),
            },
            {
                transLabel: () => 'Security',
                value: 'security',
                component: h(UserSecurity),
            },
            {
                transLabel: () => 'Preferences',
                value: 'preferences',
                component: h(UserPreferences),
            },
            {
                transLabel: () => 'Activity log',
                value: 'activity',
                component: h(UserActivity),
            },
        ];
    };
    return {
        userProfileTabs,
    };
};
