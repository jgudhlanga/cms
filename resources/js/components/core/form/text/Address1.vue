<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput input-id="address_1" :label="$t('trans.street_number')" v-bind="$attrs" placeholder="" />
</template>
