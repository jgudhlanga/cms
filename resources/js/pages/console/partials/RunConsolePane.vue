<script setup lang="ts">
import customAxios from '@/services/http-init';
import type { ConsoleRunOutput, ConsoleRunStatus } from '@/types/console';
import { trans } from 'laravel-vue-i18n';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const POLL_MS = 1500;
const QUEUED_HINT_MS = 30_000;

const props = defineProps<{
    runUuid: string;
}>();

const emit = defineEmits<{
    finished: [status: ConsoleRunStatus];
}>();

const webClient = customAxios('/');
const output = ref('');
const status = ref<ConsoleRunStatus | null>(null);
const errorMessage = ref<string | null>(null);
const offset = ref(0);
const pane = ref<HTMLElement | null>(null);
const stickToBottom = ref(true);
const queuedSince = ref<number | null>(null);
const showQueuedHint = ref(false);

let pollTimer: ReturnType<typeof setInterval> | null = null;
let queuedTimer: ReturnType<typeof setTimeout> | null = null;

const isTerminal = (value: ConsoleRunStatus | null): boolean => {
    return value === 'succeeded' || value === 'failed';
};

const stopPolling = () => {
    if (pollTimer !== null) {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    if (queuedTimer !== null) {
        clearTimeout(queuedTimer);
        queuedTimer = null;
    }
};

const scrollIfNeeded = async () => {
    await nextTick();

    if (!stickToBottom.value || pane.value === null) {
        return;
    }

    pane.value.scrollTop = pane.value.scrollHeight;
};

const fetchOutput = async () => {
    try {
        const response = await webClient.get<ConsoleRunOutput>(route('console.runs.output', { run: props.runUuid }), {
            params: { offset: offset.value },
        });
        const payload = response.data;

        if (payload.chunk !== '') {
            output.value += payload.chunk;
            offset.value = payload.offset;
            void scrollIfNeeded();
        } else {
            offset.value = payload.offset;
        }

        status.value = payload.status;
        errorMessage.value = payload.error;

        if (payload.status === 'queued') {
            if (queuedSince.value === null) {
                queuedSince.value = Date.now();
                queuedTimer = setTimeout(() => {
                    showQueuedHint.value = true;
                }, QUEUED_HINT_MS);
            }
        } else {
            showQueuedHint.value = false;
        }

        if (isTerminal(payload.status)) {
            stopPolling();
            emit('finished', payload.status);
        }
    } catch {
        // Keep the last known output; the next poll retries.
    }
};

const startPolling = () => {
    stopPolling();
    void fetchOutput();
    pollTimer = setInterval(() => {
        void fetchOutput();
    }, POLL_MS);
};

const onScroll = () => {
    if (pane.value === null) {
        return;
    }

    const remaining = pane.value.scrollHeight - pane.value.scrollTop - pane.value.clientHeight;
    stickToBottom.value = remaining < 24;
};

watch(
    () => props.runUuid,
    () => {
        output.value = '';
        status.value = null;
        errorMessage.value = null;
        offset.value = 0;
        stickToBottom.value = true;
        queuedSince.value = null;
        showQueuedHint.value = false;
        startPolling();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    stopPolling();
});
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
        <header class="flex items-center justify-between gap-3 border-b border-slate-800 px-3 py-1.5">
            <h2 class="text-[11px] font-semibold tracking-wide text-slate-300 uppercase">{{ trans('console.output_title') }}</h2>
            <span v-if="status" class="font-mono text-[11px] text-slate-400">{{ status }}</span>
        </header>
        <pre
            ref="pane"
            class="max-h-80 min-h-32 overflow-auto px-3 py-2 font-mono text-[12px] leading-relaxed whitespace-pre-wrap text-slate-100"
            @scroll="onScroll"
        >{{ output || (status === 'queued' ? trans('console.output_waiting') : '') }}</pre>
        <p v-if="status === 'queued' && showQueuedHint" class="border-t border-slate-800 px-3 py-2 text-[11px] text-slate-400">
            {{ trans('console.output_waiting_hint') }}
        </p>
        <p v-else-if="errorMessage" class="border-t border-slate-800 px-3 py-2 text-[11px] text-red-400">{{ errorMessage }}</p>
    </section>
</template>
