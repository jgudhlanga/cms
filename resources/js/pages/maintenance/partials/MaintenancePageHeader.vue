<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

defineProps<{
    sectionDescription?: string;
}>();

const descriptionOpen = ref(false);
</script>

<template>
    <div class="space-y-1">
        <h1 class="text-lg font-semibold text-foreground">
            {{ trans('trans.maintenance') }}
        </h1>
        <p class="text-sm text-muted-foreground">
            {{ trans('trans.maintenance_description') }}
        </p>

        <div v-if="sectionDescription" class="pt-1">
            <button
                type="button"
                class="inline-flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                :aria-expanded="descriptionOpen"
                @click="descriptionOpen = !descriptionOpen"
            >
                <ChevronDown
                    class="h-3.5 w-3.5 shrink-0 transition-transform duration-200"
                    :class="cn(descriptionOpen && 'rotate-180')"
                />
                {{ trans('trans.maintenance_about_section') }}
            </button>
            <p
                v-show="descriptionOpen"
                class="mt-1.5 text-xs leading-relaxed text-muted-foreground"
            >
                {{ sectionDescription }}
            </p>
        </div>
    </div>
</template>
