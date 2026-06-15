<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { useUsers } from '@/composables/users/useUsers';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { hasAbility } from '@/lib/permissions';
import type { Permission } from '@/types/acl';
import type { User } from '@/types/users';
import { computed, onMounted } from 'vue';
import { ButtonSize } from '@/enums/buttons';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { loadUserPermissions, userPermissions, isLoading } = useUsers();
const { navigateTo } = useUtils();

const canEdit = hasAbility('update:users');

const roles = computed(() => props.user.relationships?.roles ?? []);

const permissions = computed<Permission[]>(() => {
    const data = userPermissions.value?.data;

    if (!Array.isArray(data)) {
        return [];
    }

    return data as Permission[];
});

const permissionsByModule = computed(() => {
    const grouped = new Map<string, Permission[]>();

    permissions.value.forEach((permission) => {
        const moduleName = permission.relationships?.module?.attributes?.title ?? 'General';
        const existing = grouped.get(moduleName) ?? [];
        existing.push(permission);
        grouped.set(moduleName, existing);
    });

    return [...grouped.entries()].sort(([left], [right]) => left.localeCompare(right));
});

onMounted(async () => {
    await loadUserPermissions(route('v1.users.permissions', { user: props.user.id }));
});
</script>

<template>
    <div class="space-y-8">
        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                    {{ $tChoice('trans.role', 2) }}
                </h2>
                <GenericButton
                    v-if="canEdit"
                    :title="$t('trans.edit_role_permissions')"
                    :variant="ColorVariant.shade_outline"
                    class="rounded-full"
                    :size="ButtonSize.sm"
                    @click="() => navigateTo(route('users.edit', user.id))"
                />
            </div>

            <div v-if="roles.length" class="flex flex-wrap gap-2">
                <span
                    v-for="role in roles"
                    :key="role.id"
                    class="inline-flex items-center rounded-full border border-primary/30 bg-primary/10 px-3 py-1 text-sm font-medium text-primary"
                >
                    {{ role.name }}
                </span>
            </div>
            <p v-else class="text-sm italic text-muted-foreground">{{ $t('trans.not_set') }}</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                {{ $t('trans.roles_and_permissions') }}
            </h2>

            <DataLoadingSpinner v-if="isLoading" />

            <div v-else-if="permissionsByModule.length" class="space-y-4">
                <div
                    v-for="[moduleName, modulePermissions] in permissionsByModule"
                    :key="moduleName"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <h3 class="mb-3 text-sm font-semibold text-foreground">{{ moduleName }}</h3>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="permission in modulePermissions"
                            :key="permission.id"
                            class="rounded-lg bg-muted/30 px-3 py-2 text-sm text-foreground"
                        >
                            {{ permission.attributes?.name }}
                        </div>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm italic text-muted-foreground">{{ $t('trans.not_provided') }}</p>
        </section>
    </div>
</template>
