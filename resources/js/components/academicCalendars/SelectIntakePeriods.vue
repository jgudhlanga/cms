<script setup lang="ts">
import { IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { computed } from 'vue';

const props = defineProps<{ data: IntakePeriod[] }>();

const linksOptions = computed(() => {
    return props.data.length > 0
        ? props.data.map(
              (intakePeriod: IntakePeriod) =>
                  <SelectOption>{
                      value: String(intakePeriod.id),
                      label: `${intakePeriod?.attributes?.name}`,
                  },
          )
        : [];
});
</script>

<template>
    <div class="flex w-full items-center space-x-2">
        <BaseSelect
            class="w-75"
            label=""
            v-bind="$attrs"
            placeholder=""
            :options="linksOptions"
            :is-searchable="false"
            :is-clearable="false"
            :is-multi="true"
        />
    </div>
</template>
