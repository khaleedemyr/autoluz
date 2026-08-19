<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ q: '', shop_category_id: '' }) },
});

const q = ref(props.filters.q || '');
const categoryId = ref(props.filters.shop_category_id || '');

watch([q, categoryId], () => {
    router.get(route('admin.products.index'), {
        q: q.value || undefined,
        shop_category_id: categoryId.value || undefined,
    }, { preserveState: true, replace: true });
});

async function destroy(id) {
    const ok = await swalConfirm('Hapus produk ini?', { title: 'Hapus Produk', confirmButtonText: 'Hapus', icon: 'warning' });
    if (!ok) return;
    router.delete(route('admin.products.destroy', id));
}
</script>

<template>
    <AdminLayout title="Produk Toko">
        <Head title="Admin Produk" />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <input v-model="q" type="search" placeholder="Cari produk..." class="rounded-xl border-black/10 text-sm" />
                <select v-model="categoryId" class="rounded-xl border-black/10 text-sm">
                    <option value="">Semua kategori</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
            </div>
            <Link :href="route('admin.products.create')" class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white">
                Tambah Produk
            </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Harga</th>
                        <th class="px-4 py-3">Stok</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in products.data" :key="product.id" class="border-t border-black/5">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-16 overflow-hidden rounded-lg bg-mist">
                                    <img v-if="product.cover_image_url" :src="product.cover_image_url" :alt="product.name" class="h-full w-full object-cover" />
                                </div>
                                <div>
                                    <p class="font-semibold">{{ product.name }}</p>
                                    <p class="text-xs text-neutral-400">{{ product.variants_count }} varian</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ product.category?.name || '—' }}</td>
                        <td class="px-4 py-3 font-semibold">{{ product.price_label || '—' }}</td>
                        <td class="px-4 py-3">{{ product.stock }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase" :class="product.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                {{ product.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <Link :href="route('admin.products.edit', product.id)" class="mr-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand">Edit</Link>
                            <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600" @click="destroy(product.id)">Hapus</button>
                        </td>
                    </tr>
                    <tr v-if="!products.data?.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-neutral-500">Belum ada produk.</td>
                    </tr>
                </tbody>
            </table>
            <PaginationBar :links="products.links" :from="products.from" :to="products.to" :total="products.total" />
        </div>
    </AdminLayout>
</template>
