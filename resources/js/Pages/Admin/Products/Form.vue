<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    product: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.product?.id);
const slugManual = ref(!!props.product?.slug);
const coverPreview = ref(props.product?.cover_image_url || null);
const existingImages = ref([...(props.product?.images || [])]);
const removeImageIds = ref([]);

const emptyVariant = () => ({ id: null, sku: '', size: '', color: '', price: '', stock: 0, is_active: true });

const form = useForm({
    shop_category_id: props.product?.shop_category_id || '',
    name: props.product?.name || '',
    slug: props.product?.slug || '',
    excerpt: props.product?.excerpt || '',
    description_html: props.product?.description_html || '',
    weight_grams: props.product?.weight_grams || 250,
    featured: !!props.product?.featured,
    status: props.product?.status || 'draft',
    published_at: props.product?.published_at || '',
    sort_order: props.product?.sort_order || 0,
    cover_image: null,
    remove_cover_image: false,
    images: [],
    remove_image_ids: [],
    captions: {},
    variants: props.product?.variants?.length
        ? props.product.variants.map((row) => ({ ...row, is_active: row.is_active !== false }))
        : [emptyVariant()],
});

watch(() => form.name, (value) => {
    if (!slugManual.value) form.slug = slugify(value);
});

function onCoverChange(event) {
    const file = event.target.files?.[0];
    form.cover_image = file || null;
    form.remove_cover_image = false;
    coverPreview.value = file ? URL.createObjectURL(file) : props.product?.cover_image_url || null;
}

function onImagesChange(event) {
    form.images = Array.from(event.target.files || []);
}

function markRemove(id) {
    removeImageIds.value = [...new Set([...removeImageIds.value, id])];
    existingImages.value = existingImages.value.filter((img) => img.id !== id);
}

function addVariant() {
    form.variants.push(emptyVariant());
}

function removeVariant(index) {
    form.variants.splice(index, 1);
    if (!form.variants.length) form.variants.push(emptyVariant());
}

function submit() {
    form.remove_image_ids = removeImageIds.value;
    form.captions = Object.fromEntries(existingImages.value.map((img) => [img.id, img.caption || '']));

    const payload = (data) => ({
        ...data,
        shop_category_id: data.shop_category_id || null,
        featured: data.featured ? 1 : 0,
        variants: data.variants.map((row) => ({
            ...row,
            id: row.id || null,
            is_active: row.is_active ? 1 : 0,
        })),
    });

    if (isEdit.value) {
        form.transform((data) => ({ ...payload(data), _method: 'put' })).post(route('admin.products.update', props.product.id), { forceFormData: true });
        return;
    }

    form.transform(payload).post(route('admin.products.store'), { forceFormData: true });
}
</script>

<template>
    <AdminLayout :title="isEdit ? 'Edit Produk' : 'Produk Baru'">
        <Head :title="isEdit ? 'Edit Produk' : 'Produk Baru'" />

        <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]" @submit.prevent="submit">
            <div class="space-y-4">
                <div class="space-y-3 rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <input v-model="form.name" type="text" required placeholder="Nama produk (contoh: Kaos Autoluz)" class="w-full rounded-xl border-black/10" />
                    <input v-model="form.slug" type="text" class="w-full rounded-xl border-black/10" @input="slugManual = true" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select v-model="form.shop_category_id" class="w-full rounded-xl border-black/10">
                            <option value="">— Kategori —</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <input v-model="form.weight_grams" type="number" min="1" required placeholder="Berat (gram)" class="w-full rounded-xl border-black/10" />
                    </div>
                    <textarea v-model="form.excerpt" rows="3" placeholder="Ringkasan singkat" class="w-full rounded-xl border-black/10" />
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Deskripsi</label>
                        <RichTextEditor
                            v-model="form.description_html"
                            :upload-url="route('admin.products.upload-image')"
                            placeholder="Tulis deskripsi produk."
                        />
                    </div>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold">Varian (ukuran / warna / stok)</h3>
                        <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-brand" @click="addVariant">+ Varian</button>
                    </div>
                    <div class="space-y-3">
                        <div v-for="(row, index) in form.variants" :key="index" class="grid gap-2 rounded-xl border border-black/5 p-3 sm:grid-cols-6">
                            <input v-model="row.size" type="text" placeholder="Ukuran (M)" class="rounded-lg border-black/10 text-sm" />
                            <input v-model="row.color" type="text" placeholder="Warna" class="rounded-lg border-black/10 text-sm" />
                            <input v-model="row.sku" type="text" placeholder="SKU" class="rounded-lg border-black/10 text-sm" />
                            <input v-model="row.price" type="number" min="0" required placeholder="Harga" class="rounded-lg border-black/10 text-sm" />
                            <input v-model="row.stock" type="number" min="0" required placeholder="Stok" class="rounded-lg border-black/10 text-sm" />
                            <div class="flex items-center justify-between gap-2">
                                <label class="flex items-center gap-1 text-xs">
                                    <input v-model="row.is_active" type="checkbox" class="rounded text-brand" />
                                    Aktif
                                </label>
                                <button type="button" class="text-red-600" @click="removeVariant(index)">×</button>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-[11px] text-neutral-500">Produk tanpa ukuran/warna: isi 1 baris varian default (harga + stok).</p>
                    <p v-if="form.errors.variants" class="mt-1 text-xs text-red-600">{{ form.errors.variants }}</p>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Galeri</label>
                    <input type="file" accept="image/*" multiple class="w-full text-sm" @change="onImagesChange" />
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
                    <label class="mb-1 mt-4 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Sort</label>
                    <input v-model="form.sort_order" type="number" min="0" class="w-full rounded-xl border-black/10" />
                    <label class="mt-4 flex items-center gap-2 text-sm">
                        <input v-model="form.featured" type="checkbox" class="rounded text-brand" />
                        Tampil di homepage
                    </label>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Cover</label>
                    <div v-if="coverPreview" class="mb-3 overflow-hidden rounded-xl border border-black/10">
                        <img :src="coverPreview" alt="" class="aspect-video w-full object-cover" />
                    </div>
                    <input type="file" accept="image/*" class="w-full text-sm" @change="onCoverChange" />
                    <label v-if="coverPreview" class="mt-3 flex items-center gap-2 text-sm">
                        <input v-model="form.remove_cover_image" type="checkbox" class="rounded border-black/20 text-brand" />
                        Hapus cover
                    </label>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white" :disabled="form.processing">
                        Simpan
                    </button>
                    <Link :href="route('admin.products.index')" class="rounded-full border border-black/10 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em]">
                        Kembali
                    </Link>
                </div>
            </aside>
        </form>
    </AdminLayout>
</template>
