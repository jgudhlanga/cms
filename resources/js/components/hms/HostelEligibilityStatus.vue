<script setup lang="ts">
import type { HostelApplicationEligibilityRule } from '@/types/hms';
import { computed } from 'vue';

interface Props {
    rules: HostelApplicationEligibilityRule[];
    dense?: boolean;
    showAccommodation?: boolean;
    showHeading?: boolean;
    showAdvisoryNotice?: boolean;
    grid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    dense: true,
    showAccommodation: false,
    showHeading: true,
    showAdvisoryNotice: false,
    grid: false,
});

const visibleRules = computed(() =>
    props.rules.filter((rule) => props.showAccommodation || rule.key !== 'accommodation_paid'),
);

const severityClass = (rule: HostelApplicationEligibilityRule): string => {
    const severity = rule.severity ?? (rule.passed ? 'success' : 'warning');

    return {
        success: 'text-emerald-600 dark:text-emerald-400',
        info: 'text-sky-600 dark:text-sky-400',
        warning: 'text-amber-600 dark:text-amber-400',
    }[severity] ?? 'text-muted-foreground';
};

const itemClass = computed(() =>
    props.dense ? 'text-sm leading-snug' : 'text-sm leading-relaxed',
);
</script>

<template>
    <template v-if="grid">
        <p
            v-if="showAdvisoryNotice"
            class="text-sm leading-snug text-muted-foreground md:col-span-3"
        >
            {{ $t('hms.eligibility_advisory_notice') }}
        </p>
        <p
            v-for="rule in visibleRules"
            :key="rule.key"
            :class="[itemClass, severityClass(rule)]"
        >
            {{ rule.message }}
        </p>
    </template>

    <div
        v-else-if="visibleRules.length"
        :class="dense ? 'space-y-1' : 'space-y-2'"
    >
        <p
            v-if="showHeading"
            class="text-xs font-medium uppercase tracking-wide text-muted-foreground"
        >
            {{ $t('students.accommodation_eligibility') }}
        </p>
        <p
            v-if="showAdvisoryNotice"
            class="text-xs text-muted-foreground"
        >
            {{ $t('hms.eligibility_advisory_notice') }}
        </p>
        <ul :class="dense ? 'flex flex-col gap-0.5' : 'flex flex-col gap-1.5'">
            <li
                v-for="rule in visibleRules"
                :key="rule.key"
                :class="[itemClass, severityClass(rule)]"
            >
                {{ rule.message }}
            </li>
        </ul>
    </div>
</template>
