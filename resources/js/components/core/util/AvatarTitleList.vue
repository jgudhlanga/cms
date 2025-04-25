<script setup lang="ts">

import { Link } from '@inertiajs/vue3';
import ItemTitle from '@/components/core/util/ItemTitle.vue';
import Avatar from './Avatar.vue';

defineProps<{ tabs: Array<any> | null | undefined }>();
</script>

<template>
	<div class="w-full grid grid-cols-3 gap-3 mt-3 md:grid-cols-6 md:gap-6">
		<Link v-for="tab in tabs!" :href="tab?.url ?? ''" :key="tab?.url">
			<div class="flex items-center space-x-2">
				<div class="hidden md:flex">
					<Avatar src="" :name="$tChoice('trans.' + tab?.transChoiceKey, 2)" />
				</div>
				<ItemTitle :title="$tChoice('trans.' + tab?.transChoiceKey, 2)" />
			</div>
		</Link>
	</div>
</template>
