<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import type { AccountPurgeArchiveDialogTarget } from '@/types/maintenance-archives';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        archive: AccountPurgeArchiveDialogTarget;
        processing?: boolean;
    }>(),
    {
        processing: false,
    },
);

const emit = defineEmits<{
    closed: [];
    confirm: [];
}>();

const restoreItems = computed(() => {
    const items = ['trans.maintenance_archives_restore_item_account'];

    if (props.archive.purgeType === 'student_account') {
        items.push(
            'trans.maintenance_archives_restore_item_profile',
            'trans.maintenance_archives_restore_item_applications',
            'trans.maintenance_archives_restore_item_enrolments',
            'trans.maintenance_archives_restore_item_financials',
            'trans.maintenance_archives_restore_item_documents',
        );
    }

    return items;
});
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 z-0 bg-black opacity-50"></div>
        <div class="relative z-10 m-2 w-[640px] rounded-2xl bg-white text-emerald-700 shadow-lg">
            <div class="flex items-center justify-between px-6 pt-6">
                <div class="flex items-center space-x-3">
                    <component :is="icons[IconName.refresh]" :size="22" />
                    <h2 class="text-md font-semibold uppercase">{{ $t('trans.are_you_sure') }}</h2>
                </div>
                <button
                    class="rounded-full p-2 hover:bg-emerald-100"
                    type="button"
                    @click="emit('closed')"
                >
                    <component :is="icons[IconName.close]" :size="26" />
                </button>
            </div>

            <div class="px-6 pt-3">
                <div class="rounded-md border-l-4 border-emerald-600 bg-gray-50 p-4 shadow-sm">
                    <p class="text-sm text-emerald-800">
                        {{
                            $t('trans.maintenance_archives_restore_confirm_intro', {
                                name: archive.name,
                            })
                        }}
                    </p>

                    <div class="mt-4 rounded-md border border-emerald-100 bg-white px-3 py-2 text-sm text-emerald-800">
                        <span class="font-medium">{{ archive.name }}</span>
                        <span v-if="archive.email" class="text-emerald-700/80"> — {{ archive.email }}</span>
                        <span class="mt-1 block text-xs uppercase tracking-wide text-emerald-700">
                            {{ archive.purgeTypeLabel }}
                        </span>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">
                            {{ $t('trans.maintenance_archives_restore_confirm_items_heading') }}
                        </p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-emerald-800">
                            <li v-for="itemKey in restoreItems" :key="itemKey">
                                {{ $t(itemKey) }}
                            </li>
                        </ul>
                    </div>

                    <p class="mt-4 text-sm font-medium text-emerald-900">
                        {{ $t('trans.maintenance_archives_restore_confirm_password_notice') }}
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
                    :variant="ColorVariant.success"
                    type="button"
                    :processing="processing"
                    @click="emit('confirm')"
                >
                    {{ $t('trans.maintenance_archives_restore') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
