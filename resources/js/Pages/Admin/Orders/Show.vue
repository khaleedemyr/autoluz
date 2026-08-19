<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SellerLayout from '@/Layouts/SellerLayout.vue';

const props = defineProps({
    mode: { type: String, default: 'admin' },
    order: { type: Object, required: true },
    statuses: { type: Array, default: () => [] },
});

const isSeller = computed(() => props.mode === 'seller');
const layout = computed(() => (isSeller.value ? SellerLayout : AdminLayout));

const form = useForm({
    status: props.order.status,
    tracking_number: props.order.tracking_number || '',
});

function submit() {
    form.put(route(isSeller.value ? 'seller.orders.update' : 'admin.orders.update', props.order.id));
}
</script>

<template>
    <component :is="layout" :title="`Pesanan ${order.number}`">
        <Head :title="`Pesanan ${order.number}`" />

        <div class="mb-4">
            <Link :href="route(isSeller ? 'seller.orders.index' : 'admin.orders.index')" class="text-sm text-neutral-500 hover:text-brand">← Daftar pesanan</Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-4">
                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold">Item</h2>
                    <p v-if="order.store" class="mt-1 text-xs uppercase tracking-[0.12em] text-neutral-400">{{ order.store.name }}</p>
                    <div v-for="item in order.items" :key="item.id" class="mt-3 flex gap-3 border-t border-black/5 pt-3">
                        <img v-if="item.image_url" :src="item.image_url" alt="" class="h-16 w-16 rounded-lg object-cover" />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ item.name }}</p>
                            <p class="text-xs text-neutral-500">{{ item.variant_label }} · ×{{ item.qty }}</p>
                        </div>
                        <p class="font-semibold">{{ item.line_total_label }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold">Pengiriman</h2>
                    <p class="mt-2 text-sm">{{ order.recipient_name }} · {{ order.phone }}</p>
                    <p class="mt-1 text-sm text-neutral-600">{{ order.address }}, {{ order.city_name }}, {{ order.province_name }} {{ order.postal_code }}</p>
                    <p class="mt-2 text-sm">{{ order.courier }} {{ order.courier_service_name || order.courier_service }} — {{ order.shipping_cost_label }}</p>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.14em] text-neutral-400">Total</p>
                    <p class="mt-1 font-display text-3xl">{{ order.grand_total_label }}</p>
                    <p class="mt-2 text-sm text-neutral-500">Subtotal {{ order.subtotal_label }}</p>
                    <p v-if="order.user" class="mt-3 text-sm">{{ order.user.name }}<br>{{ order.user.email }}</p>
                </div>

                <form class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm" @submit.prevent="submit">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Status</label>
                    <select v-model="form.status" class="w-full rounded-xl border-black/10" :disabled="isSeller && order.status === 'pending_payment'">
                        <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                    </select>
                    <label class="mb-1 mt-4 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Nomor resi</label>
                    <input v-model="form.tracking_number" type="text" class="w-full rounded-xl border-black/10" />
                    <button type="submit" class="mt-4 w-full rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white" :disabled="form.processing || (isSeller && order.status === 'pending_payment')">
                        Simpan
                    </button>
                </form>
            </aside>
        </div>
    </component>
</template>
