<script setup>
import { ref, onBeforeUnmount } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

defineProps({
    avatarUrl: { type: String, default: null },
});

const MAX_BYTES = 2 * 1024 * 1024;
const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

const form = useForm({ avatar: null });
const preview = ref(null);
const clientError = ref('');
const removing = ref(false);
const fileInput = ref(null);

function revokePreview() {
    if (preview.value) URL.revokeObjectURL(preview.value);
    preview.value = null;
}

function onFileChange(event) {
    clientError.value = '';
    const file = event.target.files?.[0];
    revokePreview();
    form.avatar = null;
    if (!file) return;

    if (!ALLOWED.includes(file.type)) {
        clientError.value = 'Format harus JPG, PNG, atau WebP.';
        event.target.value = '';
        return;
    }
    if (file.size > MAX_BYTES) {
        clientError.value = 'Ukuran file maksimal 2 MB.';
        event.target.value = '';
        return;
    }

    form.avatar = file;
    preview.value = URL.createObjectURL(file);
}

function submit() {
    if (!form.avatar || form.processing) return;

    form.post(route('profile.avatar.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            revokePreview();
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function removeAvatar() {
    if (removing.value || !confirm('Hapus foto profil?')) return;

    removing.value = true;
    router.delete(route('profile.avatar.destroy'), {
        preserveScroll: true,
        onFinish: () => (removing.value = false),
    });
}

onBeforeUnmount(revokePreview);
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Foto Profil</h2>
            <p class="mt-1 text-sm text-gray-600">
                Format JPG, PNG, atau WebP. Maksimal 2 MB, minimal 100×100 piksel.
            </p>
        </header>

        <div class="mt-6 flex items-center gap-6">
            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full bg-gray-100">
                <img
                    v-if="preview || avatarUrl"
                    :src="preview || avatarUrl"
                    alt="Foto profil"
                    class="h-full w-full object-cover"
                />
                <div v-else class="flex h-full w-full items-center justify-center text-xs text-gray-400">
                    Belum ada
                </div>
            </div>

            <div class="flex-1">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @change="onFileChange"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200"
                />

                <p v-if="clientError" class="mt-2 text-xs text-red-600" role="alert">{{ clientError }}</p>
                <p v-if="form.errors.avatar" class="mt-2 text-xs text-red-600" role="alert">{{ form.errors.avatar }}</p>

                <div class="mt-3 flex gap-3">
                    <button
                        type="button"
                        @click="submit"
                        :disabled="!form.avatar || form.processing"
                        class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-40"
                    >
                        {{ form.processing ? 'Mengunggah…' : 'Simpan Foto' }}
                    </button>
                    <button
                        v-if="avatarUrl && !preview"
                        type="button"
                        @click="removeAvatar"
                        :disabled="removing"
                        class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200 disabled:opacity-40"
                    >
                        {{ removing ? 'Menghapus…' : 'Hapus Foto' }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>