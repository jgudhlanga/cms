<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';

interface Props {
    collegeName?: string | null;
    title?: string;
}

withDefaults(defineProps<Props>(), {
    collegeName: null,
    title: undefined,
});
</script>

<template>
    <BaseCard :title="title ?? $t('trans.application_transfer_details_title')">
        <div class="grid w-full grid-cols-1 gap-2 md:grid-cols-2">
            <LabelValue
                :label="$t('trans.application_transfer_college_label')"
                :value="collegeName ?? ''"
            />
        </div>
    </BaseCard>
</template>
