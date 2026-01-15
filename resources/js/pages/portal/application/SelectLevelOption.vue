<script setup lang="ts">
// UI components
import { computed, onMounted, ref } from 'vue';


// Utilities

// Props
interface Props {
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;

onMounted(async () => {
});


const save = async () => {

};
</script>
<template>
    <StudentPageHeader />
    <form @submit.prevent="() => save()">
        <div class="mt-20 flex w-full flex-col bg-white px-5 md:p-0">

        </div>
    </form>
</template>
