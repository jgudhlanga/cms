<script setup lang="ts">

import { IconName, icons } from '@/lib/icons';
import { Button } from '@/components/ui/button';
import { StepperTrigger } from '@/components/ui/stepper';

defineProps<{state: any}>()
</script>

<template>
	<StepperTrigger as-child>
		<Button
			:variant="state === 'completed' || state === 'active' ? 'default' : 'outline'"
			size="icon"
			class="z-10 rounded-full shrink-0"
			:class="[state === 'active' && 'ring-2 ring-ring ring-offset-2 ring-offset-background']"
			:disabled="state !== 'completed'"
		>
			<component :is="icons[IconName.check]" v-if="state === 'completed'" class="size-5" />
			<component :is="icons[IconName.circle]" v-if="state === 'active'" class="size-5" />
			<component :is="icons[IconName.dot]" v-if="state === 'inactive'" class="size-5" />
		</Button>
	</StepperTrigger>
</template>
