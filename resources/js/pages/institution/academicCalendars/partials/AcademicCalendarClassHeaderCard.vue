<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseCard from '@/components/core/card/BaseCard.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';

defineProps<{
    title: string;
    description: string | null;
    studentCount: number;
    canUpdate: boolean;
    canExportClassList?: boolean;
}>();

const emit = defineEmits<{
    edit: [];
    exportClassList: [];
}>();
</script>

<template>
    <BaseCard>
        <div class="flex flex-col gap-4">
            <HeadingSmall :title="title" />
            <div class="flex items-end justify-between gap-4">
                <div class="grid min-w-0 flex-1 grid-cols-1 gap-4 md:grid-cols-2">
                    <LabelValue :label="$tChoice('trans.student', 2)" :value="String(studentCount)" />
                    <LabelValue :label="$t('academic_calendar.description')" :value="description ?? '---'" />
                </div>
                <div
                    v-if="canUpdate || canExportClassList"
                    class="flex shrink-0 flex-wrap items-center justify-end gap-2"
                >
                    <BaseButton
                        v-if="canUpdate"
                        type="button"
                        :size="ButtonSize.sm"
                        :variant="ColorVariant.shade"
                        classes="inline-flex items-center gap-1.5 rounded-full"
                        @click="emit('edit')"
                    >
                        <BaseIcon :name="IconName.edit" class="h-3.5 w-3.5" />
                        <span>{{ $t('trans.edit') }}</span>
                    </BaseButton>
                    <BaseButton
                        v-if="canExportClassList"
                        type="button"
                        :size="ButtonSize.sm"
                        :variant="ColorVariant.primary_outline"
                        classes="inline-flex items-center gap-1.5 rounded-full"
                        @click="emit('exportClassList')"
                    >
                        <BaseIcon :name="IconName.export" class="h-3.5 w-3.5" />
                        <span>{{ $t('academic_calendar.export_class_list') }}</span>
                    </BaseButton>
                </div>
            </div>
        </div>
    </BaseCard>
</template>
