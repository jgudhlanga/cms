<script setup lang="ts">
import BaseImage from '@/components/core/image/BaseImage.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseTabs from '@/components/core/tabs/BaseTabs.vue';
import { useShowUser } from '@/composables/users/useShowUser';
import { AuthObject } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    user: User;
    auth: AuthObject;
    errors: object;
}>();

const { user } = props;
const userProfile = {
    avatar: 'https://i.pravatar.cc/150?img=3',
    name: 'James Gudhlanga',
    username: 'jgudhlanga',
    role: 'Admin',
    roles: ['Admin', 'Editor'],
    permissions: ['create-users', 'edit-users', 'delete-posts', 'view-reports'],
    email: 'james@example.com',
    phone: '+27 123 456 7890',
    dob: '1992-04-22',
    location: 'Cape Town, South Africa',
    preferences: {
        theme: 'Dark',
        language: 'English',
        notifications: true,
    },
    security: {
        twoFA: true,
        lastLogin: 'Today at 09:42AM',
        sessions: 3,
    },
    activityLog: ['Logged in at 09:42AM', 'Edited profile yesterday', 'Changed password 2 days ago'],
};
const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'user',
        href: route('users.index'),
    },
    { title: user?.attributes?.name },
];
const { userProfileTabs } = useShowUser();

const defaultTab = 'info';
const tabs = userProfileTabs(user);
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="bg-endless h-[150px]"></div>
        <div class="z-10 -mt-[80px] flex flex-col items-center justify-center">
            <BaseImage :src="''" :is-person="true" classes="w-[130px] h-[130px] rounded-full border-2 border-primary shadow-lg" />
            <h1 class="mt-2 text-xl font-bold">{{ user?.attributes?.name }}</h1>
            <p class="text-muted-foreground pb-2">{{ user?.attributes?.email }}</p>
        </div>
        <BaseTabs :default-value="defaultTab" :tabs="tabs" />
        <div class="flex flex-col space-y-6">
            <div class="flex items-center justify-between rounded-2xl bg-white p-6 shadow">
                <div class="flex items-center space-x-4">
                    <img :src="userProfile.avatar" alt="Avatar" class="h-16 w-16 rounded-full object-cover" />
                    <div>
                        <h2 class="text-xl font-bold">{{ userProfile.name }}</h2>
                        <p class="text-gray-500">@{{ userProfile.username }}</p>
                        <span class="rounded-full bg-blue-500 px-2 py-1 text-xs text-white">{{ userProfile.role }}</span>
                    </div>
                </div>
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-white shadow">Edit Profile</button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold">Basic Info</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li><strong>Email:</strong> {{ userProfile.email }}</li>
                        <li><strong>Phone:</strong> {{ userProfile.phone }}</li>
                        <li><strong>DOB:</strong> {{ userProfile.dob }}</li>
                        <li><strong>Location:</strong> {{ userProfile.location }}</li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold">Preferences</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li><strong>Theme:</strong> {{ userProfile.preferences.theme }}</li>
                        <li><strong>Language:</strong> {{ userProfile.preferences.language }}</li>
                        <li><strong>Notifications:</strong> {{ userProfile.preferences.notifications ? 'On' : 'Off' }}</li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold">Security</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li><strong>2FA:</strong> {{ userProfile.security.twoFA ? 'Enabled' : 'Disabled' }}</li>
                        <li><strong>Last Login:</strong> {{ userProfile.security.lastLogin }}</li>
                        <li><strong>Sessions:</strong> {{ userProfile.security.sessions }} active</li>
                    </ul>
                    <button class="mt-4 text-sm text-blue-600">Reset Password</button>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold">Roles & Permissions</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li>
                            <strong>Roles:</strong>
                            <ul class="list-inside list-disc">
                                <li v-for="(role, index) in userProfile.roles" :key="index">{{ role }}</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Permissions:</strong>
                            <ul class="list-inside list-disc">
                                <li v-for="(perm, index) in userProfile.permissions" :key="index">{{ perm }}</li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold">Activity Log</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li v-for="(activity, index) in userProfile.activityLog" :key="index">
                            {{ activity }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
