<script setup lang="ts">
import ItemTitle from '@/components/core/util/ItemTitle.vue';
import { useUtils } from '@/composables/core/useUtils';
import { Link } from '@inertiajs/vue3';
import Avatar from './Avatar.vue';

defineProps<{ tabs: Array<any> | null | undefined }>();

const { getTransFile } = useUtils();
</script>

<template>
    <div class="mt-3 grid w-full grid-cols-2 gap-3 md:grid-cols-5 md:gap-6">
        <Link v-for="tab in tabs!" :href="tab?.url ?? ''" :key="tab?.url">
            <div class="flex items-center space-x-2">
                <div class="hidden md:flex">
                    <Avatar src="" :name="$tChoice(getTransFile(tab), 2)" />
                </div>
                <ItemTitle :title="$tChoice(getTransFile(tab), 2)" />
            </div>
        </Link>
    </div>
</template>
