<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

/* ---------------------------------------------------------------
 | Data simulasi untuk "Coba dulu" di hero.
 | Contoh SAW sederhana (nilai x bobot, lalu dijumlahkan).
 | Ganti kriteria & bobotnya dengan data SPK aslimu.
 --------------------------------------------------------------- */
const groups = [
    {
        key: 'minat',
        label: 'Minat',
        color: 'var(--violet)',
        tags: [
            { id: 'gambar', label: 'Menggambar & desain' },
            { id: 'teknologi', label: 'Teknologi' },
            { id: 'mengajar', label: 'Berbagi ilmu' },
            { id: 'alam', label: 'Alam & lingkungan' },
        ],
    },
    {
        key: 'bakat',
        label: 'Bakat',
        color: 'var(--rose)',
        tags: [
            { id: 'analitis', label: 'Menganalisis' },
            { id: 'bicara', label: 'Berbicara di depan umum' },
            { id: 'merakit', label: 'Membuat & merakit' },
            { id: 'menulis', label: 'Menulis' },
        ],
    },
    {
        key: 'karakter',
        label: 'Karakter',
        color: 'var(--sky)',
        tags: [
            { id: 'teliti', label: 'Teliti' },
            { id: 'empatik', label: 'Empatik' },
            { id: 'berani', label: 'Berani ambil risiko' },
            { id: 'tenang', label: 'Tenang saat tertekan' },
        ],
    },
];

// bobot 0-3: seberapa kuat sebuah ciri mendukung pilihan tersebut
const careers = [
    { name: 'UI/UX Designer', color: 'var(--violet)', w: { gambar: 3, teknologi: 2, analitis: 1, merakit: 2, bicara: 1, teliti: 2, empatik: 3, berani: 1, tenang: 1 } },
    { name: 'Software Engineer', color: 'var(--sky)', w: { teknologi: 3, analitis: 3, merakit: 3, menulis: 1, teliti: 3, empatik: 1, berani: 1, tenang: 2 } },
    { name: 'Guru & Pendidik', color: 'var(--rose)', w: { mengajar: 3, gambar: 1, teknologi: 1, bicara: 3, menulis: 1, teliti: 1, empatik: 3, tenang: 2 } },
    { name: 'Psikolog', color: 'var(--violet)', w: { mengajar: 1, analitis: 2, bicara: 2, menulis: 2, teliti: 2, empatik: 3, tenang: 3 } },
    { name: 'Jurnalis', color: 'var(--rose)', w: { alam: 1, analitis: 2, bicara: 2, menulis: 3, teliti: 2, berani: 3, tenang: 1 } },
    { name: 'Peneliti Lingkungan', color: 'var(--sky)', w: { alam: 3, teknologi: 1, analitis: 3, merakit: 1, menulis: 2, teliti: 3, berani: 1, tenang: 2 } },
];

const picked = ref(['gambar', 'analitis', 'empatik']);

function toggle(id) {
    picked.value = picked.value.includes(id)
        ? picked.value.filter((x) => x !== id)
        : [...picked.value, id];
}

const ranked = computed(() => {
    const max = picked.value.length * 3;
    return careers
        .map((c) => ({
            ...c,
            pct: max
                ? Math.round((picked.value.reduce((sum, id) => sum + (c.w[id] || 0), 0) / max) * 100)
                : 0,
        }))
        .sort((a, b) => b.pct - a.pct || a.name.localeCompare(b.name));
});

const top = computed(() => (picked.value.length ? ranked.value[0] : null));

/* ---------- diagram Venn: ukuran lingkaran mengikuti jumlah pilihan ---------- */
const orbs = [
    { key: 'minat', label: 'Minat', color: '#9277FF', dot: 'var(--violet)', x: 155, y: 165 },
    { key: 'bakat', label: 'Bakat', color: '#FF5C93', dot: 'var(--rose)', x: 245, y: 165 },
    { key: 'karakter', label: 'Karakter', color: '#4DB0FF', dot: 'var(--sky)', x: 200, y: 240 },
];

function countOf(key) {
    const g = groups.find((x) => x.key === key);
    return g.tags.filter((t) => picked.value.includes(t.id)).length;
}

function scaleOf(key) {
    return 0.6 + countOf(key) * 0.16;
}

/* ---------------------------------------------------------------
 | Efek scroll (semuanya hanya memakai `transform`):
 |   1. Hero: tiga lingkaran Venn naik dengan kecepatan yang sangat berbeda,
 |      jadi formasinya terurai saat kamu scroll turun.
 |   2. Tiga hal -> Cara kerja: tiga lingkaran "terbang" dari baris
 |      Minat/Bakat/Karakter lalu bertemu membentuk Venn.
 |   3. Ajakan mendaftar: lingkaran di panel berkumpul membentuk Venn
 |      tepat saat panel tiba di tengah layar.
 |
 | - Hanya aktif di layar >= 768px dan jika pengguna TIDAK memilih
 |   "reduce motion". Selain itu halaman tampil statis (tanpa gerak).
 | - Dihitung sekali per frame (requestAnimationFrame), jadi ringan.
 --------------------------------------------------------------- */
const fly = ref(false);
const vennSlot = ref(null); // tempat Venn akhir di bagian Cara kerja
const coreDot = ref(null); // titik kecil di pusat irisan
const dots = []; // penanda posisi awal di tiap baris pilar
const flyOrbs = []; // lingkaran yang bergerak (lapisan fixed)

const heroLayers = []; // tiga lapisan lingkaran di hero
const heroCore = ref(null); // titik pusat di hero
const ctaPanel = ref(null); // panel ajakan mendaftar
const ctaOrbs = []; // tiga lingkaran di dalam panel

// Seberapa jauh tiap lingkaran bergeser per 1px scroll. Makin besar & makin
// berbeda antar-lingkaran = efek parallax makin terasa. Ubah di sini untuk menyetel.
const heroDepth = [0.1, 0.28, 0.5]; // hero: naik lebih cepat dari scroll biasa
const ctaDepth = [0.2, 0.35, 0.5]; // panel ajakan mendaftar

let raf = 0;
let mq = null;

const clamp01 = (v) => Math.min(1, Math.max(0, v));
const smooth = (v) => v * v * (3 - 2 * v);

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
    if (!fly.value) {
        resetParallax();
        return;
    }

    const vh = window.innerHeight;

    // 1. hero: tiap lapisan naik dengan kecepatan berbeda, formasi Venn terurai
    const y = Math.min(window.scrollY, 700);
    heroLayers.forEach((el, i) => el && (el.style.transform = `translateY(${-y * heroDepth[i]}px)`));
    if (heroCore.value) {
        // titik pusat menghilang seiring lingkaran menjauh dari titik temunya
        heroCore.value.style.transform = `translateY(${-y * 0.29}px)`;
        heroCore.value.style.opacity = 1 - clamp01(y / 180);
    }

    // 3. ajakan mendaftar: lingkaran berkumpul membentuk Venn saat panel di tengah layar
    if (ctaPanel.value) {
        const c = ctaPanel.value.getBoundingClientRect();
        const d = Math.max(-600, Math.min(600, c.top + c.height / 2 - vh / 2));
        ctaOrbs.forEach((el, i) => el && (el.style.transform = `translateY(${-d * ctaDepth[i]}px)`));
    }

    // 2. Tiga hal -> Cara kerja: lingkaran terbang lalu bertemu
    if (!vennSlot.value) return;

    const s = vennSlot.value.getBoundingClientRect();
    if (!s.width) return;

    // 0 saat slot baru masuk dari bawah layar, 1 saat sudah kira-kira di tengah layar
    const progress = clamp01((vh * 0.92 - s.top) / (vh * 0.5));
    const k = s.width / 400; // skala dari koordinat Venn (400 lebar) ke piksel

    orbs.forEach((o, i) => {
        const dot = dots[i];
        const el = flyOrbs[i];
        if (!dot || !el) return;

        const d = dot.getBoundingClientRect();
        const fromX = d.left + d.width / 2;
        const fromY = d.top + d.height / 2;
        const toX = s.left + o.x * k;
        const toY = s.top + (o.y - 40) * k;

        // tiap lingkaran berangkat sedikit berbeda waktu, jadi geraknya tidak serempak
        const e = smooth(clamp01(progress * 1.4 - i * 0.2));
        const x = fromX + (toX - fromX) * e;
        const y = fromY + (toY - fromY) * e;
        const r = d.width / 2 + (85 * k * 0.85 - d.width / 2) * e;

        el.style.transform = `translate(${x}px, ${y}px) scale(${r})`;
    });

    if (coreDot.value) {
        coreDot.value.style.transform = `translate(${s.left + 200 * k}px, ${s.top + 150 * k}px)`;
        coreDot.value.style.opacity = smooth(clamp01((progress - 0.85) / 0.15));
    }
}

function schedule() {
    if (!raf) raf = requestAnimationFrame(render);
}

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
    if (mq) mq.removeEventListener('change', syncMotion);
    window.removeEventListener('scroll', schedule);
    window.removeEventListener('resize', schedule);
    window.removeEventListener('load', schedule);
    if (raf) cancelAnimationFrame(raf);
});

/* ---------------- konten statis ---------------- */
const pillars = [
    {
        title: 'Minat',
        color: 'var(--violet)',
        text: 'Apa yang kamu suka, bahkan saat tidak ada yang menyuruh.',
        q: 'Kegiatan apa yang bikin kamu lupa waktu?',
    },
    {
        title: 'Bakat',
        color: 'var(--rose)',
        text: 'Hal yang kamu cepat kuasai dibanding kebanyakan orang.',
        q: 'Tugas apa yang terasa ringan buatmu, tapi berat buat teman-temanmu?',
    },
    {
        title: 'Karakter',
        color: 'var(--sky)',
        text: 'Cara kamu bersikap, mengambil keputusan, dan bekerja dengan orang lain.',
        q: 'Saat rencana berantakan, kamu biasanya ngapain dulu?',
    },
];

const steps = [
    { color: 'var(--violet)', title: 'Isi profil singkat', text: 'Daftar dan ceritakan sedikit tentang dirimu.' },
    { color: 'var(--rose)', title: 'Jawab pertanyaan', text: 'Ada tiga bagian: minat, bakat, dan karakter. Tidak ada jawaban benar atau salah.' },
    { color: 'var(--sky)', title: 'Sistem menghitung', text: 'Tiap jawaban diberi nilai, dikalikan bobot kriteria, lalu dijumlahkan untuk setiap pilihan.' },
    { color: 'var(--fg)', title: 'Baca hasilmu', text: 'Kamu mendapat urutan rekomendasi lengkap dengan alasannya.' },
];

const year = new Date().getFullYear();
</script>

<template>
    <Head title="Kenali - temukan minat, bakat, dan karaktermu">
        <meta
            name="description"
            content="Kenali membantumu memahami minat, bakat, dan karakter lewat sistem pendukung keputusan, lalu memberi rekomendasi yang cocok untukmu."
        />
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=bricolage-grotesque:600,700,800|figtree:400,500,600,700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="kenali min-h-screen antialiased">
        <!-- ================= NAVBAR ================= -->
        <header class="site-header sticky top-0 z-30 backdrop-blur">
            <div class="wrap flex items-center justify-between py-3.5">
                <a href="/" class="flex items-center gap-2.5" aria-label="Kenali, ke beranda">
                    <svg width="34" height="34" viewBox="0 0 34 34" aria-hidden="true">
                        <g style="isolation: isolate">
                            <circle class="screen" cx="12.5" cy="13" r="9" fill="#9277FF" />
                            <circle class="screen" cx="21.5" cy="13" r="9" fill="#FF5C93" />
                            <circle class="screen" cx="17" cy="21" r="9" fill="#4DB0FF" />
                        </g>
                    </svg>
                    <span class="display text-2xl font-extrabold tracking-tight">Kenali</span>
                </a>

                <nav class="hidden items-center gap-8 text-[15px] font-semibold md:flex" aria-label="Bagian halaman">
                    <a href="#tiga-hal" class="nav-link">Tiga hal yang dibaca</a>
                    <a href="#cara-kerja" class="nav-link">Cara kerja</a>
                </nav>

                <div class="flex items-center gap-4">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-sm">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link v-if="canLogin" :href="route('login')" class="nav-link text-[15px] font-semibold">
                            Masuk
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="btn btn-sm">Daftar</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- lapisan lingkaran yang bergerak saat scroll (di belakang konten) -->
        <svg v-if="fly" class="fly-layer" aria-hidden="true">
            <g style="isolation: isolate">
                <circle
                    v-for="(o, i) in orbs"
                    :key="o.key"
                    :ref="(el) => (flyOrbs[i] = el)"
                    class="fly-orb"
                    r="1"
                    :fill="o.color"
                    style="transform: translate(-200px, -200px)"
                />
            </g>
            <circle ref="coreDot" r="4" fill="#0D1130" style="opacity: 0; transform: translate(-200px, -200px)" />
        </svg>

        <main>
            <!-- ================= HERO ================= -->
            <section class="wrap grid gap-x-10 gap-y-12 pb-24 pt-10 md:pt-16 lg:grid-cols-12 lg:gap-y-14">
                <!-- teks utama -->
                <div class="lg:col-span-5 lg:col-start-1 lg:row-start-1 lg:self-center">
                    <h1 class="display text-[2.9rem] font-extrabold leading-[1.03] tracking-tight sm:text-6xl">
                        Kenali dirimu sebelum memilih jalan.
                    </h1>
                    <p class="muted mt-6 max-w-md text-lg leading-relaxed">
                        Jawab pertanyaan tentang minat, bakat, dan karaktermu. Sistem kami menghitungnya dan
                        mengurutkan pilihan yang paling cocok untukmu.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-x-7 gap-y-4">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn">
                            Lanjut ke dashboard
                        </Link>
                        <Link v-else-if="canRegister" :href="route('register')" class="btn">Mulai tes</Link>
                        <Link v-else-if="canLogin" :href="route('login')" class="btn">Masuk untuk mulai</Link>

                        <a href="#cara-kerja" class="nav-link font-semibold">Lihat cara kerja</a>
                    </div>
                </div>

                <!-- Venn: lingkaran membesar sesuai pilihanmu -->
                <div class="lg:col-span-7 lg:col-start-6 lg:row-start-1">
                    <svg
                        viewBox="0 40 400 320"
                        class="mx-auto block w-full max-w-[460px] overflow-visible lg:mx-0"
                        role="img"
                        aria-label="Tiga lingkaran cahaya beririsan yang mewakili minat, bakat, dan karakter. Ukurannya bertambah sesuai jumlah ciri yang kamu pilih."
                    >
                        <!-- pembungkus .layer = digeser saat scroll; .enter = animasi masuk; .orb = ukuran sesuai pilihan -->
                        <g v-for="(o, i) in orbs" :key="o.key" :ref="(el) => (heroLayers[i] = el)" class="layer">
                            <g class="enter" :style="{ '--d': i * 160 + 'ms' }">
                                <circle
                                    class="orb"
                                    :cx="o.x"
                                    :cy="o.y"
                                    r="85"
                                    :fill="o.color"
                                    :style="{ transform: 'scale(' + scaleOf(o.key) + ')' }"
                                />
                            </g>
                        </g>
                        <circle ref="heroCore" cx="200" cy="190" r="5" fill="#0D1130" aria-hidden="true" />
                    </svg>

                    <ul
                        class="mx-auto mt-3 flex max-w-[460px] flex-wrap justify-center gap-x-6 gap-y-1 text-sm font-semibold lg:mx-0 lg:justify-start"
                    >
                        <li v-for="o in orbs" :key="o.key" class="flex items-center gap-2">
                            <span class="dot" :style="{ background: o.dot }"></span>
                            {{ o.label }}
                            <span class="muted tabular-nums">{{ countOf(o.key) }} dipilih</span>
                        </li>
                    </ul>

                    <p class="mt-4 max-w-sm text-center text-[15px] leading-snug lg:text-left" aria-live="polite">
                        <template v-if="top">
                            Titik temu ketiganya: <strong>{{ top.name }}</strong
                            >, kecocokan {{ top.pct }}%.
                        </template>
                        <template v-else>Pilih beberapa ciri di bawah untuk melihat titik temunya.</template>
                    </p>
                </div>

                <!-- pilihan ciri -->
                <div class="lg:col-span-7 lg:col-start-6 lg:row-start-2">
                    <h2 class="display text-2xl font-bold">Coba dulu. Pilih yang paling kamu banget.</h2>

                    <div class="mt-5 space-y-5">
                        <div v-for="g in groups" :key="g.key">
                            <p class="mb-2 flex items-center gap-2 text-sm font-bold">
                                <span class="dot" :style="{ background: g.color }"></span>
                                {{ g.label }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="t in g.tags"
                                    :key="t.id"
                                    type="button"
                                    class="chip"
                                    :class="{ 'is-on': picked.includes(t.id) }"
                                    :style="{ '--c': g.color }"
                                    :aria-pressed="picked.includes(t.id)"
                                    @click="toggle(t.id)"
                                >
                                    {{ t.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- hasil sementara -->
                <div class="lg:col-span-5 lg:col-start-1 lg:row-start-2">
                    <h2 class="display text-2xl font-bold">Pilihan yang paling cocok sejauh ini</h2>

                    <TransitionGroup tag="ul" name="rank" class="mt-5 space-y-3.5">
                        <li v-for="c in ranked" :key="c.name">
                            <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm font-semibold">
                                <span>{{ c.name }}</span>
                                <span class="tabular-nums">{{ c.pct }}%</span>
                            </div>
                            <div class="track">
                                <div class="fill" :style="{ width: c.pct + '%', background: c.color }"></div>
                            </div>
                        </li>
                    </TransitionGroup>

                    <p class="muted mt-5 max-w-sm text-xs leading-relaxed">
                        Ini simulasi singkat. Tes yang sebenarnya memakai lebih banyak pertanyaan dan pembobotan yang
                        lebih rinci.
                    </p>
                </div>
            </section>

            <!-- ================= TIGA HAL ================= -->
            <section id="tiga-hal" class="wrap scroll-mt-16 pb-24 pt-4">
                <h2 class="display max-w-xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                    Tiga hal yang kami baca dari dirimu
                </h2>
                <p class="muted mt-4 max-w-lg text-lg leading-relaxed">
                    Satu saja tidak cukup. Minat tanpa bakat cepat bosan, bakat tanpa minat cepat lelah. Karena itu
                    ketiganya dilihat bersama.
                </p>

                <div class="mt-12">
                    <article
                        v-for="(p, i) in pillars"
                        :key="p.title"
                        class="pillar grid items-center gap-x-8 gap-y-4 py-10 md:grid-cols-12"
                    >
                        <div class="flex items-center gap-5 md:col-span-4">
                            <!-- saat efek scroll aktif, lingkaran ini "berangkat" dan menyisakan cincin -->
                            <span
                                :ref="(el) => (dots[i] = el)"
                                class="big-dot"
                                :class="{ 'is-ring': fly }"
                                :style="{ '--c': p.color, background: fly ? 'transparent' : p.color }"
                                aria-hidden="true"
                            ></span>
                            <h3 class="display text-4xl font-extrabold sm:text-5xl">{{ p.title }}</h3>
                        </div>
                        <p class="muted text-[17px] leading-relaxed md:col-span-3">{{ p.text }}</p>
                        <p class="display text-2xl font-bold leading-snug md:col-span-5">&ldquo;{{ p.q }}&rdquo;</p>
                    </article>
                </div>
            </section>

            <!-- ================= CARA KERJA ================= -->
            <section id="cara-kerja" class="wrap scroll-mt-16 pb-24">
                <div class="grid gap-14 lg:grid-cols-12">
                    <div class="lg:col-span-5">
                        <h2 class="display text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                            Dari jawabanmu jadi urutan rekomendasi
                        </h2>
                        <p class="muted mt-4 max-w-md text-lg leading-relaxed">
                            Kenali memakai sistem pendukung keputusan (SPK). Hasilnya bukan tebakan, tapi perhitungan
                            yang bisa ditelusuri.
                        </p>

                        <p class="display mt-8 text-2xl font-bold">
                            <span class="mark">Skor pilihan = &Sigma; (nilai &times; bobot)</span>
                        </p>
                        <p class="muted mt-3 max-w-md leading-relaxed">
                            Kriteria yang lebih penting punya bobot lebih besar, jadi pengaruhnya ke hasil juga lebih
                            besar.
                        </p>

                        <!-- tempat tiga lingkaran bertemu -->
                        <div class="mt-10 max-w-[320px]">
                            <div ref="vennSlot" class="aspect-[5/4] w-full">
                                <svg v-if="!fly" viewBox="0 40 400 320" class="block h-full w-full" aria-hidden="true">
                                    <g style="isolation: isolate">
                                        <circle class="static-orb" cx="155" cy="165" r="72" fill="#9277FF" />
                                        <circle class="static-orb" cx="245" cy="165" r="72" fill="#FF5C93" />
                                        <circle class="static-orb" cx="200" cy="240" r="72" fill="#4DB0FF" />
                                    </g>
                                    <circle cx="200" cy="190" r="4" fill="#0D1130" />
                                </svg>
                            </div>
                            <p class="muted mt-3 text-sm">Tiga hal tadi bertemu di hasilmu.</p>
                        </div>
                    </div>

                    <ol class="lg:col-span-7">
                        <li v-for="(s, i) in steps" :key="s.title" class="step flex gap-6 py-8">
                            <span class="num" :style="{ background: s.color }">{{ i + 1 }}</span>
                            <div class="pt-1">
                                <h3 class="display text-2xl font-bold">{{ s.title }}</h3>
                                <p class="muted mt-1 max-w-md leading-relaxed">{{ s.text }}</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- ================= CTA ================= -->
            <section class="wrap pb-20">
                <div ref="ctaPanel" class="cta relative overflow-hidden px-8 py-16 sm:px-14 sm:py-20">
                    <svg
                        class="pointer-events-none absolute -right-16 top-1/2 hidden -translate-y-1/2 overflow-visible md:block"
                        width="420"
                        height="400"
                        viewBox="0 0 400 380"
                        aria-hidden="true"
                    >
                        <g style="isolation: isolate">
                            <circle
                                v-for="(o, i) in orbs"
                                :key="o.key"
                                :ref="(el) => (ctaOrbs[i] = el)"
                                class="screen"
                                :cx="o.x"
                                :cy="o.y"
                                r="100"
                                :fill="o.color"
                            />
                        </g>
                    </svg>

                    <div class="relative max-w-lg">
                        <h2 class="display text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                            Mulai dari satu pertanyaan.
                        </h2>
                        <p class="muted mt-3 text-lg">Daftar, jawab pertanyaannya, lalu baca hasilnya.</p>

                        <div class="mt-8">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-violet">
                                Lanjut ke dashboard
                            </Link>
                            <Link v-else-if="canRegister" :href="route('register')" class="btn btn-violet">
                                Mulai tes
                            </Link>
                            <Link v-else-if="canLogin" :href="route('login')" class="btn btn-violet">
                                Masuk untuk mulai
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="wrap muted flex flex-col gap-2 py-6 text-sm sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ year }} Kenali. Kenali dirimu, temukan arahmu.</p>
                <p v-if="laravelVersion">Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})</p>
            </div>
        </footer>
    </div>
</template>

<style>
html {
    scroll-behavior: smooth;
}
@media (prefers-reduced-motion: reduce) {
    html {
        scroll-behavior: auto;
    }
}
</style>

<style scoped>
/* ------------------------------------------------------------
 | Palet: cukup ubah variabel di sini untuk mengganti seluruh warna.
 ------------------------------------------------------------ */
.kenali {
    --bg: #0d1130; /* malam indigo */
    --panel: #181d4d;
    --fg: #eef0ff;
    --on: #0d1130; /* teks di atas warna terang */
    --muted: rgb(238 240 255 / 0.72);
    --line: rgb(238 240 255 / 0.14);

    --violet: #9277ff;
    --rose: #ff5c93;
    --sky: #4db0ff;

    font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
    color: var(--fg);
    background: var(--bg);

    /* "clip" (bukan "hidden") supaya navbar sticky tetap berfungsi */
    overflow-x: clip;
}

.display {
    font-family: 'Bricolage Grotesque', 'Figtree', ui-sans-serif, system-ui, sans-serif;
}
.muted {
    color: var(--muted);
}

/* satu lebar container untuk semua bagian, di layar sebesar apa pun */
.wrap {
    width: 100%;
    max-width: 72rem;
    margin-inline: auto;
    padding-inline: 1.25rem;
}

/* konten berada di atas lapisan lingkaran yang bergerak */
main {
    position: relative;
    z-index: 1;
}

/* keyboard focus selalu terlihat */
.kenali :focus-visible {
    outline: 3px solid var(--fg);
    outline-offset: 3px;
    border-radius: 6px;
}

/* ---------- navbar & footer ---------- */
.site-header {
    background: rgb(13 17 48 / 0.8);
    border-bottom: 1px solid var(--line);
}
.site-footer {
    border-top: 1px solid var(--line);
}
.nav-link {
    background: linear-gradient(var(--violet), var(--violet)) no-repeat 0 100% / 0 0.3em;
    transition: background-size 0.2s ease;
}
.nav-link:hover {
    background-size: 100% 0.3em;
}

/* ---------- tombol ---------- */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.85rem 1.7rem;
    font-weight: 700;
    color: var(--on);
    background: var(--fg);
    border-radius: 999px;
    transition: transform 0.15s ease, background-color 0.15s ease;
}
.btn:hover {
    background: #fff;
    transform: translateY(-2px);
}
.btn:active {
    transform: translateY(0);
}
.btn-sm {
    padding: 0.5rem 1.15rem;
    font-size: 0.9rem;
}
.btn-violet {
    background: var(--violet);
}
.btn-violet:hover {
    background: #a891ff;
}

/* ---------- Venn: cahaya bercampur ---------- */
.layer {
    mix-blend-mode: screen; /* cahaya antar-lapisan bercampur */
}
.screen {
    mix-blend-mode: screen;
}
.enter {
    transform-box: fill-box;
    transform-origin: center;
    animation: enter 0.9s cubic-bezier(0.2, 0.8, 0.2, 1) both;
    animation-delay: var(--d, 0ms);
}
@keyframes enter {
    from {
        opacity: 0;
        transform: scale(0.4);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
.orb {
    transform-box: fill-box;
    transform-origin: center;
    transition: transform 0.6s cubic-bezier(0.34, 1.4, 0.64, 1);
}
.static-orb {
    mix-blend-mode: screen;
}

/* ---------- lapisan lingkaran yang bergerak saat scroll ---------- */
.fly-layer {
    position: fixed;
    inset: 0;
    z-index: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}
.fly-orb {
    mix-blend-mode: screen;
}

/* ---------- chip pilihan ---------- */
.dot {
    display: inline-block;
    width: 0.8rem;
    height: 0.8rem;
    border-radius: 50%;
}
.chip {
    padding: 0.45rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--fg);
    background: rgb(255 255 255 / 0.08);
    border-radius: 999px;
    transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
}
.chip:hover {
    background: rgb(255 255 255 / 0.15);
    transform: translateY(-1px);
}
.chip.is-on {
    color: var(--on);
    background: var(--c);
    font-weight: 700;
}

/* ---------- bar hasil ---------- */
.track {
    height: 8px;
    background: rgb(255 255 255 / 0.12);
    border-radius: 999px;
    overflow: hidden;
}
.fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.rank-move {
    transition: transform 0.55s cubic-bezier(0.2, 0.8, 0.2, 1);
}

/* ---------- tiga hal ---------- */
.pillar {
    border-top: 1px solid var(--line);
}
.pillar:last-child {
    border-bottom: 1px solid var(--line);
}
.big-dot {
    flex: none;
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 50%;
}
.big-dot.is-ring {
    box-shadow: inset 0 0 0 2px var(--c);
}

/* ---------- cara kerja ---------- */
.mark {
    padding: 0 0.15em;
    background: linear-gradient(transparent 68%, rgb(146 119 255 / 0.6) 68%);
}
.step {
    border-top: 1px solid var(--line);
}
.step:first-child {
    border-top: 0;
    padding-top: 0.25rem;
}
.num {
    display: grid;
    flex: none;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    font-weight: 800;
    color: var(--on);
    border-radius: 50%;
}

/* ---------- CTA ---------- */
.cta {
    background: var(--panel);
    border-radius: 32px;
}

@media (prefers-reduced-motion: reduce) {
    .enter {
        animation: none;
    }
    .btn,
    .chip,
    .orb,
    .fill,
    .rank-move,
    .nav-link {
        transition: none;
    }
}
</style>