<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { watch } from 'vue';
import { useDepartmentTabsStore } from '@/store/institution/useDepartmentTabsStore';

const { activeTab } = storeToRefs(useDepartmentTabsStore());

watch(activeTab, (newVal) => {
    if (newVal === 'staff') {
        handleStaffTabActivated();
    }
});

function handleStaffTabActivated() {
    console.log('Staff tab is now active!');
    // Your logic here
}
</script>

<template>
    {{ activeTab }}
</template>
