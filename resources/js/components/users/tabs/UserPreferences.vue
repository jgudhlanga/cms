<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import AppearanceTabs from '@/components/core/util/AppearanceTabs.vue';
import ProfileFieldCard from '@/components/users/profile/ProfileFieldCard.vue';
import { ColorVariant } from '@/enums/colors';
import { hasAbility } from '@/lib/permissions';
import HttpService from '@/services/http.service';
import ToastService from '@/services/toast.service';
import type { User } from '@/types/users';
import Switch from '@/components/ui/switch/Switch.vue';
import { computed, ref, watch } from 'vue';

interface Props {
    user: User;
}

const props = defineProps<Props>();

const canEdit = hasAbility('update:users');
const isSaving = ref(false);
const sideBarState = ref(props.user.relationships?.preference?.attributes?.sideBarState ?? false);
const locale = ref(props.user.relationships?.preference?.attributes?.locale ?? 'en');

const localeOptions = [{ value: 'en', label: 'English' }];

const selectedLocale = computed({
    get: () => locale.value,
    set: (value: string) => {
        locale.value = value;
    },
});

watch(
    () => props.user.relationships?.preference,
    (preference) => {
        sideBarState.value = preference?.attributes?.sideBarState ?? false;
        locale.value = preference?.attributes?.locale ?? 'en';
    },
);

const savePreferences = async (): Promise<void> => {
    if (!canEdit) {
        return;
    }

    isSaving.value = true;

    try {
        await HttpService.put(route('v1.users.preferences.update', { user: props.user.id }), {
            side_bar_state: sideBarState.value,
            locale: locale.value,
        });
        ToastService.success('Preferences saved.');
    } catch {
        ToastService.error('Failed to save preferences.');
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <section class="space-y-3">
            <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                {{ $t('trans.user_preferences') }}
            </h2>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <ProfileFieldCard
                    :label="$t('trans.sidebar_expanded')"
                    :value="sideBarState ? $t('trans.yes') : $t('trans.no')"
                />
                <ProfileFieldCard
                    :label="$tChoice('trans.language', 1)"
                    :value="locale"
                />
            </div>
        </section>

        <div class="rounded-xl border border-border bg-card p-4 space-y-4">
            <div class="space-y-3">
                <div class="space-y-1">
                    <p class="font-medium leading-none">{{ $t('trans.appearance') }}</p>
                    <p class="text-sm text-muted-foreground">{{ $t('trans.appearance_local_note') }}</p>
                </div>
                <AppearanceTabs class="w-full justify-center" />
            </div>

            <div class="flex items-start justify-between gap-4 border-t border-border pt-4">
                <div class="space-y-1">
                    <p class="font-medium leading-none">{{ $t('trans.sidebar_expanded') }}</p>
                    <p class="text-sm text-muted-foreground">{{ $t('trans.sidebar_expanded_description') }}</p>
                </div>
                <Switch
                    class="mt-0.5"
                    :model-value="sideBarState"
                    :disabled="!canEdit"
                    @update:model-value="(value: boolean) => (sideBarState = value)"
                />
            </div>

            <div class="space-y-3 border-t border-border pt-4">
                <div class="space-y-1">
                    <p class="font-medium leading-none">{{ $tChoice('trans.language', 1) }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ $t('trans.ui_english_is_currently_the_only_available_language') }}
                    </p>
                </div>
                <BaseSelect
                    v-model="selectedLocale"
                    class="w-full"
                    label=""
                    :options="localeOptions"
                    :is-searchable="false"
                    :is-clearable="false"
                    :is-disabled="!canEdit"
                />
            </div>

            <div v-if="canEdit" class="flex pt-4">
                <BaseButton :processing="isSaving" :variant="ColorVariant.primary" @click="savePreferences">
                    {{ $t('trans.save') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
