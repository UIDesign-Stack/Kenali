<template>
    <div class="blobs" aria-hidden="true">
        <span class="b1"></span>
        <span class="b2"></span>
        <span class="b3"></span>
        <span class="b4"></span>
        <span class="b5"></span>
    </div>
</template>

<style scoped>
.blobs { position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
.blobs span { position: absolute; border-radius: 50%; filter: blur(80px); }
.b1 { top: -120px; right: -140px; width: 520px; height: 520px; background: #9277ff; opacity: 0.38; }
.b2 { top: 16%; left: -180px; width: 460px; height: 460px; background: #ff5c93; opacity: 0.26; }
.b3 { top: 40%; right: -160px; width: 480px; height: 480px; background: #4db0ff; opacity: 0.3; }
.b4 { top: 64%; left: -140px; width: 440px; height: 440px; background: #9277ff; opacity: 0.26; }
.b5 { top: 84%; right: -120px; width: 420px; height: 420px; background: #ff5c93; opacity: 0.22; }
</style>
