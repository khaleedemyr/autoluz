<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    orders: { type: Object, required: true },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="t('shop_orders')" />

        <section class="container-editorial py-10 lg:py-14">
            <p class="section-label">{{ t('shop_label') }}</p>
            <h1 class="font-display mt-3 text-5xl tracking-[-0.04em]">{{ t('shop_orders') }}</h1>
            <p class="mt-2 max-w-xl text-sm text-neutral-600">{{ t('shop_orders_desc') }}</p>

            <div v-if="orders.data?.length" class="mt-8 space-y-4">
                <Link
                    v-for="order in orders.data"
                    :key="order.id"
                    :href="order.url"
                    class="block rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft transition hover:-translate-y-0.5 hover:border-brand/30 hover:shadow-lift"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">{{ order.number }}</p>
                            <p class="mt-1 font-display text-2xl">{{ order.grand_total_label }}</p>
                            <p class="mt-1 text-sm text-neutral-500">{{ order.courier }} {{ order.courier_service }}</p>
                        </div>
                        <span class="rounded-full bg-mist px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em]">{{ order.status_label }}</span>
                    </div>
                    <div class="mt-4 flex gap-1">
                        <span
                            v-for="step in order.timeline"
                            :key="step.key"
                            class="h-1.5 flex-1 rounded-full"
                            :class="step.done ? 'bg-brand' : 'bg-mist'"
                        />
                    </div>
                </Link>
            </div>
            <p v-else class="mt-10 rounded-2xl border border-dashed border-[var(--line)] py-16 text-center text-sm text-neutral-500">
                {{ t('shop_orders_empty') }}
            </p>
            <SitePagination :links="orders.links" />
        </section>
    </AppLayout>
</template>
