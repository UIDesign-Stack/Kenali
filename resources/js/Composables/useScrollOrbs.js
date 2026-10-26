import { inject, nextTick, onBeforeUnmount, onMounted, provide, ref } from 'vue';
import { orbs } from '@/Data/landing';

const KEY = Symbol('scrollOrbs');

const heroDepth = [0.1, 0.28, 0.5];
const ctaDepth = [0.2, 0.35, 0.5];
const clamp01 = (v) => Math.min(1, Math.max(0, v));
const smooth = (v) => v * v * (3 - 2 * v);

export function provideScrollOrbs() {
    const fly = ref(false);

    const vennSlot = ref(null);
    const coreDot = ref(null);
    const heroCore = ref(null);
    const ctaPanel = ref(null);
    const dots = [];
    const flyOrbs = [];
    const heroLayers = [];
    const ctaOrbs = [];

    let raf = 0;
    let mq = null;

    function resetParallax() {
        heroLayers.forEach((el) => el && (el.style.transform = ''));
        if (heroCore.value) {
            heroCore.value.style.transform = '';
            heroCore.value.style.opacity = '';
        }
        ctaOrbs.forEach((el) => el && (el.style.transform = ''));
    }

    function render() {
        raf = 0;
        if (!fly.value) return resetParallax();

        const vh = window.innerHeight;
        const y = Math.min(window.scrollY, 700);

        heroLayers.forEach((el, i) => el && (el.style.transform = `translateY(${-y * heroDepth[i]}px)`));
        if (heroCore.value) {
            heroCore.value.style.transform = `translateY(${-y * 0.29}px)`;
            heroCore.value.style.opacity = 1 - clamp01(y / 180);
        }

        if (ctaPanel.value) {
            const c = ctaPanel.value.getBoundingClientRect();
            const d = Math.max(-600, Math.min(600, c.top + c.height / 2 - vh / 2));
            ctaOrbs.forEach((el, i) => el && (el.style.transform = `translateY(${-d * ctaDepth[i]}px)`));
        }

        if (!vennSlot.value) return;
        const s = vennSlot.value.getBoundingClientRect();
        if (!s.width) return;

        const progress = clamp01((vh * 0.92 - s.top) / (vh * 0.5));
        const k = s.width / 400;

        orbs.forEach((o, i) => {
            const dot = dots[i];
            const el = flyOrbs[i];
            if (!dot || !el) return;

            const d = dot.getBoundingClientRect();
            const fromX = d.left + d.width / 2;
            const fromY = d.top + d.height / 2;
            const toX = s.left + o.x * k;
            const toY = s.top + (o.y - 40) * k;

            const e = smooth(clamp01(progress * 1.4 - i * 0.2));
            const x = fromX + (toX - fromX) * e;
            const yy = fromY + (toY - fromY) * e;
            const r = d.width / 2 + (85 * k * 0.85 - d.width / 2) * e;

            el.style.transform = `translate(${x}px, ${yy}px) scale(${r})`;
        });

        if (coreDot.value) {
            coreDot.value.style.transform = `translate(${s.left + 200 * k}px, ${s.top + 150 * k}px)`;
            coreDot.value.style.opacity = smooth(clamp01((progress - 0.85) / 0.15));
        }
    }

    const schedule = () => {
        if (!raf) raf = requestAnimationFrame(render);
    };

    function syncMotion() {
        fly.value = mq.matches;
        nextTick(schedule);
    }

    onMounted(() => {
        mq = window.matchMedia('(min-width: 768px) and (prefers-reduced-motion: no-preference)');
        syncMotion();
        mq.addEventListener('change', syncMotion);
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
        window.addEventListener('load', schedule);
    });

    onBeforeUnmount(() => {
        mq?.removeEventListener('change', syncMotion);
        window.removeEventListener('scroll', schedule);
        window.removeEventListener('resize', schedule);
        window.removeEventListener('load', schedule);
        if (raf) cancelAnimationFrame(raf);
    });

    const ctx = { fly, vennSlot, coreDot, heroCore, ctaPanel, dots, flyOrbs, heroLayers, ctaOrbs };
    provide(KEY, ctx);
    return ctx;
}


export const useScrollOrbs = () => inject(KEY);
