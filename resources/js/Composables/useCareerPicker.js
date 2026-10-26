import { computed, ref } from 'vue';
import { careers, groups } from '@/Data/landing';

export function useCareerPicker(initial = ['gambar', 'analitis', 'empatik']) {
    const picked = ref(initial);

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

    const counts = computed(() =>
        Object.fromEntries(
            groups.map((g) => [g.key, g.tags.filter((t) => picked.value.includes(t.id)).length]),
        ),
    );

    return { picked, toggle, ranked, top, counts };
}
