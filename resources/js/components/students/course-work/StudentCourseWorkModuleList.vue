<script setup lang="ts">
import StudentCourseWorkModuleRow from '@/components/students/course-work/StudentCourseWorkModuleRow.vue';
import type { CourseWorkModuleListItem } from '@/types/students';
import { ArrowRight } from 'lucide-vue-next';

interface Props {
    modules: CourseWorkModuleListItem[];
    title?: string;
    subtitle?: string;
    viewAllHref?: string;
    viewAllLabel?: string;
    mode?: 'navigate' | 'expandable';
    emptyMessage?: string;
    isExpanded?: (index: number) => boolean;
}

withDefaults(defineProps<Props>(), {
    mode: 'navigate',
});

const emit = defineEmits<{
    activate: [module: CourseWorkModuleListItem, index: number];
    viewAll: [];
}>();
</script>

<template>
    <section class="w-full min-w-0 rounded-2xl border border-border bg-card shadow-sm">
        <div
            v-if="title || $slots.header"
            class="flex min-w-0 items-start justify-between gap-3 px-4 py-3.5"
        >
            <slot name="header">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-foreground">
                        {{ title }}
                    </h2>
                    <p
                        v-if="subtitle"
                        class="mt-0.5 wrap-break-word text-sm text-muted-foreground"
                    >
                        {{ subtitle }}
                    </p>
                </div>
            </slot>
            <a
                v-if="viewAllHref"
                :href="viewAllHref"
                class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-primary hover:underline"
                @click.prevent="emit('viewAll')"
            >
                {{ viewAllLabel }}
                <ArrowRight class="size-4" />
            </a>
        </div>

        <div
            v-if="modules.length === 0"
            class="px-4 pb-4"
        >
            <slot name="empty">
                <div class="rounded-xl border border-dashed border-border py-8 text-center text-sm text-muted-foreground">
                    {{ emptyMessage }}
                </div>
            </slot>
        </div>

        <ul
            v-else
            class="divide-y divide-border border-t border-border"
        >
            <li
                v-for="(module, index) in modules"
                :key="module.id"
                class="min-w-0"
            >
                <StudentCourseWorkModuleRow
                    :module="module"
                    :accent-index="index"
                    :mode="mode"
                    :expanded="isExpanded ? isExpanded(index) : false"
                    @activate="emit('activate', module, index)"
                />
                <slot
                    name="row-details"
                    :module="module"
                    :index="index"
                />
            </li>
        </ul>
    </section>
</template>
