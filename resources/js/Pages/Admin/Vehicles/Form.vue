<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    vehicle: { type: Object, default: null },
    brands: { type: Array, default: () => [] },
    bodyTypes: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.vehicle?.id);
const slugManual = ref(!!props.vehicle?.slug);
const coverPreview = ref(props.vehicle?.cover_image_url || null);
const existingImages = ref([...(props.vehicle?.images || [])]);
const removeImageIds = ref([]);

const form = useForm({
    brand_id: props.vehicle?.brand_id || '',
    name: props.vehicle?.name || '',
    slug: props.vehicle?.slug || '',
    body_type: props.vehicle?.body_type || '',
    model_year: props.vehicle?.model_year || '',
    excerpt: props.vehicle?.excerpt || '',
    description_html: props.vehicle?.description_html || '',
    specs: props.vehicle?.specs?.length ? [...props.vehicle.specs] : [{ label: '', value: '' }],
    price_from: props.vehicle?.price_from || '',
    status: props.vehicle?.status || 'draft',
    published_at: props.vehicle?.published_at || '',
    sort_order: props.vehicle?.sort_order || 0,
    cover_image: null,
    remove_cover_image: false,
    images: [],
    remove_image_ids: [],
    captions: {},
});

watch(() => form.name, (value) => {
    if (!slugManual.value) form.slug = slugify(value);
});

function onCoverChange(event) {
    const file = event.target.files?.[0];
    form.cover_image = file || null;
    form.remove_cover_image = false;
    coverPreview.value = file ? URL.createObjectURL(file) : props.vehicle?.cover_image_url || null;
}

function onImagesChange(event) {
    form.images = Array.from(event.target.files || []);
}

function markRemove(id) {
    removeImageIds.value = [...new Set([...removeImageIds.value, id])];
    existingImages.value = existingImages.value.filter((img) => img.id !== id);
}

function addSpec() {
    form.specs.push({ label: '', value: '' });
}

function removeSpec(index) {
    form.specs.splice(index, 1);
    if (!form.specs.length) form.specs.push({ label: '', value: '' });
}

function submit() {
    form.remove_image_ids = removeImageIds.value;
    form.captions = Object.fromEntries(existingImages.value.map((img) => [img.id, img.caption || '']));

    if (isEdit.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
            brand_id: data.brand_id || null,
            price_from: data.price_from === '' ? null : data.price_from,
        })).post(route('admin.vehicles.update', props.vehicle.id), { forceFormData: true });
        return;
    }

    form.transform((data) => ({
        ...data,
        brand_id: data.brand_id || null,
        price_from: data.price_from === '' ? null : data.price_from,
    })).post(route('admin.vehicles.store'), { forceFormData: true });
}
</script>

<template>
    <AdminLayout :title="isEdit ? 'Edit Kendaraan' : 'Kendaraan Baru'">
        <Head :title="isEdit ? 'Edit Kendaraan' : 'Kendaraan Baru'" />

        <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]" @submit.prevent="submit">
            <div class="space-y-4">
                <div class="space-y-3 rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <input v-model="form.name" type="text" required placeholder="Nama kendaraan (contoh: Avanza)" class="w-full rounded-xl border-black/10" />
                    <input v-model="form.slug" type="text" class="w-full rounded-xl border-black/10" @input="slugManual = true" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <SearchableSelect
                            v-model="form.brand_id"
                            :options="brands"
                            :required="true"
                            empty-label="— Pilih merek —"
                            placeholder="Cari merek…"
                        />
                        <select v-model="form.body_type" class="w-full rounded-xl border-black/10">
                            <option value="">— Tipe bodi —</option>
                            <option v-for="type in bodyTypes" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input v-model="form.model_year" type="text" placeholder="Tahun model (2024/2025)" class="w-full rounded-xl border-black/10" />
                        <input v-model="form.price_from" type="number" min="0" placeholder="Harga mulai (Rp)" class="w-full rounded-xl border-black/10" />
                    </div>
                    <textarea v-model="form.excerpt" rows="3" placeholder="Ringkasan singkat" class="w-full rounded-xl border-black/10" />
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Deskripsi</label>
                        <RichTextEditor
                            v-model="form.description_html"
                            :upload-url="route('admin.vehicles.upload-image')"
                            placeholder="Tulis deskripsi kendaraan. Bisa bold, list, link, dan sisipkan gambar."
                        />
                        <p class="mt-1 text-xs text-neutral-400">Editor visual — tidak perlu menulis HTML.</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold">Spesifikasi</h3>
                        <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-brand" @click="addSpec">+ Baris</button>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(row, index) in form.specs" :key="index" class="grid grid-cols-[1fr_1fr_auto] gap-2">
                            <input v-model="row.label" type="text" placeholder="Label (Mesin)" class="rounded-xl border-black/10 text-sm" />
                            <input v-model="row.value" type="text" placeholder="Nilai (1.5L)" class="rounded-xl border-black/10 text-sm" />
                            <button type="button" class="rounded-xl border border-black/10 px-3 text-sm text-red-600" @click="removeSpec(index)">×</button>
                        </div>
                    </div>
                    <p class="mt-2 text-[11px] text-neutral-500">Contoh: Mesin, Tenaga, Transmisi, BBM, Kapasitas, Dimensi.</p>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Galeri foto kendaraan</label>
                    <input type="file" accept="image/*" multiple class="w-full text-sm" @change="onImagesChange" />
                    <p class="mt-1 text-[11px] text-neutral-500">Upload beberapa foto (eksterior, interior, detail). Tampil di halaman detail sebagai gallery. Disarankan 1600×900 px.</p>

                    <div v-if="existingImages.length" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="image in existingImages" :key="image.id" class="overflow-hidden rounded-xl border border-black/10">
                            <img :src="image.image_url" :alt="image.caption || ''" class="aspect-video w-full object-cover" />
                            <div class="space-y-2 p-2">
                                <input v-model="image.caption" type="text" placeholder="Caption" class="w-full rounded-lg border-black/10 text-xs" />
                                <button type="button" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-red-600" @click="markRemove(image.id)">Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Status</label>
                    <select v-model="form.status" class="w-full rounded-xl border-black/10">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                    <label class="mb-1 mt-4 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Published at</label>
                    <input v-model="form.published_at" type="datetime-local" class="w-full rounded-xl border-black/10" />
                    <label class="mb-1 mt-4 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Sort order</label>
                    <input v-model="form.sort_order" type="number" min="0" class="w-full rounded-xl border-black/10" />
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Cover</label>
                    <div v-if="coverPreview" class="mb-3 overflow-hidden rounded-xl border border-black/10">
                        <img :src="coverPreview" alt="" class="aspect-video w-full object-cover" />
                    </div>
                    <input type="file" accept="image/*" class="w-full text-sm" @change="onCoverChange" />
                    <p class="mt-1 text-[11px] text-neutral-500">Disarankan 1600×900 px.</p>
                    <label v-if="coverPreview" class="mt-3 flex items-center gap-2 text-sm">
                        <input v-model="form.remove_cover_image" type="checkbox" class="rounded border-black/20 text-brand" />
                        Hapus cover
                    </label>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white" :disabled="form.processing">
                        Simpan
                    </button>
                    <Link :href="route('admin.vehicles.index')" class="rounded-full border border-black/10 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em]">
                        Kembali
                    </Link>
                </div>
            </aside>
        </form>
    </AdminLayout>
</template>
