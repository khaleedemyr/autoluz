<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';

const props = defineProps({
    mode: { type: String, default: 'admin' },
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '', status: '', store_id: '' }) },
    statuses: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
});

const isSeller = computed(() => props.mode === 'seller');
const layout = computed(() => (isSeller.value ? SellerLayout : AdminLayout));
const q = ref(props.filters.q || '');
const status = ref(props.filters.status || '');
const storeId = ref(props.filters.store_id || '');

function prefix() {
    return isSeller.value ? 'seller' : 'admin';
}

watch([q, status, storeId], () => {
    router.get(route(`${prefix()}.orders.index`), {
        q: q.value || undefined,
        status: status.value || undefined,
        store_id: !isSeller.value && storeId.value ? storeId.value : undefined,
    }, { preserveState: true, replace: true });
});
</script>

<template>
    <component :is="layout" title="Pesanan Toko">
        <Head :title="isSeller ? 'Pesanan Toko' : 'Admin Pesanan'" />

        <div class="mb-4 flex flex-wrap gap-2">
            <input v-model="q" type="search" placeholder="Cari nomor / nama / resi..." class="rounded-xl border-black/10 text-sm" />
            <select v-model="status" class="rounded-xl border-black/10 text-sm">
                <option value="">Semua status</option>
                <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
            <select v-if="!isSeller" v-model="storeId" class="rounded-xl border-black/10 text-sm">
                <option value="">Semua toko</option>
                <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Nomor</th>
                        <th class="px-4 py-3">Toko</th>
                        <th class="px-4 py-3">Pembeli</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Kurir</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders.data" :key="order.id" class="border-t border-black/5">
                        <td class="px-4 py-3 font-semibold">{{ order.number }}</td>
                        <td class="px-4 py-3 text-neutral-600">{{ order.store?.name || '—' }}</td>
                        <td class="px-4 py-3">
                            <p>{{ order.recipient_name }}</p>
                            <p class="text-xs text-neutral-400">{{ order.user?.email }}</p>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ order.grand_total_label }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-mist px-2 py-1 text-[11px] font-semibold uppercase">{{ order.status_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ order.courier }} {{ order.courier_service }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route(`${prefix()}.orders.show`, order.id)" class="text-xs font-semibold uppercase tracking-[0.12em] text-brand">Detail</Link>
                        </td>
                    </tr>
                    <tr v-if="!orders.data?.length">
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-neutral-500">Belum ada pesanan.</td>
                    </tr>
                </tbody>
            </table>
            <PaginationBar :links="orders.links" :from="orders.from" :to="orders.to" :total="orders.total" />
        </div>
    </component>
</template>
