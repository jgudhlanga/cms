<script setup lang="ts">
type IntentSummary = {
    track?: string | null;
    trackLabel?: string | null;
    continuousFocus?: string | null;
    levelName?: string | null;
    intakeName?: string | null;
};

defineProps<{
    summary?: IntentSummary | null;
}>();
</script>

<template>
    <div
        v-if="summary?.trackLabel || summary?.levelName || summary?.intakeName"
        class="mb-4 rounded-lg border border-border bg-muted/30 px-3 py-2"
        role="status"
        aria-live="polite"
    >
        <p class="mb-1.5 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
            {{ $t('trans.registration_intent_summary_title') }}
        </p>
        <div class="flex flex-wrap gap-1.5">
            <span
                v-if="summary?.trackLabel"
                class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
            >
                {{ summary.trackLabel }}
                <template v-if="summary.continuousFocus">
                    · {{ summary.continuousFocus.toUpperCase() }}
                </template>
            </span>
            <span
                v-if="summary?.levelName"
                class="inline-flex items-center rounded-full bg-card px-2.5 py-0.5 text-xs font-medium text-foreground ring-1 ring-border"
            >
                {{ summary.levelName }}
            </span>
            <span
                v-if="summary?.intakeName"
                class="inline-flex items-center rounded-full bg-card px-2.5 py-0.5 text-xs font-medium text-muted-foreground ring-1 ring-border"
            >
                {{ summary.intakeName }}
            </span>
        </div>
    </div>
</template>
