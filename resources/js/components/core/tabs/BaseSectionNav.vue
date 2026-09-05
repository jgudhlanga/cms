<script setup lang="ts">
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import type { CustomTab } from '@/types/utils';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch, type ComponentPublicInstance } from 'vue';

const props = withDefaults(
    defineProps<{
        tabs: CustomTab[];
        activeTab: string;
        layout?: 'horizontal' | 'vertical';
        variant?: 'pills' | 'underline';
        grouped?: boolean;
        description?: string;
        ariaLabel?: string;
        badgeCounts?: Record<string, number | undefined>;
        navId?: string;
        dense?: boolean;
    }>(),
    {
        layout: 'horizontal',
        variant: 'pills',
        grouped: true,
        ariaLabel: 'Section navigation',
        dense: false,
    },
);

const emit = defineEmits<{
    'update:activeTab': [value: string];
}>();

const isHorizontal = computed(() => props.layout === 'horizontal');
const isPills = computed(() => props.variant === 'pills');
// The sliding indicator only fits a continuous pill "track" (the grouped bg-muted case).
// Standalone pills are individually bordered cards, and the underline variant isn't
// part of this pass — the same mechanism could be pointed at it later if wanted.
const hasSlidingIndicator = computed(() => isPills.value && props.grouped);

const generatedNavId = useId();
const resolvedNavId = computed(() => props.navId ?? generatedNavId);
const tabButtonId = (tab: CustomTab): string => `${resolvedNavId.value}-tab-${tab.value}`;
const tabPanelId = (tab: CustomTab): string => `${resolvedNavId.value}-panel-${tab.value}`;

const focusRingClass = 'outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const navClass = computed(() =>
    cn(
        'flex w-full min-w-0',
        isPills.value
            ? isHorizontal.value
                ? 'flex-row gap-2 overflow-x-auto pb-1'
                : 'flex-col gap-1'
            : cn('gap-0', isHorizontal.value ? 'border-border flex-row overflow-x-auto border-b' : 'border-border flex-col border-r'),
    ),
);

const pillsContainerClass = computed(() => {
    if (!isPills.value) {
        return '';
    }

    if (!props.grouped) {
        return isHorizontal.value ? 'inline-flex w-fit min-w-0 flex-wrap items-center gap-2' : 'flex w-full flex-col gap-1';
    }

    const trackPadding = props.dense ? 'p-1' : 'p-1.5';

    return isHorizontal.value
        ? cn('bg-muted relative inline-flex w-fit min-w-0 items-center gap-1 rounded-xl', trackPadding)
        : cn('bg-muted relative flex w-full flex-col gap-1 rounded-xl', trackPadding);
});

const navButtonClass = (isActive: boolean, isDisabled: boolean): string => {
    if (isPills.value) {
        const standaloneClass = !props.grouped ? 'rounded-lg border border-border bg-card shadow-sm' : 'rounded-lg';

        return cn(
            'relative z-10 inline-flex items-center font-medium transition-[color,box-shadow,background-color,border-color]',
            props.dense ? 'gap-2 px-3 py-1.5 text-[13px]' : 'gap-2.5 px-4 py-2.5 text-sm',
            focusRingClass,
            standaloneClass,
            isHorizontal.value ? cn('shrink-0 justify-center', !props.dense && 'min-w-[5.5rem]') : 'w-full justify-start',
            isDisabled
                ? 'cursor-not-allowed opacity-50'
                : isActive
                  ? props.grouped
                      ? 'text-primary'
                      : 'border-primary/30 bg-primary/10 text-primary shadow-sm'
                  : props.grouped
                    ? 'text-muted-foreground hover:bg-muted/60 hover:text-foreground'
                    : 'text-muted-foreground hover:border-border hover:bg-muted/40 hover:text-foreground',
        );
    }

    return cn(
        'relative inline-flex items-center gap-2 text-sm transition-colors',
        focusRingClass,
        isHorizontal.value ? '-mb-px shrink-0 border-b-2 px-4 py-2.5' : 'w-full border-l-2 px-4 py-2.5',
        isDisabled
            ? 'cursor-not-allowed opacity-50'
            : isActive
              ? isHorizontal.value
                  ? 'border-primary text-foreground font-medium'
                  : 'border-primary bg-muted/50 text-foreground font-medium'
              : isHorizontal.value
                ? 'text-muted-foreground hover:border-border hover:text-foreground border-transparent'
                : 'text-muted-foreground hover:bg-muted/30 hover:text-foreground border-transparent',
    );
};

const labelClass = (isActive: boolean): string => cn('truncate', !isPills.value && 'uppercase', isPills.value && isActive && 'font-medium');

const activateTab = (tab: CustomTab): void => {
    if (tab.disabled) {
        return;
    }

    emit('update:activeTab', tab.value);
};

const onTabClick = (tab: CustomTab): void => activateTab(tab);

// Roving tabindex: only one tab is a native Tab-key stop at a time (the active tab,
// or the first enabled tab if the active value is currently missing/disabled).
const tabRefs = new Map<string, HTMLButtonElement>();

const setTabRef = (value: string, el: Element | ComponentPublicInstance | null): void => {
    if (el instanceof HTMLButtonElement) {
        tabRefs.set(value, el);
    } else {
        tabRefs.delete(value);
    }
};

const enabledTabs = computed(() => props.tabs.filter((tab) => !tab.disabled));

const rovingTabValue = computed(() => {
    if (enabledTabs.value.some((tab) => tab.value === props.activeTab)) {
        return props.activeTab;
    }

    return enabledTabs.value[0]?.value;
});

const tabIndexFor = (tab: CustomTab): string => (tab.value === rovingTabValue.value ? '0' : '-1');

const onTabKeydown = (event: KeyboardEvent, tab: CustomTab): void => {
    const list = enabledTabs.value;
    if (list.length === 0) {
        return;
    }

    const forwardKey = isHorizontal.value ? 'ArrowRight' : 'ArrowDown';
    const backwardKey = isHorizontal.value ? 'ArrowLeft' : 'ArrowUp';
    const currentIndex = list.findIndex((t) => t.value === tab.value);

    let nextTab: CustomTab | undefined;

    if (event.key === forwardKey) {
        nextTab = list[(currentIndex + 1) % list.length];
    } else if (event.key === backwardKey) {
        nextTab = list[(currentIndex - 1 + list.length) % list.length];
    } else if (event.key === 'Home') {
        nextTab = list[0];
    } else if (event.key === 'End') {
        nextTab = list[list.length - 1];
    } else {
        return;
    }

    event.preventDefault();
    activateTab(nextTab);
    tabRefs.get(nextTab.value)?.focus();
};

// Mobile scroll affordance: fade the scrollable edges, but only when there's
// actually more content to scroll to on that side.
const navEl = ref<HTMLElement | null>(null);
const showLeftFade = ref(false);
const showRightFade = ref(false);
let fadeRafPending = false;

const FADE_WIDTH_PX = 24;

const updateFadeState = (): void => {
    if (!isPills.value || !isHorizontal.value || !navEl.value) {
        showLeftFade.value = false;
        showRightFade.value = false;
        return;
    }

    const el = navEl.value;
    showLeftFade.value = el.scrollLeft > 1;
    showRightFade.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 1;
};

const onNavScroll = (): void => {
    if (fadeRafPending) {
        return;
    }

    fadeRafPending = true;
    requestAnimationFrame(() => {
        fadeRafPending = false;
        updateFadeState();
    });
};

const navMaskStyle = computed(() => {
    if (!showLeftFade.value && !showRightFade.value) {
        return {};
    }

    const leftStop = showLeftFade.value ? `${FADE_WIDTH_PX}px` : '0px';
    const rightStop = showRightFade.value ? `calc(100% - ${FADE_WIDTH_PX}px)` : '100%';
    const mask = `linear-gradient(to right, transparent, black ${leftStop}, black ${rightStop}, transparent 100%)`;

    return { maskImage: mask, WebkitMaskImage: mask };
});

// Sliding active-tab indicator (grouped pills only, see hasSlidingIndicator above).
const trackEl = ref<HTMLElement | null>(null);
const indicatorStyle = ref<Record<string, string>>({});
const indicatorReady = ref(false);
const indicatorAnimatable = ref(false);

const updateIndicator = (): void => {
    if (!hasSlidingIndicator.value) {
        indicatorReady.value = false;
        return;
    }

    const target = tabRefs.get(props.activeTab);
    if (!target || !trackEl.value) {
        indicatorReady.value = false;
        return;
    }

    indicatorStyle.value = {
        transform: `translate(${target.offsetLeft}px, ${target.offsetTop}px)`,
        width: `${target.offsetWidth}px`,
        height: `${target.offsetHeight}px`,
    };
    indicatorReady.value = true;
};

const indicatorClass = computed(() =>
    cn(
        'bg-primary/10 absolute top-0 left-0 z-0 rounded-lg shadow-sm',
        isHorizontal.value ? 'transition-[transform,width] duration-200 ease-out' : 'transition-[transform,height] duration-200 ease-out',
        !indicatorAnimatable.value && 'transition-none',
    ),
);

let navResizeObserver: ResizeObserver | undefined;
let trackResizeObserver: ResizeObserver | undefined;

onMounted(() => {
    nextTick(() => {
        updateFadeState();
        updateIndicator();
        // Snap into place first, only start animating on tab changes after this frame
        // has painted — otherwise the indicator visibly flies in from the top-left corner.
        requestAnimationFrame(() => {
            indicatorAnimatable.value = true;
        });
    });

    if (navEl.value) {
        navEl.value.addEventListener('scroll', onNavScroll, { passive: true });
        navResizeObserver = new ResizeObserver(() => updateFadeState());
        navResizeObserver.observe(navEl.value);
    }

    if (trackEl.value) {
        trackResizeObserver = new ResizeObserver(() => updateIndicator());
        trackResizeObserver.observe(trackEl.value);
    }
});

onBeforeUnmount(() => {
    navEl.value?.removeEventListener('scroll', onNavScroll);
    navResizeObserver?.disconnect();
    trackResizeObserver?.disconnect();
});

watch(
    () => props.activeTab,
    () => nextTick(updateIndicator),
);

watch(
    () => props.tabs,
    () =>
        nextTick(() => {
            updateFadeState();
            updateIndicator();
        }),
);
</script>

<template>
    <div class="min-w-0" :class="dense ? 'space-y-1.5' : 'space-y-2'">
        <nav ref="navEl" :class="navClass" :aria-label="ariaLabel" :style="navMaskStyle">
            <div ref="trackEl" :class="pillsContainerClass" role="tablist" :aria-orientation="layout">
                <div v-if="hasSlidingIndicator" v-show="indicatorReady" :class="indicatorClass" :style="indicatorStyle" aria-hidden="true" />
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    :ref="(el) => setTabRef(tab.value, el)"
                    type="button"
                    role="tab"
                    :id="tabButtonId(tab)"
                    :aria-controls="tabPanelId(tab)"
                    :aria-selected="activeTab === tab.value ? 'true' : 'false'"
                    :tabindex="tabIndexFor(tab)"
                    :disabled="tab.disabled"
                    :class="navButtonClass(activeTab === tab.value, !!tab.disabled)"
                    @click="onTabClick(tab)"
                    @keydown="onTabKeydown($event, tab)"
                >
                    <component v-if="tab.icon" :is="icons[tab.icon]" class="shrink-0" :class="dense ? 'h-4 w-4' : 'h-4.5 w-4.5'" />
                    <span :class="labelClass(activeTab === tab.value)">{{ tab.transLabel?.() }}</span>
                    <span
                        v-if="badgeCounts?.[tab.value] && badgeCounts[tab.value]! > 0"
                        class="bg-destructive text-destructive-foreground rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                    >
                        {{ badgeCounts[tab.value] }}
                    </span>
                </button>
            </div>
        </nav>
        <p v-if="description" class="text-muted-foreground" :class="dense ? 'text-xs' : 'text-sm'">
            {{ description }}
        </p>
    </div>
</template>
