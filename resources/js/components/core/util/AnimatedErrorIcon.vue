<script setup lang="ts"></script>

<template>
    <div class="error-icon animate-shake mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white">
            <svg class="h-12 w-12 text-red-600" viewBox="0 0 52 52">
                <path
                    d="M26 0C11.664 0 0 11.663 0 26s11.664 26 26 26 26-11.663 26-26S40.336 0 26 0zm0 50C12.767 50 2 39.233 2 26S12.767 2 26 2s24 10.767 24 24-10.767 24-24 24z"
                />
                <path
                    d="M35.707 16.293a.999.999 0 0 0-1.414 0L26 24.586l-8.293-8.293a.999.999 0 1 0-1.414 1.414L24.586 26l-8.293 8.293a.999.999 0 1 0 1.414 1.414L26 27.414l8.293 8.293a.997.997 0 0 0 1.414 0 .999.999 0 0 0 0-1.414L27.414 26l8.293-8.293a.999.999 0 0 0 0-1.414z"
                />
            </svg>
        </div>
    </div>
</template>
<style scoped>
@keyframes shake {
    0%,
    100% {
        transform: translateX(0);
    }
    10%,
    30%,
    50%,
    70%,
    90% {
        transform: translateX(-5px);
    }
    20%,
    40%,
    60%,
    80% {
        transform: translateX(5px);
    }
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-shake {
    animation: shake 0.8s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

.animate-pulse-slow {
    animation: pulse 2s infinite;
}

.error-icon {
    position: relative;
}

.error-icon::before,
.error-icon::after {
    content: '';
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
    left: 0;
    border-radius: 50%;
    background-color: rgba(239, 68, 68, 0.2);
    z-index: -1;
}

.error-icon::before {
    animation: pulse 2s infinite;
}

.error-icon::after {
    animation: pulse 2s 0.5s infinite;
}
</style>
