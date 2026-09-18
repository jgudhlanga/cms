<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import BaseAccordionItem from '@/components/core/accordion/BaseAccordionItem.vue';
import { BaseInput } from '@/components/core/form';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import RunCommandModal from '@/pages/console/partials/RunCommandModal.vue';
import RunConsolePane from '@/pages/console/partials/RunConsolePane.vue';
import RunHistory from '@/pages/console/partials/RunHistory.vue';
import customAxios from '@/services/http-init';
import { useConsoleStore } from '@/store/console/useConsoleStore';
import type { ConsoleCommandDefinition, ConsoleCommandGroup, ConsoleCommandRun, ConsoleRisk } from '@/types/console';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { AxiosError } from 'axios';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    groups: ConsoleCommandGroup[];
    canRun: boolean;
    canRunDestructive: boolean;
    runs?: ConsoleCommandRun[] | null;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'trans.console' }];
const store = useConsoleStore();
const { search } = storeToRefs(store);
const runs = ref<ConsoleCommandRun[]>(props.runs ?? []);
const selectedCommand = ref<ConsoleCommandDefinition | null>(null);
const dispatching = ref(false);
const modal = ref<{ setError: (field: string, message: string) => void; reset: () => void } | null>(null);
const webClient = customAxios('/');

const GROUP_ICONS: Record<string, IconName> = {
    school: IconName.school,
    mail: IconName.mail,
    money: IconName.money,
    landmark: IconName.landmark,
    megaphone: IconName.megaphone,
    trash: IconName.trash,
    terminal: IconName.terminal,
};

const groupIcon = (icon: string): IconName => GROUP_ICONS[icon] ?? IconName.terminal;

const accordionOpen = computed(() => props.groups.map((group) => group.key));

const filteredGroups = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return props.groups;
    }

    return props.groups
        .map((group) => ({
            ...group,
            commands: group.commands.filter((command) => {
                return [command.label, command.summary, command.signature, command.key].join(' ').toLowerCase().includes(term);
            }),
        }))
        .filter((group) => group.commands.length > 0);
});

const riskClass = (risk: ConsoleRisk): string => {
    if (risk === 'destructive') {
        return 'border-red-500/40 bg-red-500/10 text-red-600';
    }

    if (risk === 'mutating') {
        return 'border-amber-500/40 bg-amber-500/10 text-amber-700';
    }

    return 'border-border bg-muted text-muted-foreground';
};

const riskLabel = (risk: ConsoleRisk): string => {
    if (risk === 'destructive') {
        return trans('console.risk_destructive');
    }

    if (risk === 'mutating') {
        return trans('console.risk_mutating');
    }

    return trans('console.risk_read_only');
};

const canRunCommand = (command: ConsoleCommandDefinition): boolean => {
    if (!props.canRun) {
        return false;
    }

    if (command.risk === 'destructive') {
        return props.canRunDestructive;
    }

    return true;
};

const runDisabledHint = (command: ConsoleCommandDefinition): string => {
    if (!props.canRun) {
        return trans('console.view_only_hint');
    }

    if (command.risk === 'destructive' && !props.canRunDestructive) {
        return trans('console.destructive_locked');
    }

    return '';
};

const isLiveStatus = (status: ConsoleCommandRun['status']): boolean => {
    return status === 'queued' || status === 'running';
};

const liveRunsByCommand = computed(() => {
    const live: Record<string, ConsoleCommandRun> = {};

    for (const run of runs.value) {
        if (isLiveStatus(run.status) && live[run.commandKey] === undefined) {
            live[run.commandKey] = run;
        }
    }

    return live;
});

const markRunFinished = (uuid: string, status: ConsoleCommandRun['status']) => {
    runs.value = runs.value.map((run) => {
        if (run.uuid !== uuid) {
            return run;
        }

        return {
            ...run,
            status,
            statusLabel: status === 'failed' ? trans('console.status_failed') : trans('console.status_succeeded'),
            statusColour: status === 'failed' ? 'red' : 'green',
        };
    });
};

let historyTimer: ReturnType<typeof setInterval> | null = null;

const hasActiveRun = computed(() => runs.value.some((run) => run.status === 'queued' || run.status === 'running'));

const reloadHistory = () => {
    router.reload({
        only: ['runs'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            const next = (page.props.runs as ConsoleCommandRun[] | null | undefined) ?? [];
            runs.value = next;
        },
    });
};

const startHistoryPolling = () => {
    if (historyTimer !== null) {
        return;
    }

    historyTimer = setInterval(() => {
        reloadHistory();
    }, 2500);
};

const stopHistoryPolling = () => {
    if (historyTimer !== null) {
        clearInterval(historyTimer);
        historyTimer = null;
    }
};

watch(hasActiveRun, (active) => {
    if (active) {
        startHistoryPolling();
        return;
    }

    stopHistoryPolling();
});

watch(
    () => props.runs,
    (next) => {
        if (next) {
            runs.value = next;
        }
    },
);

onMounted(() => {
    reloadHistory();
});

onBeforeUnmount(() => {
    stopHistoryPolling();
    store.$reset();
});

const openRun = (command: ConsoleCommandDefinition) => {
    if (!canRunCommand(command)) {
        return;
    }

    selectedCommand.value = command;
};

const closeModal = () => {
    if (dispatching.value) {
        return;
    }

    selectedCommand.value = null;
};

const dispatchCommand = async (payload: { parameters: Record<string, unknown>; password: string }) => {
    if (selectedCommand.value === null) {
        return;
    }

    dispatching.value = true;

    try {
        const response = await webClient.post<{ run: ConsoleCommandRun }>(route('console.dispatch'), {
            command: selectedCommand.value.key,
            password: payload.password,
            parameters: payload.parameters,
        });

        modal.value?.reset();
        selectedCommand.value = null;
        runs.value = [response.data.run, ...runs.value.filter((run) => run.uuid !== response.data.run.uuid)].slice(0, 25);
        startHistoryPolling();
    } catch (error) {
        const axiosError = error as AxiosError<{ errors?: Record<string, string[]>; message?: string }>;
        const fieldErrors = axiosError.response?.data?.errors ?? {};

        Object.entries(fieldErrors).forEach(([field, messages]) => {
            modal.value?.setError(field, messages[0] ?? trans('auth.password'));
        });

        if (Object.keys(fieldErrors).length === 0) {
            modal.value?.setError('password', axiosError.response?.data?.message ?? trans('auth.password'));
        }
    } finally {
        dispatching.value = false;
    }
};
</script>

<template>
    <Head :title="$t('trans.console')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-6xl space-y-3 px-2 sm:px-4">
            <header class="border-border bg-card flex flex-wrap items-center gap-3 rounded-xl border px-4 py-3">
                <span class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                    <component :is="icons[IconName.terminal]" class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                    <h1 class="text-accent-foreground text-xs font-bold uppercase">{{ $t('console.title') }}</h1>
                    <p class="text-muted-foreground text-xs">{{ $t('console.description') }}</p>
                </div>
                <span
                    v-if="!canRun"
                    class="border-border text-muted-foreground inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                    :title="$t('console.view_only_hint')"
                >
                    {{ $t('console.view_only') }}
                </span>
            </header>

            <BaseInput
                input-id="console-search"
                v-model="search"
                :placeholder="$t('console.search_placeholder')"
                :vertical-layout="true"
                autocomplete="off"
            />

            <BaseAccordion type="multiple" :default-value="accordionOpen">
                <BaseAccordionItem v-for="group in filteredGroups" :key="group.key" :value="group.key" :title="group.label">
                    <template #trigger-extra>
                        <component :is="icons[groupIcon(group.icon)]" class="text-muted-foreground size-4" aria-hidden="true" />
                    </template>
                    <div class="space-y-3">
                        <article
                            v-for="command in group.commands"
                            :key="command.key"
                            class="border-border bg-background flex flex-col gap-3 rounded-lg border px-4 py-3"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold">{{ command.label }}</h3>
                                        <span
                                            class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold uppercase"
                                            :class="riskClass(command.risk)"
                                        >
                                            {{ riskLabel(command.risk) }}
                                        </span>
                                    </div>
                                    <p class="text-muted-foreground font-mono text-[11px]">{{ command.signature }}</p>
                                    <p class="text-muted-foreground text-xs leading-relaxed">{{ command.summary }}</p>
                                </div>
                                <BaseButton
                                    :variant="command.risk === 'destructive' ? ColorVariant.danger_outline : ColorVariant.primary"
                                    type="button"
                                    :disabled="!canRunCommand(command) || Boolean(liveRunsByCommand[command.key])"
                                    :title="runDisabledHint(command)"
                                    class="shrink-0"
                                    @click="openRun(command)"
                                >
                                    <component :is="icons[IconName.play]" class="size-4" aria-hidden="true" />
                                    {{ liveRunsByCommand[command.key] ? $t('console.running') : $t('console.run') }}
                                </BaseButton>
                            </div>
                            <RunConsolePane
                                v-if="liveRunsByCommand[command.key]"
                                :run-uuid="liveRunsByCommand[command.key].uuid"
                                @finished="markRunFinished(liveRunsByCommand[command.key].uuid, $event)"
                            />
                        </article>
                    </div>
                </BaseAccordionItem>
            </BaseAccordion>

            <RunHistory :runs="runs" />
        </div>

        <RunCommandModal
            v-if="selectedCommand"
            ref="modal"
            :command="selectedCommand"
            :processing="dispatching"
            @submit="dispatchCommand"
            @close="closeModal"
        />
    </PageContainer>
</template>
