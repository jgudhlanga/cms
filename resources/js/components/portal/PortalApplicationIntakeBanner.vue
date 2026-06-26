<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    intakeName?: string | null;
    openIntakeNames?: string | null;
}>();

const showSingleIntake = computed(() => Boolean(props.intakeName?.trim()));
const showMultipleIntakes = computed(() => Boolean(props.openIntakeNames?.trim()) && !showSingleIntake.value);
</script>

<template>
    <div
        v-if="showSingleIntake || showMultipleIntakes"
        class="mx-auto mb-3 w-full max-w-2xl px-4 text-center sm:mb-4 sm:px-0"
    >
        <p
            v-if="showSingleIntake"
            class="inline-flex items-center rounded-full border border-primary/20 bg-primary/5 px-2 py-0.5 text-xs text-foreground"
        >
            {{ $t('trans.portal_enrolling_for_intake', { intake: intakeName }) }}
        </p>
        <p
            v-else-if="showMultipleIntakes"
            class="inline-flex items-center rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-sm text-foreground"
        >
            {{ $t('trans.portal_open_intakes', { intakes: openIntakeNames }) }}
        </p>
    </div>
</template>
