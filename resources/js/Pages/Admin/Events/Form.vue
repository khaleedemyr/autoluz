<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    event: { type: Object, default: null },
});

const isEdit = computed(() => !!props.event?.id);

const form = useForm({
    title: props.event?.title || '',
    slug: props.event?.slug || '',
    excerpt: props.event?.excerpt || '',
    body_html: props.event?.body_html || '',
    location: props.event?.location || '',
    venue: props.event?.venue || '',
    city: props.event?.city || '',
    starts_at: props.event?.starts_at || '',
    ends_at: props.event?.ends_at || '',
    registration_url: props.event?.registration_url || '',
    is_featured: props.event?.is_featured ?? false,
    status: props.event?.status || 'draft',
    sort_order: props.event?.sort_order ?? 0,
    cover_image: null,
    remove_cover_image: false,
});

const coverPreview = ref(props.event?.cover_image_url || null);
const slugManual = ref(!!props.event?.slug);

watch(
    () => form.title,
    (value) => {
        if (!slugManual.value) {
            form.slug = slugify(value);
        }
    },
);

function onCoverChange(event) {
    const file = event.target.files?.[0];
    form.cover_image = file || null;
    form.remove_cover_image = false;
    coverPreview.value = file ? URL.createObjectURL(file) : props.event?.cover_image_url || null;
}

function removeCover() {
    form.cover_image = null;
    form.remove_cover_image = true;
    coverPreview.value = null;
}

function submit() {
    if (isEdit.value) {
        form
            .transform((data) => ({ ...data, _method: 'put' }))
            .post(route('admin.events.update', props.event.id), {
                forceFormData: true,
            });
        return;
    }

    form.post(route('admin.events.store'), { forceFormData: true });
}
</script>

<template>
    <AdminLayout :title="isEdit ? 'Edit Event' : 'Event Baru'">
        <Head :title="isEdit ? 'Edit Event' : 'Event Baru'" />

        <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]" @submit.prevent="submit">
            <div class="space-y-5 rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Judul</label>
                    <input v-model="form.title" type="text" class="w-full rounded-xl border-black/10" required />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Slug</label>
                    <input
                        v-model="form.slug"
                        type="text"
                        class="w-full rounded-xl border-black/10"
                        @input="slugManual = true"
                    />
                    <p v-if="form.errors.slug" class="mt-1 text-xs text-red-600">{{ form.errors.slug }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Ringkasan</label>
                    <textarea v-model="form.excerpt" rows="3" class="w-full rounded-xl border-black/10" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Detail Event</label>
                    <RichTextEditor
                        v-model="form.body_html"
                        :upload-url="route('admin.events.upload-image')"
                        placeholder="Tulis detail event di sini. Bisa bold, list, link, dan sisipkan gambar."
                    />
                    <p class="mt-1 text-xs text-neutral-400">
                        Editor visual — tidak perlu menulis HTML.
                    </p>
                    <p v-if="form.errors.body_html" class="mt-1 text-xs text-red-600">{{ form.errors.body_html }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Mulai</label>
                        <input v-model="form.starts_at" type="datetime-local" class="w-full rounded-xl border-black/10" required />
                        <p v-if="form.errors.starts_at" class="mt-1 text-xs text-red-600">{{ form.errors.starts_at }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Selesai</label>
                        <input v-model="form.ends_at" type="datetime-local" class="w-full rounded-xl border-black/10" />
                        <p v-if="form.errors.ends_at" class="mt-1 text-xs text-red-600">{{ form.errors.ends_at }}</p>
                    </div>
                    <p class="sm:col-span-2 text-xs text-neutral-400">Waktu ditampilkan di frontend sebagai WIB (GMT+7), sesuai yang diisi di sini.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Venue</label>
                        <input v-model="form.venue" type="text" class="w-full rounded-xl border-black/10" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Lokasi</label>
                        <input v-model="form.location" type="text" class="w-full rounded-xl border-black/10" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Kota</label>
                        <input v-model="form.city" type="text" class="w-full rounded-xl border-black/10" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Link Registrasi</label>
                    <input v-model="form.registration_url" type="url" class="w-full rounded-xl border-black/10" placeholder="https://" />
                    <p v-if="form.errors.registration_url" class="mt-1 text-xs text-red-600">{{ form.errors.registration_url }}</p>
                </div>
            </div>

            <aside class="space-y-5">
                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Status</label>
                    <select v-model="form.status" class="w-full rounded-xl border-black/10">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>

                    <label class="mt-4 flex items-center gap-2 text-sm">
                        <input v-model="form.is_featured" type="checkbox" class="rounded border-black/20 text-brand" />
                        Featured di homepage
                    </label>

                    <label class="mb-1 mt-4 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Urutan</label>
                    <input v-model="form.sort_order" type="number" min="0" class="w-full rounded-xl border-black/10" />
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Cover Image</label>
                    <div v-if="coverPreview" class="mb-3 overflow-hidden rounded-xl bg-mist">
                        <img :src="coverPreview" alt="" class="aspect-[16/10] w-full object-cover" />
                    </div>
                    <input type="file" accept="image/*" class="w-full text-sm" @change="onCoverChange" />
                    <button
                        v-if="coverPreview"
                        type="button"
                        class="mt-2 text-xs font-semibold uppercase tracking-[0.12em] text-red-600"
                        @click="removeCover"
                    >
                        Hapus gambar
                    </button>
                    <p v-if="form.errors.cover_image" class="mt-1 text-xs text-red-600">{{ form.errors.cover_image }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="submit"
                        class="rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                    <Link
                        :href="route('admin.events.index')"
                        class="rounded-full border border-black/10 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em]"
                    >
                        Batal
                    </Link>
                </div>
            </aside>
        </form>
    </AdminLayout>
</template>
