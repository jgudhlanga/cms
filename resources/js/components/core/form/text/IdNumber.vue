<script setup lang="ts">
import BaseInput from './BaseInput.vue';

withDefaults(
    defineProps<{
        showFormatHint?: boolean;
        helperText?: string;
    }>(),
    {
        showFormatHint: true,
    },
);
</script>

<template>
    <div class="flex w-full flex-col">
        <BaseInput input-id="id_number" :label="$t('trans.id_number')" v-bind="$attrs" :placeholder="$t('trans.ui_eg_63_1234567n63')" />
        <p v-if="showFormatHint" class="text-muted-foreground mt-1.5 text-xs leading-relaxed">
            {{ helperText ?? $t('trans.id_number_format_hint') }}
        </p>
    </div>
</template>
