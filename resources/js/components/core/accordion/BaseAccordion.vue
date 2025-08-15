<script setup lang="ts">
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { AccordionItemProps } from '@/types/utils';
import { ref } from 'vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';

interface Props {
    defaultValue: string;
    items: AccordionItemProps[];
}

defineProps<Props>();
const openItem = ref('courses');
</script>
<template>
    {{ openItem }}
    <Accordion :value="openItem" type="single" class="w-full" collapsible :default-value="defaultValue" @onValueChange="openItem = $event">
        <AccordionItem v-for="item in items" class="border-0" :value="item.value" :key="item.value">
            <AccordionTrigger class="cursor-pointer hover:no-underline">
                <HeadingSmall :title="item.title()" :description="item.description ? item.description() : ''" />
            </AccordionTrigger>
            <AccordionContent>
                <component :is="item.content" />
            </AccordionContent>
            <CustomSeparator classes="h-1" />
        </AccordionItem>
    </Accordion>
</template>
