<script setup lang="ts"></script>

<template>
    <div class="area">
        <ul class="circles">
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>
</template>
<style scoped>
/*Start Animations*/
@-webkit-keyframes animatetop {
    from {
        top: -300px;
        opacity: 0;
    }
    to {
        top: 0;
        opacity: 1;
    }
}

@keyframes animatetop {
    from {
        top: -300px;
        opacity: 0;
    }
    to {
        top: 0;
        opacity: 1;
    }
}

@-webkit-keyframes zoomIn {
    0% {
        opacity: 0;
        -webkit-transform: scale3d(0.3, 0.3, 0.3);
        transform: scale3d(0.3, 0.3, 0.3);
    }
    50% {
        opacity: 1;
    }
}

@keyframes zoomIn {
    0% {
        opacity: 0;
        -webkit-transform: scale3d(0.3, 0.3, 0.3);
        transform: scale3d(0.3, 0.3, 0.3);
    }
    50% {
        opacity: 1;
    }
}

/*End Animations*/
/*
-- Start BackGround Animation
*/
.area {
    background: white;
    background: -webkit-linear-gradient(to left, #6b7ee5, #2342f5);
    width: 100%;
    height: 100vh;
    position: absolute;
    z-index: -1;
}

.circles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 96%;
    overflow: hidden;
}

.circles li {
    position: absolute;
    display: block;
    list-style: none;
    width: 10px;
    height: 10px;
    background: gray;
    animation: animate 25s linear infinite;
    bottom: -150px;
}

.circles li:nth-child(1) {
    left: 8%;
    width: 10px;
    height: 10px;
    animation-delay: 0s;
}

.circles li:nth-child(2) {
    left: 10%;
    width: 10px;
    height: 10px;
    animation-delay: 2s;
    animation-duration: 12s;
}

.circles li:nth-child(3) {
    left: 15%;
    width: 10px;
    height: 10px;
    animation-delay: 4s;
}

.circles li:nth-child(4) {
    left: 17%;
    width: 10px;
    height: 10px;
    animation-delay: 0s;
    animation-duration: 18s;
}

.circles li:nth-child(5) {
    left: 20%;
    width: 10px;
    height: 10px;
    animation-delay: 0s;
}

.circles li:nth-child(6) {
    left: 80%;
    width: 10px;
    height: 10px;
    animation-delay: 3s;
}

.circles li:nth-child(7) {
    left: 85%;
    width: 10px;
    height: 10px;
    animation-delay: 7s;
}

.circles li:nth-child(8) {
    left: 90%;
    width: 10px;
    height: 10px;
    animation-delay: 15s;
    animation-duration: 45s;
}

.circles li:nth-child(9) {
    left: 95%;
    width: 10px;
    height: 10px;
    animation-delay: 2s;
    animation-duration: 35s;
}

.circles li:nth-child(10) {
    left: 96%;
    width: 10px;
    height: 10px;
    animation-delay: 0s;
    animation-duration: 11s;
}

@keyframes animate {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
        border-radius: 0;
    }

    100% {
        transform: translateY(-1000px) rotate(720deg);
        opacity: 0;
        border-radius: 50%;
    }
}
</style>
