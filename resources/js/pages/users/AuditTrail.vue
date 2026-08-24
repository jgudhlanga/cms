<script setup lang="ts">
import UserCausedActivity from '@/components/audit/UserCausedActivity.vue';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { hasAbility } from '@/lib/permissions';
import { resolveUiLabel } from '@/lib/uiLabel';
import HttpService from '@/services/http.service';
import type { AuthObject } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import type { SelectOption } from '@/types/utils';
import { Head, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { debounce } from 'lodash';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    auth: AuthObject;
}>();

const page = usePage();
const canPickUser = computed(() => hasAbility('root:manage'));

const currentUserId = computed(() => Number(props.auth?.user?.id ?? page.props.auth?.user?.id ?? 0));

const meOption = computed<SelectOption>(() => ({
    value: currentUserId.value,
    label: resolveUiLabel('trans.activity_user_me', trans),
}));

const selectedUser = ref<SelectOption | null>(null);
const options = ref<SelectOption[]>([]);
const isLoadingUsers = ref(false);

const breadcrumbs: Link[] = [{ transChoiceKey: 'user', href: route('users.index') }, { transKey: 'trans.audit_trail' }];

const selectedUserId = computed(() => {
    const value = selectedUser.value?.value ?? currentUserId.value;

    return value ? Number(value) : currentUserId.value;
});

type LookupUser = {
    id: number | string;
    name: string;
    email?: string | null;
};

const toOption = (user: LookupUser): SelectOption => ({
    value: Number(user.id),
    label: user.email ? `${user.name} (${user.email})` : user.name,
});

const loadUsers = async (search = ''): Promise<void> => {
    if (!canPickUser.value) {
        return;
    }

    isLoadingUsers.value = true;

    try {
        const params = new URLSearchParams();
        if (search.trim()) {
            params.set('search', search.trim());
        }

        const url = `${route('v1.users.activity-lookup')}${params.toString() ? `?${params}` : ''}`;
        const response = (await HttpService.get(url)) as { data?: LookupUser[] };
        const rows = response.data ?? [];

        options.value = [meOption.value, ...rows.filter((user) => Number(user.id) !== currentUserId.value).map(toOption)];
    } finally {
        isLoadingUsers.value = false;
    }
};

const whenSearch = debounce(async (search: string) => {
    await loadUsers(search);
}, 600);

onMounted(async () => {
    selectedUser.value = meOption.value;
    options.value = [meOption.value];

    if (canPickUser.value) {
        await loadUsers();
    }
});
</script>

<template>
    <Head :title="$t('trans.audit_trail')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div v-if="canPickUser" class="max-w-md">
                <BaseCombobox
                    v-model="selectedUser"
                    :label="resolveUiLabel('trans.switch_user', trans)"
                    :vertical-layout="false"
                    :options="options"
                    :is-loading="isLoadingUsers"
                    :on-search="async (search: string) => await whenSearch(search)"
                />
            </div>

            <UserCausedActivity :user-id="selectedUserId" />
        </div>
    </PageContainer>
</template>
