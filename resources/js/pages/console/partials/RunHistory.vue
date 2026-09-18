<script setup lang="ts">
import type { ConsoleCommandRun } from '@/types/console';
import { trans } from 'laravel-vue-i18n';

defineProps<{
    runs: ConsoleCommandRun[];
}>();

const statusClass = (colour: string): string => {
    if (colour === 'green') {
        return 'bg-green-100 text-green-700';
    }

    if (colour === 'red') {
        return 'bg-red-100 text-red-700';
    }

    if (colour === 'blue') {
        return 'bg-blue-100 text-blue-700';
    }

    return 'bg-slate-100 text-slate-700';
};

const formatWhen = (value: string | null): string => {
    if (value === null) {
        return '—';
    }

    return new Date(value).toLocaleString();
};

const formatDuration = (ms: number | null): string => {
    if (ms === null) {
        return '—';
    }

    if (ms < 1000) {
        return trans('console.ms', { ms: String(ms) });
    }

    return `${(ms / 1000).toFixed(1)}s`;
};
</script>

<template>
    <section class="border-border bg-card overflow-hidden rounded-xl border">
        <header class="px-4 py-3">
            <h2 class="text-accent-foreground text-xs font-bold uppercase">{{ trans('console.history_title') }}</h2>
        </header>
        <p v-if="runs.length === 0" class="text-muted-foreground px-4 pb-4 text-sm">{{ trans('console.history_empty') }}</p>
        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-border text-muted-foreground border-y text-[11px] uppercase">
                    <tr>
                        <th class="px-4 py-2 font-semibold">{{ trans('trans.status') }}</th>
                        <th class="px-4 py-2 font-semibold">{{ trans('console.command_line') }}</th>
                        <th class="px-4 py-2 font-semibold">{{ trans('console.queued_by') }}</th>
                        <th class="px-4 py-2 font-semibold">{{ trans('console.duration') }}</th>
                        <th class="px-4 py-2 font-semibold">{{ trans('console.exit_code') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="run in runs" :key="run.uuid" class="border-border border-t">
                        <td class="px-4 py-2">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusClass(run.statusColour)">
                                {{ run.statusLabel }}
                            </span>
                            <p class="text-muted-foreground mt-1 text-[11px]">{{ formatWhen(run.queuedAt) }}</p>
                        </td>
                        <td class="px-4 py-2 font-mono text-[11px] break-all">{{ run.commandLine }}</td>
                        <td class="px-4 py-2 text-xs">{{ run.queuedBy ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ formatDuration(run.durationMs) }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ run.exitCode ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
