<script setup>
import { onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { swalError } from '@/utils/swal';
import axios from 'axios';

const props = defineProps({
    order: { type: Object, required: true },
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
    const snapToken = token || props.snap_token || props.order.can_pay;
    try {
        let next = token;
        if (!next) {
            const { data } = await axios.post(route('shop.orders.pay', props.order.number));
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
    if (props.snap_token && props.order.can_pay) {
        pay(props.snap_token);
    }
});
</script>

<template>
    <AppLayout>
        <Head :title="order.number" />

        <section class="container-editorial py-10 lg:py-14">
            <Link :href="route('shop.orders.index')" class="text-sm text-neutral-500 hover:text-brand">← {{ t('shop_orders') }}</Link>
            <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="section-label">{{ t('shop_label') }}</p>
                    <h1 class="font-display mt-2 text-4xl tracking-[-0.04em]">{{ order.number }}</h1>
                    <p v-if="order.store" class="mt-1 text-sm">
                        <Link :href="order.store.url" class="text-brand hover:underline">{{ order.store.name }}</Link>
                    </p>
                    <p class="mt-2 text-neutral-500">{{ order.status_label }}</p>
                    <Link v-if="order.checkout_url" :href="order.checkout_url" class="mt-2 inline-block text-xs font-semibold uppercase tracking-[0.12em] text-brand">
                        {{ t('shop_parent_checkout') }} {{ order.checkout_number }}
                    </Link>
                </div>
                <button
                    v-if="order.can_pay"
                    type="button"
                    class="rounded-full bg-brand px-5 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-white"
                    @click="pay()"
                >
                    {{ t('shop_pay_now') }}
                </button>
            </div>

            <div class="mt-8 flex gap-1">
                <div v-for="step in order.timeline" :key="step.key" class="flex-1">
                    <div class="h-1.5 rounded-full" :class="step.done ? 'bg-brand' : 'bg-mist'" />
                    <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.12em]" :class="step.done ? 'text-charcoal' : 'text-neutral-400'">{{ step.label }}</p>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="space-y-4">
                    <div class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                        <h2 class="font-semibold">{{ t('shop_items') }}</h2>
                        <div v-for="item in order.items" :key="item.id" class="mt-3 flex gap-3 border-t border-[var(--line)] pt-3">
                            <img v-if="item.image_url" :src="item.image_url" alt="" class="h-16 w-16 rounded-lg object-cover" />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ item.name }}</p>
                                <p class="text-xs text-neutral-500">{{ item.variant_label }} · ×{{ item.qty }}</p>
                            </div>
                            <p class="font-semibold">{{ item.line_total_label }}</p>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                        <h2 class="font-semibold">{{ t('shop_shipping') }}</h2>
                        <p class="mt-2 text-sm">{{ order.recipient_name }} · {{ order.phone }}</p>
                        <p class="mt-1 text-sm text-neutral-600">{{ order.address }}, {{ order.city_name }}, {{ order.province_name }} {{ order.postal_code }}</p>
                        <p class="mt-2 text-sm">{{ order.courier }} {{ order.courier_service_name || order.courier_service }} — {{ order.shipping_cost_label }}</p>
                        <p v-if="order.tracking_number" class="mt-3 rounded-xl bg-mist px-3 py-2 text-sm font-semibold">
                            {{ t('shop_tracking') }}: {{ order.tracking_number }}
                        </p>
                    </div>
                </div>
                <aside class="h-fit rounded-2xl border border-[var(--line)] bg-charcoal p-5 text-white shadow-lift">
                    <p class="text-[11px] uppercase tracking-[0.16em] text-white/45">{{ t('shop_total') }}</p>
                    <p class="mt-2 font-display text-4xl">{{ order.grand_total_label }}</p>
                    <p class="mt-3 text-sm text-white/60">{{ t('shop_subtotal') }} {{ order.subtotal_label }}</p>
                    <p class="text-sm text-white/60">{{ t('shop_shipping') }} {{ order.shipping_cost_label }}</p>
                </aside>
            </div>
        </section>
    </AppLayout>
</template>
