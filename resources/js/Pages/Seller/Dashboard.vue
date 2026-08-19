<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';

defineProps({
    store: { type: Object, required: true },
    stats: { type: Object, required: true },
});

function rupiah(value) {
    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
}
</script>

<template>
    <SellerLayout title="Dashboard">
        <Head title="Dashboard Toko" />

        <div v-if="store.status !== 'approved'" class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Status toko: <strong>{{ store.status_label }}</strong>. Produk baru tampil di etalase setelah admin menyetujui toko. Lengkapi kota asal pengiriman di pengaturan.
        </div>
        <div v-else-if="!store.origin_ready" class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Kota asal pengiriman belum diisi. Pembeli tidak bisa checkout produk toko ini sampai asal diisi.
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-neutral-400">Produk</p>
                <p class="mt-2 font-display text-4xl tracking-[-0.04em]">{{ stats.products }}</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-neutral-400">Perlu dikemas</p>
                <p class="mt-2 font-display text-4xl tracking-[-0.04em]">{{ stats.orders_ship }}</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-neutral-400">Menunggu bayar</p>
                <p class="mt-2 font-display text-4xl tracking-[-0.04em]">{{ stats.orders_pending }}</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-neutral-400">Omzet</p>
                <p class="mt-2 font-display text-3xl tracking-[-0.04em]">{{ rupiah(stats.revenue) }}</p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <Link :href="route('seller.products.create')" class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white">+ Produk</Link>
            <Link :href="route('seller.orders.index')" class="rounded-full border border-black/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em]">Pesanan</Link>
            <Link :href="route('seller.settings.edit')" class="rounded-full border border-black/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em]">Pengaturan toko</Link>
        </div>
    </SellerLayout>
</template>
