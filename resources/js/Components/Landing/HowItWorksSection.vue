<script setup>
import { steps } from '@/Data/landing';
import { useScrollOrbs } from '@/Composables/useScrollOrbs';

const { fly, vennSlot } = useScrollOrbs();
</script>

<template>
    <section id="cara-kerja" class="wrap scroll-mt-16 pb-24">
        <div class="grid gap-14 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <h2 class="display text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                    <span class="grad-text">Dari jawabanmu</span> jadi urutan rekomendasi
                </h2>
                <p class="muted mt-4 max-w-md text-lg leading-relaxed">
                    Kenali memakai sistem pendukung keputusan (SPK). Hasilnya bukan tebakan, tapi perhitungan yang bisa
                    ditelusuri.
                </p>

                <p class="display mt-8 text-2xl font-bold">
                    <span class="formula">Skor pilihan = &Sigma; (nilai &times; bobot)</span>
                </p>
                <p class="muted mt-3 max-w-md leading-relaxed">
                    Kriteria yang lebih penting punya bobot lebih besar, jadi pengaruhnya ke hasil juga lebih besar.
                </p>

                <div class="mt-10 max-w-[320px]">
                    <div ref="vennSlot" class="aspect-[5/4] w-full">
                        <svg v-if="!fly" viewBox="0 40 400 320" class="block h-full w-full" aria-hidden="true">
                            <g style="isolation: isolate">
                                <circle class="static-orb" cx="155" cy="165" r="72" fill="#9277FF" />
                                <circle class="static-orb" cx="245" cy="165" r="72" fill="#FF5C93" />
                                <circle class="static-orb" cx="200" cy="240" r="72" fill="#4DB0FF" />
                            </g>
                            <circle cx="200" cy="190" r="4" fill="#ffffff" stroke="#1e1b4b" stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="muted mt-3 text-sm">Tiga hal tadi bertemu di hasilmu.</p>
                </div>
            </div>

            <ol class="grid content-start gap-5 sm:grid-cols-2 lg:col-span-7">
                <li v-for="(s, i) in steps" :key="s.title" class="step-card" :style="{ '--c': s.color }">
                    <span
                        class="num"
                        :style="{ background: s.color, color: s.color === 'var(--fg)' ? '#ffffff' : 'var(--fg)' }"
                        >{{ i + 1 }}</span
                    >
                    <h3 class="display mt-5 text-2xl font-bold">{{ s.title }}</h3>
                    <p class="muted mt-2 leading-relaxed">{{ s.text }}</p>
                </li>
            </ol>
        </div>
    </section>
</template>

<style scoped>
.static-orb { opacity: 0.9; mix-blend-mode: multiply; }

.formula {
    display: inline-block;
    padding: 0.5rem 1rem;
    color: #ffffff;
    background: linear-gradient(135deg, #4f46e5, #7c3aed 55%, #db2777);
    border-radius: 16px;
    box-shadow: 0 12px 24px -12px rgb(124 58 237 / 0.7);
}

.step-card {
    position: relative;
    overflow: hidden;
    padding: 1.75rem;
    background: rgb(255 255 255 / 0.85);
    border-radius: 24px;
    box-shadow: 0 20px 40px -28px rgb(79 70 229 / 0.4), inset 0 0 0 1px rgb(255 255 255 / 0.9);
    transition: transform 0.25s ease;
}
.step-card::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 5px; background: var(--c); }
.step-card:hover { transform: translateY(-3px); }
.num { display: grid; flex: none; place-items: center; width: 2.75rem; height: 2.75rem; font-weight: 800; border-radius: 50%; }
@media (prefers-reduced-motion: reduce) { .step-card { transition: none; } }
</style>
