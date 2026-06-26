<script setup lang="ts">
import { ADDRESS_FIELDS } from '@/lib/addressFields';
import BaseInput from './BaseInput.vue';

const field = ADDRESS_FIELDS[1];
</script>

<template>
    <BaseInput
        input-id="address_2"
        :label="$t(field.labelKey)"
        :placeholder="$t(field.placeholderKey)"
        v-bind="$attrs"
    />
</template>
