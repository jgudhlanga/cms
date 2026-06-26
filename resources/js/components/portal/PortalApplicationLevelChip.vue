<script setup lang="ts">
defineProps<{
    levelName?: string | null;
    intakeName?: string | null;
}>();
</script>

<template>
    <div v-if="levelName?.trim() || intakeName?.trim()" class="flex flex-wrap items-center justify-center gap-1.5">
        <span
            v-if="levelName?.trim()"
            class="inline-flex items-center rounded-full border border-primary/20 bg-primary/5 px-2 py-0.5 text-[11px] leading-tight text-foreground"
        >
            {{ $t('trans.portal_selected_level_chip', { level: levelName }) }}
        </span>
        <span
            v-if="intakeName?.trim()"
            class="inline-flex items-center rounded-full border border-primary/20 bg-primary/5 px-2 py-0.5 text-[11px] leading-tight text-foreground"
        >
            {{ $t('trans.portal_enrolling_for_intake', { intake: intakeName }) }}
        </span>
    </div>
</template>
