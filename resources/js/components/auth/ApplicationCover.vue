<script setup lang="ts">

</script>

<template>
    <InfiniteBackground />
    <div class="flex flex-col items-center justify-center space-y-4 overflow-hidden py-8">
        <div class="my-5 flex size-20 flex-col items-center justify-center overflow-hidden rounded-3xl">
            <AppLogo classes="block h-full w-full object-contain" />
        </div>
        <slot/>
    </div>
</template>
