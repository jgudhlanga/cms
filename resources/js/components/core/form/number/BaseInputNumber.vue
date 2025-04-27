<script setup lang="ts">
import { Label } from '@/components/ui/label'
import {
  NumberField,
  NumberFieldContent,
  NumberFieldDecrement,
  NumberFieldIncrement,
  NumberFieldInput,
} from '@/components/ui/number-field'

withDefaults(defineProps<{ inputId: string, defaultValue?: number | undefined, min?: number, max?: number, label?: string }>(), {
	min: 0,
	defaultValue: 0,
})

</script>

<template>
  <NumberField
  	:id="inputId"
	:default-value="defaultValue"
	:min="0"
	v-bind="$attrs">
    <Label v-if="label" :for="inputId">{{ label }}</Label>
    <NumberFieldContent>
      <NumberFieldDecrement />
      <NumberFieldInput />
      <NumberFieldIncrement />
    </NumberFieldContent>
  </NumberField>
</template>