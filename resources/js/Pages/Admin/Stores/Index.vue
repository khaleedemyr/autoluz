<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';

const props = defineProps({
    stores: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '', status: '' }) },
    statuses: { type: Array, default: () => [] },
});

const q = ref(props.filters.q || '');
const status = ref(props.filters.status || '');

watch([q, status], () => {
    router.get(route('admin.stores.index'), {
        q: q.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
});

async function destroy(id) {
    const ok = await swalConfirm('Hapus toko partner ini?', { title: 'Hapus Toko', confirmButtonText: 'Hapus', icon: 'warning' });
    if (!ok) return;
    router.delete(route('admin.stores.destroy', id));
}
</script>

<template>
    <AdminLayout title="Toko Partner">
        <Head title="Toko Partner" />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <input v-model="q" type="search" placeholder="Cari toko / owner..." class="rounded-xl border-black/10 text-sm" />
                <select v-model="status" class="rounded-xl border-black/10 text-sm">
                    <option value="">Semua status</option>
                    <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>
            <Link :href="route('admin.stores.create')" class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white">
                Tambah Toko
            </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Toko</th>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3">Asal</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="store in stores.data" :key="store.id" class="border-t border-black/5">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ store.name }}</p>
                            <p class="text-xs text-neutral-400">{{ store.slug }} <span v-if="store.is_official">· Official</span></p>
                        </td>
                        <td class="px-4 py-3 text-neutral-600">
                            <p>{{ store.owner?.name || '—' }}</p>
                            <p class="text-xs text-neutral-400">{{ store.owner?.email }}</p>
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ store.origin_city_name || 'Belum diisi' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase" :class="store.status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                {{ store.status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ store.products_count }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <Link :href="route('admin.stores.edit', store.id)" class="mr-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand">Edit</Link>
                            <button v-if="!store.is_official" type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600" @click="destroy(store.id)">Hapus</button>
                        </td>
                    </tr>
                    <tr v-if="!stores.data?.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-neutral-500">Belum ada toko partner.</td>
                    </tr>
                </tbody>
            </table>
            <PaginationBar :links="stores.links" :from="stores.from" :to="stores.to" :total="stores.total" />
        </div>
    </AdminLayout>
</template>
