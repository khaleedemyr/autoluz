<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    cart: { type: Object, required: true },
});

const { t } = useI18n();
const groups = () => props.cart.groups?.length ? props.cart.groups : [{ id: 0, store: null, items: props.cart.items || [] }];

function updateQty(item, qty) {
    router.patch(route('shop.cart.update', item.id), { qty }, { preserveScroll: true });
}

function removeItem(item) {
    router.delete(route('shop.cart.destroy', item.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('shop_cart')" />

        <section class="container-editorial py-10 lg:py-14">
            <p class="section-label">{{ t('shop_label') }}</p>
            <h1 class="font-display mt-3 text-5xl tracking-[-0.04em]">{{ t('shop_cart') }}</h1>

            <div v-if="cart.items?.length" class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="space-y-8">
                    <div v-for="group in groups()" :key="group.id || 'none'" class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <Link
                                v-if="group.store"
                                :href="group.store.url"
                                class="text-sm font-semibold uppercase tracking-[0.12em] hover:text-brand"
                            >
                                {{ group.store.is_official ? t('shop_official') + ' · ' : '' }}{{ group.store.name }}
                            </Link>
                            <p v-else class="text-sm font-semibold uppercase tracking-[0.12em] text-neutral-500">{{ t('shop_unknown_store') }}</p>
                            <p class="text-sm font-semibold">{{ group.subtotal_label }}</p>
                        </div>
                        <p v-if="group.store && !group.origin_ready" class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">{{ t('shop_store_origin_missing') }}</p>
                        <div v-for="item in group.items" :key="item.id" class="flex gap-4 rounded-2xl border border-[var(--line)] bg-white p-4 shadow-soft">
                            <Link :href="item.url" class="h-24 w-20 shrink-0 overflow-hidden rounded-xl bg-mist">
                                <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-full w-full object-cover" />
                            </Link>
                            <div class="min-w-0 flex-1">
                                <Link :href="item.url" class="font-semibold hover:text-brand">{{ item.name }}</Link>
                                <p class="text-xs text-neutral-500">{{ item.variant_label }}</p>
                                <p class="mt-1 text-sm font-semibold">{{ item.price_label }}</p>
                                <p v-if="!item.in_stock" class="text-xs text-red-600">{{ t('shop_stock_short') }}</p>
                                <div class="mt-3 flex items-center gap-3">
                                    <input
                                        :value="item.qty"
                                        type="number"
                                        min="1"
                                        :max="item.stock"
                                        class="w-20 rounded-xl border-black/10 text-sm"
                                        @change="updateQty(item, Number($event.target.value))"
                                    />
                                    <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600" @click="removeItem(item)">
                                        {{ t('shop_remove') }}
                                    </button>
                                </div>
                            </div>
                            <p class="hidden font-semibold sm:block">{{ item.line_total_label }}</p>
                        </div>
                    </div>
                </div>

                <aside class="h-fit rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-400">{{ t('shop_subtotal') }}</p>
                    <p class="mt-2 font-display text-3xl">{{ cart.subtotal_label }}</p>
                    <p class="mt-2 text-xs text-neutral-500">{{ t('shop_split_hint') }}</p>
                    <Link
                        :href="route('shop.checkout')"
                        class="mt-5 block rounded-full bg-brand px-5 py-3 text-center text-xs font-semibold uppercase tracking-[0.16em] text-white"
                    >
                        {{ t('shop_checkout') }}
                    </Link>
                    <Link :href="route('shop.index')" class="mt-3 block text-center text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500 hover:text-brand">
                        {{ t('shop_continue') }}
                    </Link>
                </aside>
            </div>

            <p v-else class="mt-10 rounded-2xl border border-dashed border-[var(--line)] py-16 text-center text-sm text-neutral-500">
                {{ t('shop_cart_empty') }}
                <Link :href="route('shop.index')" class="ml-1 text-brand">{{ t('shop_see_all') }}</Link>
            </p>
        </section>
    </AppLayout>
</template>
