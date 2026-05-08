<script setup lang="ts">
withDefaults(
    defineProps<{
        item: any;
    }>(),
    {},
);
const styles = 'uppercase text-xs font-normal';
</script>

<template>
    <span :class="styles" v-if="item?.transChoiceKey != null">{{ $tChoice(item?.transChoiceKey, item?.transChoiceKeyIndex ?? 2) }}</span>
    <span :class="styles" v-else-if="item?.transKey != null">{{ $t(item?.transKey) }}</span>
    <span :class="styles" v-else>{{ item?.title }}</span>
</template>
