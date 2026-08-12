<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    gallery: { type: Object, default: null },
});

const isEdit = computed(() => !!props.gallery?.id);
const slugManual = ref(!!props.gallery?.slug);
const coverPreview = ref(props.gallery?.cover_image_url || null);
const existingImages = ref([...(props.gallery?.images || [])]);
const removeImageIds = ref([]);

const form = useForm({
    title: props.gallery?.title || '',
    slug: props.gallery?.slug || '',
    excerpt: props.gallery?.excerpt || '',
    status: props.gallery?.status || 'draft',
    published_at: props.gallery?.published_at || '',
    sort_order: props.gallery?.sort_order || 0,
    cover_image: null,
    remove_cover_image: false,
    images: [],
    remove_image_ids: [],
    captions: {},
});

watch(() => form.title, (value) => {
    if (!slugManual.value) form.slug = slugify(value);
});

function onCoverChange(event) {
    const file = event.target.files?.[0];
    form.cover_image = file || null;
    form.remove_cover_image = false;
    coverPreview.value = file ? URL.createObjectURL(file) : props.gallery?.cover_image_url || null;
}

function onImagesChange(event) {
    form.images = Array.from(event.target.files || []);
}

function markRemove(id) {
    removeImageIds.value = [...new Set([...removeImageIds.value, id])];
    existingImages.value = existingImages.value.filter((img) => img.id !== id);
}

function submit() {
    form.remove_image_ids = removeImageIds.value;
    form.captions = Object.fromEntries(existingImages.value.map((img) => [img.id, img.caption || '']));

    if (isEdit.value) {
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(route('admin.galleries.update', props.gallery.id), { forceFormData: true });
        return;
    }

    form.post(route('admin.galleries.store'), { forceFormData: true });
}
</script>

<template>
    <AdminLayout :title="isEdit ? 'Edit Galeri' : 'Galeri Baru'">
        <Head :title="isEdit ? 'Edit Galeri' : 'Galeri Baru'" />

        <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]" @submit.prevent="submit">
            <div class="space-y-4 rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                <input v-model="form.title" type="text" required placeholder="Judul galeri" class="w-full rounded-xl border-black/10" />
                <input v-model="form.slug" type="text" class="w-full rounded-xl border-black/10" @input="slugManual = true" />
                <textarea v-model="form.excerpt" rows="3" placeholder="Ringkasan" class="w-full rounded-xl border-black/10" />

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Tambah Foto</label>
                    <input type="file" accept="image/*" multiple class="w-full text-sm" @change="onImagesChange" />
                </div>

                <div v-if="existingImages.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="image in existingImages" :key="image.id" class="overflow-hidden rounded-xl border border-black/5">
                        <img :src="image.image_url" alt="" class="aspect-[4/3] w-full object-cover" />
                        <div class="space-y-2 p-2">
                            <input v-model="image.caption" type="text" placeholder="Caption" class="w-full rounded-lg border-black/10 text-xs" />
                            <button type="button" class="text-[10px] font-semibold uppercase tracking-[0.12em] text-red-600" @click="markRemove(image.id)">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <select v-model="form.status" class="w-full rounded-xl border-black/10 text-sm">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                    <input v-model="form.published_at" type="datetime-local" class="mt-3 w-full rounded-xl border-black/10 text-sm" />
                </div>
                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <div v-if="coverPreview" class="mb-3 overflow-hidden rounded-xl">
                        <img :src="coverPreview" alt="" class="aspect-[16/10] w-full object-cover" />
                    </div>
                    <input type="file" accept="image/*" class="w-full text-sm" @change="onCoverChange" />
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white" :disabled="form.processing">
                        Simpan
                    </button>
                    <Link :href="route('admin.galleries.index')" class="rounded-full border border-black/10 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em]">
                        Batal
                    </Link>
                </div>
            </aside>
        </form>
    </AdminLayout>
</template>
