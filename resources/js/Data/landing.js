export const groups = [
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

export const careers = [
    { name: 'UI/UX Designer', color: 'var(--violet)', w: { gambar: 3, teknologi: 2, analitis: 1, merakit: 2, bicara: 1, teliti: 2, empatik: 3, berani: 1, tenang: 1 } },
    { name: 'Software Engineer', color: 'var(--sky)', w: { teknologi: 3, analitis: 3, merakit: 3, menulis: 1, teliti: 3, empatik: 1, berani: 1, tenang: 2 } },
    { name: 'Guru & Pendidik', color: 'var(--rose)', w: { mengajar: 3, gambar: 1, teknologi: 1, bicara: 3, menulis: 1, teliti: 1, empatik: 3, tenang: 2 } },
    { name: 'Psikolog', color: 'var(--violet)', w: { mengajar: 1, analitis: 2, bicara: 2, menulis: 2, teliti: 2, empatik: 3, tenang: 3 } },
    { name: 'Jurnalis', color: 'var(--rose)', w: { alam: 1, analitis: 2, bicara: 2, menulis: 3, teliti: 2, berani: 3, tenang: 1 } },
    { name: 'Peneliti Lingkungan', color: 'var(--sky)', w: { alam: 3, teknologi: 1, analitis: 3, merakit: 1, menulis: 2, teliti: 3, berani: 1, tenang: 2 } },
];

export const orbs = [
    { key: 'minat', label: 'Minat', color: '#9277FF', dot: 'var(--violet)', x: 155, y: 165 },
    { key: 'bakat', label: 'Bakat', color: '#FF5C93', dot: 'var(--rose)', x: 245, y: 165 },
    { key: 'karakter', label: 'Karakter', color: '#4DB0FF', dot: 'var(--sky)', x: 200, y: 240 },
];

export const pillars = [
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

export const steps = [
    { color: 'var(--violet)', title: 'Isi profil singkat', text: 'Daftar dan ceritakan sedikit tentang dirimu.' },
    { color: 'var(--rose)', title: 'Jawab pertanyaan', text: 'Ada tiga bagian: minat, bakat, dan karakter. Tidak ada jawaban benar atau salah.' },
    { color: 'var(--sky)', title: 'Sistem menghitung', text: 'Tiap jawaban diberi nilai, dikalikan bobot kriteria, lalu dijumlahkan untuk setiap pilihan.' },
    { color: 'var(--fg)', title: 'Baca hasilmu', text: 'Kamu mendapat urutan rekomendasi lengkap dengan alasannya.' },
];
