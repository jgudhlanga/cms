<script setup lang="ts">

import {
  HoverCard,
  HoverCardContent,
  HoverCardTrigger,
} from '@/components/ui/hover-card'

</script>

<template>
  <HoverCard>
    <HoverCardTrigger as-child>
	  <slot name="trigger" />
    </HoverCardTrigger>
    <HoverCardContent class="w-80">
		<slot name="content" />
    </HoverCardContent>
  </HoverCard>
</template>
