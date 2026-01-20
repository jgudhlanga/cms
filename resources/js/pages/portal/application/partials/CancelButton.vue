<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';

const {navigateTo} = useUtils()
</script>

<template>
    <button @click="() => navigateTo(route('portal.application.level-options'))" class="cancel-button">
        {{ $t('trans.cancel') }}
    </button>
</template>

<style scoped>
.cancel-button {
    display: inline-flex;
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, red 0%, palevioletred 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: 600;
    justify-content: center;
    align-items: center;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
</style>
