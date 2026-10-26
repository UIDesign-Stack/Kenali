<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import BackgroundBlobs from '@/Components/Landing/BackgroundBlobs.vue';
import { Head, Link } from '@inertiajs/vue3';
import '../../css/kenali.css';

const year = new Date().getFullYear();
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=bricolage-grotesque:600,700,800|figtree:400,500,600,700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="kenali min-h-screen antialiased lg:grid lg:grid-cols-2">
        <BackgroundBlobs />

        <!-- kolom form -->
        <div class="relative z-10 flex min-h-screen flex-col px-5 py-6 sm:px-10">
            <div>
                <Link href="/" class="inline-flex items-center gap-2.5" aria-label="Kenali, ke beranda">
                    <ApplicationLogo class="h-9 w-9" />
                    <span class="display text-2xl font-extrabold tracking-tight">Kenali</span>
                </Link>
            </div>

            <main class="flex flex-1 items-center justify-center py-10">
                <div class="card w-full max-w-md !p-8 sm:!p-10">
                    <slot />
                </div>
            </main>

            <p class="muted text-center text-sm">&copy; {{ year }} Kenali</p>
        </div>

        <!-- panel gelap (hanya layar besar) -->
        <aside class="panel relative z-10 m-4 hidden overflow-hidden lg:flex lg:flex-col lg:justify-end">
            <svg
                class="pointer-events-none absolute left-1/2 top-[38%] -translate-x-1/2 -translate-y-1/2 overflow-visible"
                width="420"
                height="400"
                viewBox="0 0 400 380"
                aria-hidden="true"
            >
                <g style="isolation: isolate">
                    <circle class="orb orb-1" cx="155" cy="165" r="100" fill="#9277FF" />
                    <circle class="orb orb-2" cx="245" cy="165" r="100" fill="#FF5C93" />
                    <circle class="orb orb-3" cx="200" cy="240" r="100" fill="#4DB0FF" />
                </g>
                <circle cx="200" cy="190" r="5" fill="#ffffff" stroke="#1e1b4b" stroke-width="2" />
            </svg>

            <div class="relative p-12">
                <p class="display text-4xl font-extrabold leading-tight tracking-tight">
                    <span class="grad-text">Kenali dirimu,</span><br />temukan arahmu.
                </p>
                <p class="mt-3 max-w-sm text-white/80">
                    Minat, bakat, dan karaktermu bertemu di satu hasil yang bisa kamu telusuri.
                </p>
            </div>
        </aside>
    </div>
</template>

<style scoped>
.panel {
    color: #ffffff;
    background: linear-gradient(135deg, #1e1b4b 0%, #3730a3 50%, #7c3aed 80%, #be185d 100%);
    border-radius: 32px;
    box-shadow: 0 30px 60px -30px rgb(55 48 163 / 0.7);
}
.panel .grad-text {
    background-image: linear-gradient(90deg, #fde68a, #fda4af 55%, #93c5fd);
}
.orb {
    opacity: 0.9;
    mix-blend-mode: screen;
    transform-box: fill-box;
    transform-origin: center;
    animation: float 7s ease-in-out infinite;
}
.orb-2 { animation-delay: -2.3s; }
.orb-3 { animation-delay: -4.6s; }
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@media (prefers-reduced-motion: reduce) {
    .orb { animation: none; }
}
</style>