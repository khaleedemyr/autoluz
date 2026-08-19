<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    total: { type: Number, default: 0 },
    routeName: { type: String, required: true },
    routeParams: { type: [Object, String, Number], default: undefined },
    showStores: { type: Boolean, default: true },
});

const { t } = useI18n();
const q = ref(props.filters.q || '');
const kategori = ref(props.filters.kategori || '');
const toko = ref(props.filters.toko || '');
const sort = ref(props.filters.sort || 'newest');
const min = ref(props.filters.min || '');
const max = ref(props.filters.max || '');
const priceOpen = ref(!!(props.filters.min || props.filters.max));

watch(() => props.filters, (next) => {
    q.value = next.q || '';
    kategori.value = next.kategori || '';
    toko.value = next.toko || '';
    sort.value = next.sort || 'newest';
    min.value = next.min || '';
    max.value = next.max || '';
}, { deep: true });

const active = computed(() => {
    const chips = [];
    if (kategori.value) {
        const cat = props.categories.find((row) => row.slug === kategori.value);
        chips.push({ key: 'kategori', label: cat?.name || kategori.value });
    }
    if (props.showStores && toko.value) {
        const store = props.stores.find((row) => row.slug === toko.value);
        chips.push({ key: 'toko', label: store?.name || toko.value });
    }
    if (min.value || max.value) {
        const lo = min.value ? `Rp ${Number(min.value).toLocaleString('id-ID')}` : '…';
        const hi = max.value ? `Rp ${Number(max.value).toLocaleString('id-ID')}` : '…';
        chips.push({ key: 'price', label: `${lo} – ${hi}` });
    }
    return chips;
});

function apply(next = {}) {
    const payload = {
        q: (next.q ?? q.value) || undefined,
        kategori: (next.kategori ?? kategori.value) || undefined,
        toko: props.showStores ? ((next.toko ?? toko.value) || undefined) : undefined,
        sort: (next.sort ?? sort.value) || undefined,
        min: (next.min ?? min.value) || undefined,
        max: (next.max ?? max.value) || undefined,
    };
    router.get(route(props.routeName, props.routeParams), payload, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function setCategory(slug) {
    kategori.value = kategori.value === slug ? '' : slug;
    apply({ kategori: kategori.value });
}

function setStore(slug) {
    toko.value = toko.value === slug ? '' : slug;
    apply({ toko: toko.value });
}

function setSort(value) {
    sort.value = value;
    apply({ sort: value });
}

function clearChip(key) {
    if (key === 'kategori') kategori.value = '';
    if (key === 'toko') toko.value = '';
    if (key === 'price') {
        min.value = '';
        max.value = '';
        priceOpen.value = false;
    }
    apply({
        kategori: key === 'kategori' ? '' : kategori.value,
        toko: key === 'toko' ? '' : toko.value,
        min: key === 'price' ? '' : min.value,
        max: key === 'price' ? '' : max.value,
    });
}

function clearAll() {
    q.value = '';
    kategori.value = '';
    toko.value = '';
    sort.value = 'newest';
    min.value = '';
    max.value = '';
    apply({ q: '', kategori: '', toko: '', sort: 'newest', min: '', max: '' });
}

function applyPrice() {
    apply();
}

const chipClass = (on) => on
    ? 'border-charcoal bg-charcoal text-white'
    : 'border-[var(--line)] bg-white text-charcoal hover:border-charcoal/40';
</script>

<template>
    <div class="sticky top-[4.25rem] z-30 border-b border-[var(--line)] bg-[#f6f5f2]/92 backdrop-blur-md lg:top-[4.75rem]">
        <div class="container-editorial py-3 lg:py-4">
            <form class="flex flex-col gap-3 lg:flex-row lg:items-center" @submit.prevent="apply()">
                <label class="relative min-w-0 flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-neutral-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3-3" /></svg>
                    </span>
                    <input
                        v-model="q"
                        type="search"
                        :placeholder="t('shop_search')"
                        class="w-full rounded-full border-[var(--line)] bg-white py-2.5 pl-10 pr-4 text-sm shadow-none focus:border-charcoal focus:ring-0"
                    />
                </label>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        :value="sort"
                        class="rounded-full border-[var(--line)] bg-white py-2.5 pl-4 pr-9 text-xs font-semibold uppercase tracking-[0.12em]"
                        @change="setSort($event.target.value)"
                    >
                        <option value="newest">{{ t('shop_sort_newest') }}</option>
                        <option value="price_asc">{{ t('shop_sort_price_asc') }}</option>
                        <option value="price_desc">{{ t('shop_sort_price_desc') }}</option>
                        <option value="name">{{ t('shop_sort_name') }}</option>
                    </select>
                    <button
                        type="button"
                        class="rounded-full border px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.12em]"
                        :class="chipClass(priceOpen || min || max)"
                        @click="priceOpen = !priceOpen"
                    >
                        {{ t('shop_price_range') }}
                    </button>
                    <button type="submit" class="rounded-full bg-brand px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white">
                        {{ t('shop_filter') }}
                    </button>
                </div>
            </form>

            <div v-if="priceOpen" class="mt-3 flex flex-wrap items-center gap-2">
                <input v-model="min" type="number" min="0" :placeholder="t('shop_min_price')" class="w-36 rounded-full border-[var(--line)] bg-white text-sm" />
                <span class="text-neutral-400">—</span>
                <input v-model="max" type="number" min="0" :placeholder="t('shop_max_price')" class="w-36 rounded-full border-[var(--line)] bg-white text-sm" />
                <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-brand" @click="applyPrice">{{ t('shop_apply_price') }}</button>
            </div>

            <div v-if="showStores && stores.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button
                    type="button"
                    class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em]"
                    :class="chipClass(!toko)"
                    @click="setStore('')"
                >
                    {{ t('shop_all_stores') }}
                </button>
                <button
                    v-for="store in stores"
                    :key="store.id"
                    type="button"
                    class="flex shrink-0 items-center gap-2 rounded-full border py-1 pl-1 pr-3 text-xs font-semibold"
                    :class="chipClass(toko === store.slug)"
                    @click="setStore(store.slug)"
                >
                    <img v-if="store.logo_url" :src="store.logo_url" alt="" class="h-6 w-6 rounded-full object-cover" />
                    <span class="max-w-[9rem] truncate">{{ store.name }}</span>
                </button>
            </div>

            <div v-if="categories.length" class="mt-2 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button
                    type="button"
                    class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em]"
                    :class="chipClass(!kategori)"
                    @click="setCategory('')"
                >
                    {{ t('shop_all_categories') }}
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em]"
                    :class="chipClass(kategori === cat.slug)"
                    @click="setCategory(cat.slug)"
                >
                    {{ cat.name }}
                </button>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-400">{{ t('shop_products_count', { count: total }) }}</p>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="chipItem in active"
                        :key="chipItem.key"
                        type="button"
                        class="rounded-full bg-charcoal px-3 py-1 text-[11px] font-semibold text-white"
                        @click="clearChip(chipItem.key)"
                    >
                        {{ chipItem.label }} ×
                    </button>
                    <button
                        v-if="active.length || q"
                        type="button"
                        class="text-[11px] font-semibold uppercase tracking-[0.12em] text-neutral-500 hover:text-brand"
                        @click="clearAll"
                    >
                        {{ t('shop_clear_filters') }}
                    </button>
                    <Link
                        v-if="!showStores"
                        :href="route('shop.index')"
                        class="text-[11px] font-semibold uppercase tracking-[0.12em] text-neutral-500 hover:text-brand"
                    >
                        ← {{ t('shop_see_all') }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
