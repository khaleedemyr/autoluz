<script setup>
import { onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { swalError } from '@/utils/swal';
import axios from 'axios';

const props = defineProps({
    checkout: { type: Object, required: true },
    snap_token: { type: String, default: null },
    midtrans: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

function loadSnap() {
    return new Promise((resolve, reject) => {
        if (window.snap) {
            resolve();
            return;
        }
        if (!props.midtrans.snap_url) {
            reject(new Error('missing snap'));
            return;
        }
        const script = document.createElement('script');
        script.src = props.midtrans.snap_url;
        script.setAttribute('data-client-key', props.midtrans.client_key || '');
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('snap failed'));
        document.head.appendChild(script);
    });
}

async function pay(token) {
    try {
        let next = token;
        if (!next) {
            const { data } = await axios.post(route('shop.checkouts.pay', props.checkout.number));
            next = data.token;
        }
        await loadSnap();
        window.snap.pay(next, {
            onSuccess: () => router.reload(),
            onPending: () => router.reload(),
            onError: () => swalError(t('shop_pay_failed')),
            onClose: () => {},
        });
    } catch (e) {
        swalError(e.response?.data?.message || t('shop_pay_failed'));
    }
}

onMounted(() => {
    if (props.snap_token && props.checkout.can_pay) {
        pay(props.snap_token);
    }
});
</script>

<template>
    <AppLayout>
        <Head :title="checkout.number" />

        <section class="container-editorial py-10 lg:py-14">
            <Link :href="route('shop.orders.index')" class="text-sm text-neutral-500 hover:text-brand">← {{ t('shop_orders') }}</Link>
            <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="section-label">{{ t('shop_label') }}</p>
                    <h1 class="font-display mt-2 text-4xl tracking-[-0.04em]">{{ checkout.number }}</h1>
                    <p class="mt-2 text-neutral-500">{{ checkout.status_label }}</p>
                </div>
                <button
                    v-if="checkout.can_pay"
                    type="button"
                    class="rounded-full bg-brand px-5 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-white"
                    @click="pay()"
                >
                    {{ t('shop_pay_now') }}
                </button>
            </div>

            <div class="mt-8 space-y-4">
                <div v-for="order in checkout.orders" :key="order.id" class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">{{ order.store?.name || t('shop_partner') }}</p>
                            <Link :href="order.url" class="mt-1 block font-display text-2xl hover:text-brand">{{ order.number }}</Link>
                            <p class="mt-1 text-sm text-neutral-500">{{ order.status_label }} · {{ order.courier }} {{ order.courier_service }}</p>
                        </div>
                        <p class="font-semibold">{{ order.grand_total_label }}</p>
                    </div>
                    <div v-for="item in order.items" :key="item.id" class="mt-3 flex gap-3 border-t border-[var(--line)] pt-3">
                        <img v-if="item.image_url" :src="item.image_url" alt="" class="h-14 w-14 rounded-lg object-cover" />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ item.name }}</p>
                            <p class="text-xs text-neutral-500">{{ item.variant_label }} · ×{{ item.qty }}</p>
                        </div>
                        <p class="text-sm font-semibold">{{ item.line_total_label }}</p>
                    </div>
                </div>
            </div>

            <aside class="mt-6 h-fit rounded-2xl border border-[var(--line)] bg-charcoal p-5 text-white shadow-lift">
                <p class="text-[11px] uppercase tracking-[0.16em] text-white/45">{{ t('shop_total') }}</p>
                <p class="mt-2 font-display text-4xl">{{ checkout.grand_total_label }}</p>
                <p class="mt-3 text-sm text-white/60">{{ t('shop_subtotal') }} {{ checkout.subtotal_label }}</p>
                <p class="text-sm text-white/60">{{ t('shop_shipping') }} {{ checkout.shipping_cost_label }}</p>
            </aside>
        </section>
    </AppLayout>
</template>
