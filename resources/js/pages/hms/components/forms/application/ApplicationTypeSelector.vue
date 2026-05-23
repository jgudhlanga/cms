<script setup lang="ts">
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import type { RadioGroupOption } from '@/types/forms';

defineProps<{
    options: RadioGroupOption[];
}>();

const applicationType = defineModel<'student' | 'guest'>({ required: true });
</script>

<template>
    <section class="space-y-3">
        <p class="text-sm font-medium text-muted-foreground">
            {{ $t('hms.application_type_label') }}
        </p>
        <BaseRadioGroup
            v-model="applicationType"
            :options="options"
            :vertical-layout="false"
            orientation="horizontal"
        />
    </section>
</template>
