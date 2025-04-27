<script setup lang="ts">
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import HeadingSmall from '../util/HeadingSmall.vue';

interface Props {
	value: string;
	title?: string;
	defaultValue?: string,
}

defineProps<Props>();

</script>

<template>
  <Accordion type="single" class="w-full" collapsible :default-value="defaultValue">
    <AccordionItem class="border-b-0"  :value="value">
      <AccordionTrigger class="hover:no-underline">
		<HeadingSmall v-if="title" :title="title"/>
	  </AccordionTrigger>
      <AccordionContent>
		<slot />
      </AccordionContent>
    </AccordionItem>
  </Accordion>
</template>
