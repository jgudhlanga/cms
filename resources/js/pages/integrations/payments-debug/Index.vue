<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import LedgerList from '@/pages/integrations/payments-debug/partials/LedgerList.vue';
import StatusModal from '@/pages/integrations/payments-debug/partials/StatusModal.vue';
import HttpService from '@/services/http.service';
import { usePaymentDebugStore } from '@/store/integrations/usePaymentDebugStore';
import { PaymentDebugLedgerGroup, PaymentDebugMatchedBy, PaymentDebugPerson, PaymentDebugSearchResponse } from '@/types/integrations';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { trans, transChoice } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

defineProps<{
    canUpdate: boolean;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'trans.integrations' }, { transKey: 'trans.ui_payments_debug' }];

const store = usePaymentDebugStore();
const { search, reload } = storeToRefs(store);
const isSearching = ref(false);
const hasSearched = ref(false);
const matchedBy = ref<PaymentDebugMatchedBy | null>(null);
const person = ref<PaymentDebugPerson | null>(null);
const groups = ref<PaymentDebugLedgerGroup[]>([]);
const activeType = ref('');
const errorMessage = ref('');

const typeOptions = computed(() => groups.value.map((group) => ({ value: group.type, label: group.label })));

const visibleGroups = computed(() => {
    if (!activeType.value) {
        return groups.value;
    }

    return groups.value.filter((group) => group.type === activeType.value);
});

const personDetails = computed(() => {
    if (person.value === null) {
        return [];
    }

    return [
        { label: trans('trans.email'), value: person.value.email },
        { label: transChoice('trans.phone', 1), value: person.value.phone },
        { label: transChoice('trans.department', 1), value: person.value.department },
        { label: transChoice('trans.course', 1), value: person.value.course },
        { label: transChoice('trans.level', 1), value: person.value.level },
        { label: transChoice('trans.mode_of_study', 1), value: person.value.modeOfStudy },
    ].filter((detail) => Boolean(detail.value));
});

const matchedByLabel = computed(() => {
    if (matchedBy.value === 'email') {
        return trans('integrations.payments_debug_matched_email');
    }

    if (matchedBy.value === 'student_number') {
        return trans('integrations.payments_debug_matched_student_number');
    }

    if (matchedBy.value === 'reference') {
        return trans('integrations.payments_debug_matched_reference');
    }

    return '';
});

const searchLedger = async () => {
    const term = search.value.trim();

    if (term === '') {
        errorMessage.value = trans('integrations.payments_debug_enter_term');
        return;
    }

    isSearching.value = true;
    hasSearched.value = true;
    errorMessage.value = '';

    try {
        const response = (await HttpService.get(route('integrations.payments-debug.search', { q: term }))) as PaymentDebugSearchResponse;

        matchedBy.value = response.matchedBy;
        person.value = response.person;
        groups.value = response.groups ?? [];
        activeType.value = '';
    } catch (error: unknown) {
        matchedBy.value = null;
        person.value = null;
        groups.value = [];
        const axiosError = error as { response?: { data?: { message?: string } } };
        errorMessage.value = axiosError?.response?.data?.message ?? trans('integrations.payments_debug_no_results');
    } finally {
        isSearching.value = false;
    }
};

watch(search, () => {
    hasSearched.value = false;
    matchedBy.value = null;
    person.value = null;
    groups.value = [];
    activeType.value = '';
    errorMessage.value = '';
});

watch(reload, async (shouldReload) => {
    if (shouldReload) {
        await searchLedger();
        reload.value = false;
    }
});

onBeforeUnmount(() => {
    store.$reset();
});
</script>

<template>
    <Head :title="$t('trans.ui_payments_debug')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-5xl space-y-3 px-2 sm:px-4">
            <header class="border-border bg-card flex flex-wrap items-center gap-3 rounded-xl border px-4 py-3">
                <span class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                    <component :is="icons[IconName.search]" class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                    <h1 class="text-accent-foreground text-xs font-bold uppercase">{{ $t('integrations.payments_debug_title') }}</h1>
                    <p class="text-muted-foreground text-xs">{{ $t('integrations.payments_debug_description') }}</p>
                </div>
                <span
                    v-if="!canUpdate"
                    class="border-border text-muted-foreground inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                    :title="$t('integrations.payments_debug_view_only_hint')"
                >
                    {{ $t('integrations.view_only') }}
                </span>
            </header>

            <section class="border-border bg-card rounded-xl border">
                <form class="flex flex-col gap-4 px-4 py-4" @submit.prevent="searchLedger">
                    <BaseInput
                        input-id="payments-debug-search"
                        :label="$t('integrations.payments_debug_search_label')"
                        v-model="search"
                        :placeholder="$t('integrations.payments_debug_search_placeholder')"
                        :vertical-layout="true"
                        :label-uppercase="true"
                        :is-required="true"
                        autocomplete="off"
                    />
                    <div class="text-muted-foreground -mt-2 space-y-1 text-xs">
                        <p>{{ $t('integrations.payments_debug_search_hint') }}</p>
                        <p>{{ $t('integrations.payments_debug_search_hint_secondary') }}</p>
                    </div>
                    <p v-if="errorMessage" class="text-destructive -mt-1 text-xs font-semibold">{{ errorMessage }}</p>
                    <div class="flex justify-end">
                        <BaseButton type="submit" :processing="isSearching" :title="$t('integrations.payments_debug_search')">
                            <component :is="icons[IconName.search]" class="size-4" aria-hidden="true" />
                        </BaseButton>
                    </div>
                </form>
            </section>

            <p v-if="matchedByLabel && !person" class="text-muted-foreground px-1 text-xs font-medium">{{ matchedByLabel }}</p>

            <section v-if="person" class="border-border bg-card rounded-xl border px-4 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                        <component :is="icons[IconName.user]" class="size-5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-accent-foreground text-sm font-semibold">{{ person.name }}</p>
                        <p class="text-muted-foreground text-xs">{{ matchedByLabel }}</p>
                    </div>
                    <span
                        v-if="person.studentNumber"
                        class="border-border text-accent-foreground rounded-full border px-2 py-0.5 font-mono text-[11px] font-semibold"
                        :title="$t('integrations.payments_debug_student_number')"
                    >
                        {{ person.studentNumber }}
                    </span>
                </div>
                <dl
                    v-if="personDetails.length > 0"
                    class="border-border mt-3 grid grid-cols-1 gap-x-6 gap-y-3 border-t pt-3 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div v-for="detail in personDetails" :key="detail.label" class="min-w-0">
                        <dt class="text-muted-foreground text-[11px] font-semibold uppercase">{{ detail.label }}</dt>
                        <dd class="text-accent-foreground truncate text-sm" :title="String(detail.value)">{{ detail.value }}</dd>
                    </div>
                </dl>
            </section>

            <div v-if="typeOptions.length > 1" class="flex flex-wrap gap-2 px-1">
                <button
                    type="button"
                    :class="
                        cn(
                            'rounded-full border px-3 py-1 text-[11px] font-semibold uppercase',
                            activeType === '' ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted',
                        )
                    "
                    @click="activeType = ''"
                >
                    {{ $t('integrations.payments_debug_results') }}
                </button>
                <button
                    v-for="option in typeOptions"
                    :key="option.value"
                    type="button"
                    :class="
                        cn(
                            'rounded-full border px-3 py-1 text-[11px] font-semibold uppercase',
                            activeType === option.value
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-border text-muted-foreground hover:bg-muted',
                        )
                    "
                    @click="activeType = option.value"
                >
                    {{ option.label }}
                </button>
            </div>

            <div v-if="visibleGroups.length > 0 && !isSearching" class="space-y-6">
                <section v-for="group in visibleGroups" :key="group.type" class="border-border bg-card rounded-xl border">
                    <div class="px-4 py-3">
                        <HeadingSmall :title="group.label" />
                    </div>
                    <LedgerList :ledgers="group.ledgers" />
                </section>
            </div>

            <p
                v-else-if="hasSearched && !isSearching && !errorMessage"
                class="text-muted-foreground border-border bg-card rounded-xl border px-4 py-8 text-center text-sm"
            >
                {{ $t('integrations.payments_debug_no_results') }}
            </p>
            <p v-else-if="!hasSearched" class="text-muted-foreground px-1 text-sm">
                {{ $t('integrations.payments_debug_empty') }}
            </p>
        </div>
        <StatusModal :can-update="canUpdate" />
    </PageContainer>
</template>
