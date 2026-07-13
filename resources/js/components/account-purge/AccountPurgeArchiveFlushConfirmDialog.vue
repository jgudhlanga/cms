<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import type { AccountPurgeArchiveDialogTarget } from '@/types/maintenance-archives';

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

const flushItems = [
    'trans.maintenance_archives_flush_item_payload',
    'trans.maintenance_archives_flush_item_media',
    'trans.maintenance_archives_flush_item_note',
] as const;
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
                    <p class="text-sm text-red-700">
                        {{
                            $t('trans.maintenance_archives_flush_confirm_intro', {
                                name: archive.name,
                            })
                        }}
                    </p>

                    <div class="mt-4 rounded-md border border-red-100 bg-white px-3 py-2 text-sm text-red-700">
                        <span class="font-medium">{{ archive.name }}</span>
                        <span v-if="archive.email" class="text-red-600/80"> — {{ archive.email }}</span>
                        <span class="mt-1 block text-xs uppercase tracking-wide text-red-700">
                            {{ archive.purgeTypeLabel }}
                        </span>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-red-800">
                            {{ $t('trans.maintenance_archives_flush_confirm_items_heading') }}
                        </p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                            <li v-for="itemKey in flushItems" :key="itemKey">
                                {{ $t(itemKey) }}
                            </li>
                        </ul>
                    </div>

                    <p class="mt-4 text-sm font-medium text-red-800">
                        {{ $t('trans.maintenance_archives_flush_irreversible') }}
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
                    @click="emit('confirm')"
                >
                    {{ $t('trans.maintenance_archives_delete') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
