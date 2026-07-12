<script setup lang="ts">
import {
    courseWorkModuleDotAccent,
    courseWorkModuleStatusBadgeClass,
    courseWorkModuleStatusLabel,
} from '@/composables/students/studentProgrammeDisplay';
import type { CourseWorkModuleListItem } from '@/types/students';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    module: CourseWorkModuleListItem;
    accentIndex?: number;
    mode?: 'navigate' | 'expandable';
    expanded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    accentIndex: 0,
    mode: 'navigate',
    expanded: false,
});

const emit = defineEmits<{
    activate: [];
}>();

const statusLabel = computed(() => courseWorkModuleStatusLabel(props.module.statusKey));
const statusBadgeClass = computed(() => courseWorkModuleStatusBadgeClass(props.module.statusKey));
const dotAccent = computed(() => courseWorkModuleDotAccent(props.accentIndex));
</script>

<template>
    <button
        type="button"
        class="group flex w-full min-w-0 items-center gap-3 px-4 py-3.5 text-left transition-colors hover:bg-muted/40 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
        @click="emit('activate')"
    >
        <span
            class="size-2 shrink-0 rounded-full"
            :class="dotAccent"
        />
        <div class="min-w-0 flex-1">
            <p class="wrap-break-word text-sm font-medium leading-snug text-foreground sm:truncate">
                {{ module.name }}
            </p>
            <p
                v-if="module.code"
                class="mt-0.5 font-mono text-xs text-muted-foreground"
            >
                {{ module.code }}
            </p>
        </div>
        <span
            class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-1 text-xs font-medium"
            :class="statusBadgeClass"
        >
            {{ statusLabel }}
        </span>
        <ChevronDown
            v-if="mode === 'expandable'"
            class="size-4 shrink-0 text-muted-foreground transition-transform duration-200"
            :class="{ 'rotate-180': expanded }"
        />
        <ChevronRight
            v-else
            class="size-4 shrink-0 text-muted-foreground"
        />
    </button>
</template>
