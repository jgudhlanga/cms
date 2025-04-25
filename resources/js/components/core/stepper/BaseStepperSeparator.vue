<script setup lang="ts">
import { StepperSeparator } from '@/components/ui/stepper';
</script>

<template>
	<StepperSeparator
		class="absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 block h-0.5 shrink-0 rounded-full bg-muted
		 group-data-[state=completed]:bg-primary"
	/>
</template>
