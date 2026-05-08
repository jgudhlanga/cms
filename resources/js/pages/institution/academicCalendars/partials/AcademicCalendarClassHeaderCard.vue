<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';

defineProps<{
    title: string;
    description: string | null;
    studentCount: number;
    canUpdate: boolean;
}>();

const emit = defineEmits<{
    edit: [];
}>();
</script>

<template>
    <BaseCard>
        <div class="flex items-start justify-between gap-2">
            <HeadingSmall :title="title" />
            <IconButton v-if="canUpdate" :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="emit('edit')" />
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <LabelValue :label="$tChoice('trans.student', 2)" :value="String(studentCount)" />
            <LabelValue :label="$t('academic_calendar.description')" :value="description ?? '---'" />
        </div>
    </BaseCard>
</template>
