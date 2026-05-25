<script setup lang="ts">
interface Props {
    title: string;
}
defineProps<Props>();
</script>

<template>
    <button v-bind="$attrs" class="button">
        {{ title }}
    </button>
</template>

<style scoped>
.button {
    display: inline-flex;
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #2342f5 0%, #00d2ff 100%);
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
