<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useDefaults } from '@/composables/core/useDefaults';
import { useInitials } from '@/composables/core/useInitials';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import type { User } from '@/types/users';
import { Circle } from 'lucide-vue-next';
import { computed } from 'vue';
import { ButtonSize } from '@/enums/buttons';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { getInitials } = useInitials();
const { defaultAvatarImage } = useDefaults();
const { navigateTo } = useUtils();

const canEdit = hasAbility('update:users');

const avatarSrc = computed(() => {
    const avatarUrl = props.user.attributes.avatarUrl;

    if (!avatarUrl) {
        return defaultAvatarImage.value;
    }

    if (typeof avatarUrl === 'object' && avatarUrl !== null && 'thumb' in avatarUrl) {
        return (avatarUrl as { thumb?: string }).thumb ?? defaultAvatarImage.value;
    }

    return String(avatarUrl);
});

const isActiveStatus = computed(() => {
    const status = props.user.attributes.status?.toLowerCase() ?? '';

    return status === 'active' || status.includes('active');
});

const phoneNumber = computed(() => props.user.attributes.phoneNumber?.trim() ?? '');

const onEditProfile = (): void => {
    navigateTo(route('users.edit', props.user.id));
};
</script>

<template>
    <div class="border-b border-border px-4 py-2 mb-3">
        <div class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 flex-1 items-start gap-4 sm:items-center">
                <Avatar class="size-20 shrink-0 border-2 border-border shadow-sm sm:size-24">
                    <AvatarImage :src="avatarSrc" :alt="user.attributes.name" />
                    <AvatarFallback class="bg-primary/10 text-base font-bold text-primary sm:text-lg">
                        {{ getInitials(user.attributes.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex min-w-0 flex-wrap items-center gap-3">
                        <h1 class="min-w-0 text-xl font-bold tracking-tight text-foreground md:text-2xl">
                            {{ user.attributes.name }}
                        </h1>
                        <span
                            v-if="user.attributes.status"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                            :class="
                                isActiveStatus
                                    ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-500/30 dark:bg-green-500/15 dark:text-green-400'
                                    : 'border-border bg-muted text-muted-foreground'
                            "
                        >
                            <Circle
                                class="h-1.5 w-1.5 fill-current"
                                :class="isActiveStatus ? 'text-green-500' : 'text-muted-foreground'"
                            />
                            {{ user.attributes.status }}
                        </span>
                    </div>

                    <div class="flex min-w-0 flex-wrap items-center gap-x-5 gap-y-1 text-sm text-muted-foreground">
                        <p class="flex min-w-0 items-center gap-2">
                            <component :is="icons[IconName.mail]" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ user.attributes.email }}</span>
                        </p>
                        <p v-if="phoneNumber" class="flex min-w-0 items-center gap-2">
                            <component :is="icons[IconName.phone]" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ phoneNumber }}</span>
                        </p>
                        <p v-if="user.attributes.tenant" class="flex min-w-0 items-center gap-2">
                            <component :is="icons[IconName.company]" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ user.attributes.tenant }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <GenericButton
                v-if="canEdit"
                :title="$t('trans.edit_profile')"
                :icon="IconName.edit"
                :variant="ColorVariant.shade_outline"
                class="rounded-full"
                :size="ButtonSize.sm"
                @click="onEditProfile"
            />
        </div>
    </div>
</template>
