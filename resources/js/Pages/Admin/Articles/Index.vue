<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';

const props = defineProps({
    articles: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: {
        type: Object,
        default: () => ({ q: '', status: '', category_id: '', per_page: 15 }),
    },
    selectedCategory: { type: Object, default: null },
});

const q = ref(props.filters.q || '');
const status = ref(props.filters.status || '');
const categoryId = ref(props.filters.category_id || '');
const perPage = ref(Number(props.filters.per_page || 15));

watch([q, status, categoryId, perPage], () => {
    router.get(
        route('admin.articles.index'),
        {
            q: q.value || undefined,
            status: status.value || undefined,
            category_id: categoryId.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, replace: true },
    );
});

function clearCategoryFilter() {
    categoryId.value = '';
}

async function destroy(id) {
    const ok = await swalConfirm('Hapus artikel ini?', {
        title: 'Hapus Artikel',
        confirmButtonText: 'Hapus',
        icon: 'warning',
    });
    if (!ok) return;
    router.delete(route('admin.articles.destroy', id));
}
</script>

<template>
    <AdminLayout title="Artikel">
        <Head title="Admin Artikel" />

        <div
            v-if="selectedCategory"
            class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand/20 bg-brand/5 px-4 py-3"
        >
            <p class="text-sm text-neutral-700">
                Menampilkan artikel kategori
                <span class="font-semibold text-brand">{{ selectedCategory.name }}</span>
            </p>
            <button
                type="button"
                class="text-xs font-semibold uppercase tracking-[0.12em] text-brand hover:underline"
                @click="clearCategoryFilter"
            >
                Hapus filter
            </button>
        </div>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <input
                    v-model="q"
                    type="search"
                    placeholder="Cari judul/konten..."
                    class="rounded-xl border-black/10 text-sm"
                />
                <select v-model="categoryId" class="rounded-xl border-black/10 text-sm">
                    <option value="">Semua kategori</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>
                <select v-model="status" class="rounded-xl border-black/10 text-sm">
                    <option value="">Semua status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
                <select v-model.number="perPage" class="rounded-xl border-black/10 text-sm">
                    <option :value="10">10 / page</option>
                    <option :value="15">15 / page</option>
                    <option :value="25">25 / page</option>
                    <option :value="50">50 / page</option>
                    <option :value="100">100 / page</option>
                </select>
            </div>
            <Link
                :href="route('admin.articles.create')"
                class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white"
            >
                + Artikel Baru
            </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist/60 text-xs uppercase tracking-[0.12em] text-neutral-500">
                        <tr>
                            <th class="px-4 py-3">Artikel</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Published</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="article in articles.data" :key="article.id" class="border-t border-black/5 align-top">
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <img
                                        v-if="article.featured_image_url"
                                        :src="article.featured_image_url"
                                        class="h-14 w-20 rounded-lg object-cover"
                                        alt=""
                                    />
                                    <div>
                                        <div class="font-semibold">{{ article.title }}</div>
                                        <div class="text-xs text-neutral-400">/{{ article.slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Link
                                    v-if="article.category"
                                    :href="route('admin.articles.index', { category_id: article.category.id })"
                                    class="text-neutral-600 hover:text-brand hover:underline"
                                >
                                    {{ article.category.name }}
                                </Link>
                                <span v-else class="text-neutral-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-mist px-2 py-1 text-[11px] font-semibold uppercase">
                                    {{ article.status }}
                                </span>
                                <span v-if="article.is_featured" class="ml-1 text-[11px] font-semibold text-brand">★</span>
                            </td>
                            <td class="px-4 py-3 text-neutral-500">{{ article.published_at || '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <Link :href="route('articles.show', article.slug)" class="mr-3 text-neutral-500 hover:text-brand" target="_blank">
                                    Lihat
                                </Link>
                                <Link :href="route('admin.articles.edit', article.id)" class="mr-3 text-brand hover:underline">
                                    Edit
                                </Link>
                                <button type="button" class="text-red-600 hover:underline" @click="destroy(article.id)">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!articles.data?.length">
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-neutral-500">
                                Tidak ada artikel untuk filter ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar
                :links="articles.links"
                :from="articles.from"
                :to="articles.to"
                :total="articles.total"
            />
        </div>
    </AdminLayout>
</template>
