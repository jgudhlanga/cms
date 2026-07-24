import UserRbac from '@/components/users/tabs/UserRbac.vue';
import UserActivity from '@/components/users/tabs/UserActivity.vue';
import UserInfo from '@/components/users/tabs/UserInfo.vue';
import UserPreferences from '@/components/users/tabs/UserPreferences.vue';
import UserSecurity from '@/components/users/tabs/UserSecurity.vue';
import { IconName } from '@/lib/icons';
import { User } from '@/types/users';
import { CustomTab } from '@/types/utils';
import { trans } from 'laravel-vue-i18n';
import { h } from 'vue';

export const useShowUser = () => {
    const userProfileTabs = (user: User): Array<CustomTab> => {
        return [
            {
                transLabel: () => trans('trans.basic_info'),
                value: 'basic_info',
                component: h(UserInfo, { user }),
                icon: IconName.user,
            },
            {
                transLabel: () => trans('trans.roles'),
                value: 'rbac',
                component: h(UserRbac, { user }),
                icon: IconName.shield,
            },
            {
                transLabel: () => trans('trans.security'),
                value: 'security',
                component: h(UserSecurity, { user }),
                icon: IconName.finger_print,
            },
            {
                transLabel: () => trans('trans.preferences'),
                value: 'preferences',
                component: h(UserPreferences, { user }),
                icon: IconName.settings,
            },
            {
                transLabel: () => trans('trans.activity_log'),
                value: 'activity',
                component: h(UserActivity, { user }),
                icon: IconName.history,
            },
        ];
    };

    return {
        userProfileTabs,
    };
};
