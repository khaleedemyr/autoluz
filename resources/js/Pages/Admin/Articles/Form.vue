<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import { swalError, swalToast, swalWarning } from '@/utils/swal';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    article: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.article?.id);
const generatingSeo = ref(false);
const seoScore = ref(null);
const seoTips = ref([]);
const brandQuery = ref('');
// On create, keep slug synced to title. On edit, lock until user unlocks.
const slugLocked = ref(isEdit.value);

const filteredBrands = computed(() => {
    const q = brandQuery.value.trim().toLowerCase();
    if (!q) return props.brands;
    return props.brands.filter((brand) => String(brand.name || '').toLowerCase().includes(q));
});

const form = useForm({
    title: props.article?.title || '',
    slug: props.article?.slug || '',
    excerpt: props.article?.excerpt || '',
    content_html: props.article?.content_html || '',
    category_id: props.article?.category_id || '',
    brand_ids: props.article?.brand_ids || [],
    status: props.article?.status || 'draft',
    is_featured: props.article?.is_featured || false,
    published_at: props.article?.published_at || '',
    featured_image: null,
    remove_featured_image: false,
    meta_title: props.article?.meta_title || '',
    meta_description: props.article?.meta_description || '',
    focus_keyword: props.article?.focus_keyword || '',
    canonical_url: props.article?.canonical_url || '',
    og_title: props.article?.og_title || '',
    og_description: props.article?.og_description || '',
});

function isEmptyHtml(html) {
    const text = String(html || '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .trim();
    return text === '';
}

watch(
    () => form.title,
    (title) => {
        if (!slugLocked.value) {
            form.slug = slugify(title);
        }
    },
);

function unlockSlugFromTitle() {
    slugLocked.value = false;
    form.slug = slugify(form.title);
}

async function generateSeo() {
    if (!form.title.trim()) {
        await swalWarning('Isi judul dulu sebelum generate SEO.');
        return;
    }

    generatingSeo.value = true;
    seoTips.value = [];

    try {
        const { data: seo } = await window.axios.post(route('admin.seo.generate'), {
            title: form.title,
            excerpt: form.excerpt,
            content_html: form.content_html,
            slug: form.slug,
            category_id: form.category_id || null,
        });

        form.meta_title = seo.meta_title || '';
        form.meta_description = seo.meta_description || '';
        form.focus_keyword = seo.focus_keyword || '';
        form.canonical_url = seo.canonical_url || '';
        form.og_title = seo.og_title || '';
        form.og_description = seo.og_description || '';
        seoScore.value = seo.score ?? null;
        seoTips.value = seo.tips || [];
        swalToast('SEO berhasil digenerate.');
    } catch (error) {
        await swalError(error?.response?.data?.message || error?.message || 'Gagal generate SEO');
    } finally {
        generatingSeo.value = false;
    }
}

async function submit() {
    if (isEmptyHtml(form.content_html)) {
        form.setError('content_html', 'Isi artikel wajib diisi.');
        await swalWarning('Isi artikel wajib diisi.');
        return;
    }

    if (isEdit.value) {
        form
            .transform((data) => ({
                ...data,
                _method: 'put',
                category_id: data.category_id || null,
            }))
            .post(route('admin.articles.update', props.article.id), {
                forceFormData: true,
            });
        return;
    }

    form
        .transform((data) => ({
            ...data,
            category_id: data.category_id || null,
        }))
        .post(route('admin.articles.store'), {
            forceFormData: true,
        });
}
</script>

<template>
    <AdminLayout :title="isEdit ? 'Edit Artikel' : 'Artikel Baru'">
        <Head :title="isEdit ? 'Edit Artikel' : 'Artikel Baru'" />

        <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]" @submit.prevent="submit">
            <div class="space-y-4">
                <div class="space-y-4 rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Judul</label>
                        <input v-model="form.title" type="text" class="w-full rounded-xl border-black/10" required />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Slug</label>
                            <button
                                v-if="isEdit && slugLocked"
                                type="button"
                                class="text-[11px] font-semibold uppercase tracking-[0.12em] text-brand hover:underline"
                                @click="unlockSlugFromTitle"
                            >
                                Sesuaikan dari judul
                            </button>
                        </div>
                        <input
                            v-model="form.slug"
                            type="text"
                            class="w-full rounded-xl border-black/10 bg-neutral-50"
                            readonly
                            :placeholder="slugify(form.title) || 'otomatis dari judul'"
                        />
                        <p class="mt-1 text-xs text-neutral-400">
                            Otomatis dari judul. {{ isEdit ? 'Klik “Sesuaikan dari judul” jika ingin diubah.' : '' }}
                        </p>
                        <p v-if="form.errors.slug" class="mt-1 text-xs text-red-600">{{ form.errors.slug }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Excerpt</label>
                        <textarea v-model="form.excerpt" rows="3" class="w-full rounded-xl border-black/10" placeholder="Ringkasan singkat artikel" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Isi Artikel</label>
                        <RichTextEditor
                            v-model="form.content_html"
                            :upload-url="route('admin.articles.upload-image')"
                            placeholder="Tulis isi berita di sini. Bisa bold, list, link, dan sisipkan gambar."
                        />
                        <p class="mt-1 text-xs text-neutral-400">
                            Editor visual — user tidak perlu menulis HTML.
                        </p>
                        <p v-if="form.errors.content_html" class="mt-1 text-xs text-red-600">{{ form.errors.content_html }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-display text-xl tracking-[-0.03em]">SEO</h2>
                            <p class="mt-1 text-xs text-neutral-500">Meta title, description, OG, dan keyword.</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-full border border-brand/30 bg-brand/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-brand disabled:opacity-60"
                            :disabled="generatingSeo"
                            @click="generateSeo"
                        >
                            {{ generatingSeo ? 'Generating...' : 'Generate SEO' }}
                        </button>
                    </div>

                    <div
                        v-if="seoScore !== null"
                        class="mb-4 rounded-xl border border-black/5 bg-neutral-50 px-4 py-3"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Skor SEO</span>
                            <span class="font-display text-2xl tracking-[-0.04em]" :class="seoScore >= 70 ? 'text-emerald-600' : seoScore >= 45 ? 'text-amber-600' : 'text-red-600'">
                                {{ seoScore }}
                            </span>
                        </div>
                        <ul v-if="seoTips.length" class="mt-2 space-y-1 text-xs text-neutral-600">
                            <li v-for="(tip, idx) in seoTips" :key="idx">• {{ tip }}</li>
                        </ul>
                    </div>

                    <div class="grid gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">
                                Meta Title
                                <span class="ml-1 font-normal normal-case tracking-normal text-neutral-400">({{ form.meta_title.length }}/70)</span>
                            </label>
                            <input v-model="form.meta_title" type="text" maxlength="70" class="w-full rounded-xl border-black/10" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">
                                Meta Description
                                <span class="ml-1 font-normal normal-case tracking-normal text-neutral-400">({{ form.meta_description.length }}/180)</span>
                            </label>
                            <textarea v-model="form.meta_description" rows="3" maxlength="180" class="w-full rounded-xl border-black/10" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Focus Keyword</label>
                                <input v-model="form.focus_keyword" type="text" class="w-full rounded-xl border-black/10" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Canonical URL</label>
                                <input v-model="form.canonical_url" type="url" class="w-full rounded-xl border-black/10" placeholder="https://..." />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">OG Title</label>
                            <input v-model="form.og_title" type="text" class="w-full rounded-xl border-black/10" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">OG Description</label>
                            <textarea v-model="form.og_description" rows="2" class="w-full rounded-xl border-black/10" />
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
                        <option value="archived">Archived</option>
                    </select>

                    <label class="mb-1 mt-4 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Kategori</label>
                    <select v-model="form.category_id" class="w-full rounded-xl border-black/10">
                        <option value="">— Tanpa kategori —</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>

                    <label class="mb-1 mt-4 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Merek</label>
                    <div class="overflow-hidden rounded-xl border border-black/10">
                        <div class="border-b border-black/5 p-2">
                            <input
                                v-model="brandQuery"
                                type="search"
                                placeholder="Cari merek…"
                                class="w-full rounded-lg border-black/10 text-sm"
                            />
                        </div>
                        <div class="max-h-40 space-y-1 overflow-y-auto p-2">
                            <label
                                v-for="brand in filteredBrands"
                                :key="brand.id"
                                class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-mist"
                            >
                                <input
                                    v-model="form.brand_ids"
                                    type="checkbox"
                                    :value="brand.id"
                                    class="rounded border-black/20 text-brand"
                                />
                                {{ brand.name }}
                            </label>
                            <p v-if="!filteredBrands.length" class="px-2 py-1 text-xs text-neutral-400">
                                {{ brands.length ? 'Tidak ditemukan.' : 'Belum ada merek.' }}
                            </p>
                        </div>
                        <p v-if="form.brand_ids.length" class="border-t border-black/5 px-3 py-1.5 text-[11px] text-neutral-500">
                            {{ form.brand_ids.length }} merek dipilih
                        </p>
                    </div>

                    <label class="mb-1 mt-4 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Published at</label>
                    <input v-model="form.published_at" type="datetime-local" class="w-full rounded-xl border-black/10" />

                    <label class="mt-4 flex items-center gap-2 text-sm">
                        <input v-model="form.is_featured" type="checkbox" class="rounded border-black/20 text-brand" />
                        Featured di homepage
                    </label>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Featured Image</label>
                    <img
                        v-if="article?.featured_image_url && !form.remove_featured_image && !form.featured_image"
                        :src="article.featured_image_url"
                        class="mb-3 max-h-40 w-full rounded-xl object-cover"
                        alt=""
                    />
                    <input
                        type="file"
                        accept="image/*"
                        class="block w-full text-sm"
                        @change="form.featured_image = $event.target.files[0]"
                    />
                    <label v-if="article?.featured_image_url" class="mt-3 flex items-center gap-2 text-sm">
                        <input v-model="form.remove_featured_image" type="checkbox" class="rounded border-black/20 text-brand" />
                        Hapus gambar saat ini
                    </label>
                    <p v-if="form.errors.featured_image" class="mt-1 text-xs text-red-600">{{ form.errors.featured_image }}</p>
                </div>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="flex-1 rounded-full bg-brand px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                    <Link
                        :href="route('admin.articles.index')"
                        class="rounded-full border border-black/10 bg-white px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.14em]"
                    >
                        Batal
                    </Link>
                </div>
            </aside>
        </form>
    </AdminLayout>
</template>
