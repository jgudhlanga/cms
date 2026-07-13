<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { Textarea } from '@/components/ui/textarea';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface AccountPurgeDialogUser {
    name: string;
    email: string;
}

const props = withDefaults(
    defineProps<{
        users: AccountPurgeDialogUser[];
        purgeItems: string[];
        introTranslationKey?: string;
        processing?: boolean;
    }>(),
    {
        introTranslationKey: 'trans.maintenance_users_purge_confirm_intro',
        processing: false,
    },
);

const emit = defineEmits<{
    closed: [];
    confirm: [reason: string];
}>();

const reason = ref('');
const reasonError = ref<string | null>(null);
const page = usePage();

const purgeArchiveRetentionDays = computed(
    () => (page.props.purgeArchiveRetentionDays as number | undefined) ?? 30,
);

const isBulk = computed(() => props.users.length > 1);

const introText = computed(() =>
    isBulk.value
        ? trans('trans.maintenance_users_bulk_purge_confirm_intro', { count: props.users.length })
        : trans(props.introTranslationKey, {
              name: props.users[0]?.name ?? '',
          }),
);

const validateReason = (): boolean => {
    const trimmed = reason.value.trim();

    if (trimmed.length === 0) {
        reasonError.value = trans('trans.account_purge_reason_required');

        return false;
    }

    if (trimmed.length < 10) {
        reasonError.value = trans('trans.account_purge_reason_min');

        return false;
    }

    reasonError.value = null;

    return true;
};

const handleConfirm = (): void => {
    if (!validateReason()) {
        return;
    }

    emit('confirm', reason.value.trim());
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 z-0 bg-black opacity-50"></div>
        <div class="relative z-10 m-2 w-[640px] rounded-2xl bg-white text-red-600 shadow-lg">
            <div class="flex items-center justify-between px-6 pt-6">
                <div class="flex items-center space-x-3">
                    <component :is="icons[IconName.danger]" :size="22" />
                    <h2 class="text-md font-semibold uppercase">{{ $t('trans.are_you_sure') }}</h2>
                </div>
                <button
                    class="rounded-full p-2 hover:bg-red-200"
                    type="button"
                    @click="emit('closed')"
                >
                    <component :is="icons[IconName.close]" :size="26" />
                </button>
            </div>

            <div class="px-6 pt-3">
                <div class="rounded-md border-l-4 border-red-600 bg-gray-50 p-4 shadow-sm">
                    <p class="text-sm text-red-700">{{ introText }}</p>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-red-800">
                            {{ $t('trans.maintenance_users_purge_affected_users') }}
                        </p>
                        <ul
                            class="mt-2 max-h-40 space-y-2 overflow-y-auto text-sm text-red-700"
                            :class="{ 'list-none': !isBulk }"
                        >
                            <li
                                v-for="(user, index) in users"
                                :key="`${user.email}-${index}`"
                                class="rounded-md border border-red-100 bg-white px-3 py-2"
                            >
                                <span class="font-medium">{{ user.name }}</span>
                                <span class="text-red-600/80"> — {{ user.email }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-red-800">
                            {{ $t('trans.maintenance_users_purge_items_heading') }}
                        </p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                            <li v-for="itemKey in purgeItems" :key="itemKey">
                                {{ $t(itemKey) }}
                            </li>
                        </ul>
                    </div>

                    <div class="mt-4">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-red-800">
                            {{ $t('trans.account_purge_reason_label') }}
                        </label>
                        <Textarea
                            v-model="reason"
                            class="w-full border-red-200 bg-white text-red-900 placeholder:text-red-400"
                            :placeholder="$t('trans.account_purge_reason_placeholder')"
                            rows="3"
                        />
                        <p v-if="reasonError" class="mt-1 text-xs text-red-700">{{ reasonError }}</p>
                    </div>

                    <p class="mt-4 text-sm font-medium text-red-800">
                        {{ $t('trans.maintenance_users_purge_irreversible', { days: purgeArchiveRetentionDays }) }}
                    </p>
                </div>
            </div>

            <div :class="cn('mt-6 flex w-full items-center justify-center space-x-3 px-6 py-5')">
                <BaseButton
                    classes="rounded-full"
                    :variant="ColorVariant.shade"
                    type="button"
                    @click="emit('closed')"
                >
                    {{ $t('trans.close') }}
                </BaseButton>
                <BaseButton
                    classes="rounded-full"
                    :variant="ColorVariant.danger"
                    type="button"
                    :processing="processing"
                    @click="handleConfirm"
                >
                    {{ $t('trans.continue') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
