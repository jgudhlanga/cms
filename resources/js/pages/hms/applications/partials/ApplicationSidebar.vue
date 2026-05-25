<script setup lang="ts">
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useHms } from '@/composables/hms/useHms';
import type { HostelApplicationSidebarItem } from '@/types/hms';
import { onMounted, ref, watch } from 'vue';

interface Props {
    currentApplicationId: string | number;
}

const props = defineProps<Props>();

const { fetchPendingApplicationQueue } = useHms();
const pendingApplications = ref<HostelApplicationSidebarItem[]>([]);
const isLoading = ref(false);

const loadPendingApplications = async (): Promise<void> => {
    isLoading.value = true;
    try {
        pendingApplications.value = await fetchPendingApplicationQueue(props.currentApplicationId);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    void loadPendingApplications();
});

watch(
    () => props.currentApplicationId,
    () => {
        void loadPendingApplications();
    },
);
</script>

<template>
    <div class="flex flex-col space-y-3">
        <HeadingSmall
            :title="$t('trans.ui_next_5')"
            :description="$t('hms.sidebar_pending_description')"
        />
        <p v-if="isLoading" class="text-sm text-muted-foreground">
            {{ $t('trans.loading') }}…
        </p>
        <p v-else-if="pendingApplications.length === 0" class="text-sm text-muted-foreground">
            {{ $t('trans.no_data') }}
        </p>
        <div v-else class="flex flex-col space-y-2">
            <TextLink
                v-for="application in pendingApplications"
                :key="application.id"
                classes="rounded-md border-r-2 border-black bg-gray-200 px-3 py-2 text-xs uppercase text-accent-foreground"
                :title="application.displayName"
                :href="route('hostels.applications.show', String(application.id))"
            />
        </div>
    </div>
</template>
