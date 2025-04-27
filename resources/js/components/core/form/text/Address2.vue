<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput input-id="address_2" :label="$t('trans.street_name')" v-bind="$attrs" placeholder="" />
</template>
