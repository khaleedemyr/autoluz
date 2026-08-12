<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import BrandLogo from '@/Components/Site/BrandLogo.vue';
import FollowBrandButton from '@/Components/Site/FollowBrandButton.vue';
import VehicleCard from '@/Components/Site/VehicleCard.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    brand: { type: Object, required: true },
    vehicles: { type: Array, default: () => [] },
    articles: { type: Object, required: true },
    tab: { type: String, default: 'lineup' },
});

const { t, paginationLabel } = useI18n();

const activeTab = ref(props.tab === 'articles' ? 'articles' : 'lineup');
const mobileFiltersOpen = ref(false);

const filters = ref({
    q: '',
    bodyType: 'all',
    year: 'all',
    fuel: 'all',
    price: 'all',
    sort: 'name',
});

watch(
    () => props.tab,
    (value) => {
        activeTab.value = value === 'articles' ? 'articles' : 'lineup';
    },
);

onMounted(() => {
    if (!props.vehicles.length && props.articles.data?.length) {
        activeTab.value = 'articles';
    }
});

function setTab(tab) {
    activeTab.value = tab;
    router.get(
        route('brands.show', props.brand.slug),
        { tab },
        { preserveState: true, preserveScroll: true, replace: true, only: ['tab', 'articles'] },
    );
}

function specValue(vehicle, patterns) {
    const specs = vehicle.specs || [];
    const row = specs.find((item) => patterns.some((re) => re.test(String(item.label || ''))));
    return row?.value ? String(row.value) : '';
}

function fuelOf(vehicle) {
    const raw = specValue(vehicle, [/bbm/i, /bahan\s*bakar/i, /fuel/i]);
    if (!raw) return t('vehicles_other');
    if (/hybrid/i.test(raw)) return 'Hybrid';
    if (/diesel/i.test(raw)) return 'Diesel';
    if (/listrik|electric|ev/i.test(raw)) return 'EV';
    if (/bensin|petrol|gasoline/i.test(raw)) return 'Bensin';
    return raw.split(/[/,·|]/)[0].trim() || t('vehicles_other');
}

const bodyTypes = computed(() => {
    const counts = {};
    props.vehicles.forEach((vehicle) => {
        const type = vehicle.body_type || t('vehicles_other');
        counts[type] = (counts[type] || 0) + 1;
    });
    return Object.entries(counts)
        .map(([name, count]) => ({ name, count }))
        .sort((a, b) => a.name.localeCompare(b.name));
});

const years = computed(() => {
    const counts = {};
    props.vehicles.forEach((vehicle) => {
        const year = vehicle.model_year || t('vehicles_other');
        counts[year] = (counts[year] || 0) + 1;
    });
    return Object.entries(counts)
        .map(([name, count]) => ({ name, count }))
        .sort((a, b) => String(b.name).localeCompare(String(a.name)));
});

const fuels = computed(() => {
    const counts = {};
    props.vehicles.forEach((vehicle) => {
        const fuel = fuelOf(vehicle);
        counts[fuel] = (counts[fuel] || 0) + 1;
    });
    return Object.entries(counts)
        .map(([name, count]) => ({ name, count }))
        .sort((a, b) => a.name.localeCompare(b.name));
});

const priceRanges = [
    { id: 'all', labelKey: 'vehicles_filter_all' },
    { id: '0-300', labelKey: 'vehicles_price_under_300', min: 0, max: 300000000 },
    { id: '300-600', labelKey: 'vehicles_price_300_600', min: 300000000, max: 600000000 },
    { id: '600-1000', labelKey: 'vehicles_price_600_1000', min: 600000000, max: 1000000000 },
    { id: '1000+', labelKey: 'vehicles_price_over_1000', min: 1000000000, max: Infinity },
];

function matchesPrice(vehicle) {
    const range = priceRanges.find((item) => item.id === filters.value.price);
    if (!range || range.id === 'all') return true;
    const price = Number(vehicle.price_from || 0);
    if (!price) return false;
    return price >= range.min && price < range.max;
}

const filteredVehicles = computed(() => {
    const q = filters.value.q.trim().toLowerCase();

    let list = props.vehicles.filter((vehicle) => {
        if (filters.value.bodyType !== 'all') {
            const type = vehicle.body_type || t('vehicles_other');
            if (type !== filters.value.bodyType) return false;
        }
        if (filters.value.year !== 'all') {
            const year = vehicle.model_year || t('vehicles_other');
            if (year !== filters.value.year) return false;
        }
        if (filters.value.fuel !== 'all' && fuelOf(vehicle) !== filters.value.fuel) return false;
        if (!matchesPrice(vehicle)) return false;
        if (q) {
            const hay = [
                vehicle.name,
                vehicle.excerpt,
                vehicle.body_type,
                ...(vehicle.specs || []).flatMap((row) => [row.label, row.value]),
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            if (!hay.includes(q)) return false;
        }
        return true;
    });

    const sorted = [...list];
    switch (filters.value.sort) {
        case 'price_asc':
            sorted.sort((a, b) => (a.price_from || Number.MAX_SAFE_INTEGER) - (b.price_from || Number.MAX_SAFE_INTEGER));
            break;
        case 'price_desc':
            sorted.sort((a, b) => (b.price_from || 0) - (a.price_from || 0));
            break;
        case 'year_desc':
            sorted.sort((a, b) => String(b.model_year || '').localeCompare(String(a.model_year || '')));
            break;
        default:
            sorted.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
    }
    return sorted;
});

const perPage = 8;
const vehiclePage = ref(1);

watch(filters, () => {
    vehiclePage.value = 1;
}, { deep: true });

const vehicleTotalPages = computed(() => Math.max(1, Math.ceil(filteredVehicles.value.length / perPage)));

const pagedVehicles = computed(() => {
    const page = Math.min(vehiclePage.value, vehicleTotalPages.value);
    const start = (page - 1) * perPage;
    return filteredVehicles.value.slice(start, start + perPage);
});

const vehicleLinks = computed(() => {
    const total = vehicleTotalPages.value;
    if (total <= 1) return [];

    const links = [
        {
            label: '&laquo; Previous',
            url: vehiclePage.value > 1 ? `#vpage-${vehiclePage.value - 1}` : null,
            active: false,
            page: vehiclePage.value > 1 ? vehiclePage.value - 1 : null,
        },
    ];

    for (let i = 1; i <= total; i += 1) {
        links.push({
            label: String(i),
            url: `#vpage-${i}`,
            active: i === vehiclePage.value,
            page: i,
        });
    }

    links.push({
        label: 'Next &raquo;',
        url: vehiclePage.value < total ? `#vpage-${vehiclePage.value + 1}` : null,
        active: false,
        page: vehiclePage.value < total ? vehiclePage.value + 1 : null,
    });

    return links;
});

function goVehiclePage(page) {
    if (!page || page < 1 || page > vehicleTotalPages.value) return;
    vehiclePage.value = page;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

const hasActiveFilters = computed(() =>
    filters.value.q !== ''
    || filters.value.bodyType !== 'all'
    || filters.value.year !== 'all'
    || filters.value.fuel !== 'all'
    || filters.value.price !== 'all'
    || filters.value.sort !== 'name',
);

function resetFilters() {
    filters.value = {
        q: '',
        bodyType: 'all',
        year: 'all',
        fuel: 'all',
        price: 'all',
        sort: 'name',
    };
}

const articleLinks = computed(() =>
    (props.articles.links || []).map((link) => {
        if (!link.url) return link;
        try {
            const url = new URL(link.url, window.location.origin);
            url.searchParams.set('tab', 'articles');
            return { ...link, url: url.pathname + url.search };
        } catch {
            return link;
        }
    }),
);
</script>

<template>
    <AppLayout>
        <Head :title="brand.name" />

        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial flex flex-wrap items-end justify-between gap-4 py-10 lg:py-14">
                <div class="flex items-center gap-5">
                    <BrandLogo :brand="brand" size="lg" />
                    <div>
                        <p class="section-label">{{ brand.type_label || t('brands_label') }}</p>
                        <h1 class="font-display mt-2 text-5xl tracking-[-0.04em]">{{ brand.name }}</h1>
                        <p v-if="brand.description" class="mt-3 max-w-2xl text-neutral-600">{{ brand.description }}</p>
                        <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ brand.vehicles_count || vehicles.length || 0 }} {{ t('vehicles_label') }}
                            ·
                            {{ brand.articles_count || 0 }} {{ t('brands_articles') }}
                        </p>
                    </div>
                </div>
                <FollowBrandButton :brand-id="brand.id" />
            </div>

            <div class="container-editorial">
                <div class="flex gap-1 border-b border-[var(--line)]">
                    <button
                        type="button"
                        class="relative -mb-px border-b-2 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                        :class="activeTab === 'lineup' ? 'border-brand text-brand' : 'border-transparent text-neutral-400 hover:text-charcoal'"
                        @click="setTab('lineup')"
                    >
                        {{ t('vehicles_tab_lineup') }}
                        <span class="ml-1 text-neutral-400">({{ vehicles.length }})</span>
                    </button>
                    <button
                        type="button"
                        class="relative -mb-px border-b-2 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                        :class="activeTab === 'articles' ? 'border-brand text-brand' : 'border-transparent text-neutral-400 hover:text-charcoal'"
                        @click="setTab('articles')"
                    >
                        {{ t('vehicles_tab_articles') }}
                        <span class="ml-1 text-neutral-400">({{ brand.articles_count || articles.data?.length || 0 }})</span>
                    </button>
                </div>
            </div>
        </section>

        <section v-if="activeTab === 'lineup'" class="container-editorial py-8 lg:py-10">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 lg:hidden">
                <p class="text-sm text-neutral-500">{{ filteredVehicles.length }} {{ t('vehicles_showing') }}</p>
                <button
                    type="button"
                    class="rounded-full border border-[var(--line)] bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em]"
                    @click="mobileFiltersOpen = !mobileFiltersOpen"
                >
                    {{ t('vehicles_filters') }}
                </button>
            </div>

            <div class="grid gap-6 lg:grid-cols-[16.5rem_minmax(0,1fr)]">
                <aside
                    class="space-y-5 rounded-2xl border border-[var(--line)] bg-white p-4 shadow-soft lg:sticky lg:top-28 lg:self-start"
                    :class="mobileFiltersOpen ? 'block' : 'hidden lg:block'"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                            {{ t('vehicles_filters') }}
                        </h2>
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="text-[11px] font-semibold uppercase tracking-[0.12em] text-brand"
                            @click="resetFilters"
                        >
                            {{ t('vehicles_filter_reset') }}
                        </button>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ t('vehicles_filter_search') }}
                        </label>
                        <input
                            v-model="filters.q"
                            type="search"
                            :placeholder="t('vehicles_filter_search_ph')"
                            class="w-full rounded-xl border-[var(--line)] text-sm"
                        />
                    </div>

                    <div>
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ t('vehicles_filter_type') }}
                        </p>
                        <div class="space-y-1">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm transition"
                                :class="filters.bodyType === 'all' ? 'bg-brand/10 font-semibold text-brand' : 'text-neutral-600 hover:bg-mist'"
                                @click="filters.bodyType = 'all'"
                            >
                                <span>{{ t('vehicles_filter_all') }}</span>
                                <span class="text-xs text-neutral-400">{{ vehicles.length }}</span>
                            </button>
                            <button
                                v-for="type in bodyTypes"
                                :key="type.name"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm transition"
                                :class="filters.bodyType === type.name ? 'bg-brand/10 font-semibold text-brand' : 'text-neutral-600 hover:bg-mist'"
                                @click="filters.bodyType = type.name"
                            >
                                <span>{{ type.name }}</span>
                                <span class="text-xs text-neutral-400">{{ type.count }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="years.length">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ t('vehicles_filter_year') }}
                        </p>
                        <div class="space-y-1">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm transition"
                                :class="filters.year === 'all' ? 'bg-brand/10 font-semibold text-brand' : 'text-neutral-600 hover:bg-mist'"
                                @click="filters.year = 'all'"
                            >
                                <span>{{ t('vehicles_filter_all') }}</span>
                            </button>
                            <button
                                v-for="year in years"
                                :key="year.name"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm transition"
                                :class="filters.year === year.name ? 'bg-brand/10 font-semibold text-brand' : 'text-neutral-600 hover:bg-mist'"
                                @click="filters.year = year.name"
                            >
                                <span>{{ year.name }}</span>
                                <span class="text-xs text-neutral-400">{{ year.count }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="fuels.length">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ t('vehicles_filter_fuel') }}
                        </p>
                        <div class="space-y-1">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm transition"
                                :class="filters.fuel === 'all' ? 'bg-brand/10 font-semibold text-brand' : 'text-neutral-600 hover:bg-mist'"
                                @click="filters.fuel = 'all'"
                            >
                                <span>{{ t('vehicles_filter_all') }}</span>
                            </button>
                            <button
                                v-for="fuel in fuels"
                                :key="fuel.name"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm transition"
                                :class="filters.fuel === fuel.name ? 'bg-brand/10 font-semibold text-brand' : 'text-neutral-600 hover:bg-mist'"
                                @click="filters.fuel = fuel.name"
                            >
                                <span>{{ fuel.name }}</span>
                                <span class="text-xs text-neutral-400">{{ fuel.count }}</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ t('vehicles_filter_price') }}
                        </p>
                        <div class="space-y-1">
                            <button
                                v-for="range in priceRanges"
                                :key="range.id"
                                type="button"
                                class="flex w-full items-center rounded-lg px-2.5 py-2 text-left text-sm transition"
                                :class="filters.price === range.id ? 'bg-brand/10 font-semibold text-brand' : 'text-neutral-600 hover:bg-mist'"
                                @click="filters.price = range.id"
                            >
                                {{ t(range.labelKey) }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ t('vehicles_filter_sort') }}
                        </label>
                        <select v-model="filters.sort" class="w-full rounded-xl border-[var(--line)] text-sm">
                            <option value="name">{{ t('vehicles_sort_name') }}</option>
                            <option value="price_asc">{{ t('vehicles_sort_price_asc') }}</option>
                            <option value="price_desc">{{ t('vehicles_sort_price_desc') }}</option>
                            <option value="year_desc">{{ t('vehicles_sort_year') }}</option>
                        </select>
                    </div>
                </aside>

                <div>
                    <div class="mb-4 hidden items-end justify-between gap-3 lg:flex">
                        <div>
                            <p class="section-label">{{ t('vehicles_label') }}</p>
                            <h2 class="font-display mt-1 text-3xl tracking-[-0.04em]">
                                {{ t('vehicles_brand_title', { brand: brand.name }) }}
                            </h2>
                        </div>
                        <p class="text-sm text-neutral-500">
                            {{ filteredVehicles.length }} {{ t('vehicles_showing') }}
                        </p>
                    </div>

                    <div v-if="!vehicles.length" class="rounded-2xl border border-dashed border-[var(--line)] bg-white px-5 py-12 text-center text-sm text-neutral-500">
                        {{ t('vehicles_empty') }}
                    </div>
                    <template v-else-if="filteredVehicles.length">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <VehicleCard v-for="vehicle in pagedVehicles" :key="vehicle.id" :vehicle="vehicle" />
                        </div>
                        <div v-if="vehicleLinks.length" class="mt-10 flex flex-wrap gap-2">
                            <button
                                v-for="(link, index) in vehicleLinks"
                                :key="`${link.label}-${index}`"
                                type="button"
                                class="border px-3 py-1.5 text-sm transition"
                                :class="[
                                    link.active ? 'border-brand bg-brand text-white' : 'border-[var(--line)]',
                                    link.page ? 'hover:border-brand' : 'cursor-not-allowed opacity-40',
                                ]"
                                :disabled="!link.page"
                                @click="goVehiclePage(link.page)"
                            >
                                {{ paginationLabel(link.label) }}
                            </button>
                        </div>
                    </template>
                    <p
                        v-else
                        class="rounded-2xl border border-dashed border-[var(--line)] bg-white px-5 py-12 text-center text-sm text-neutral-500"
                    >
                        {{ t('vehicles_empty_filter') }}
                    </p>
                </div>
            </div>
        </section>

        <section v-else class="container-editorial py-8 lg:py-10 pb-16">
            <div class="mb-6">
                <p class="section-label">{{ t('latest_label') }}</p>
                <h2 class="font-display mt-2 text-3xl tracking-[-0.04em]">{{ t('brands_articles_title') }}</h2>
            </div>
            <div v-if="articles.data?.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <ArticleCard v-for="article in articles.data" :key="article.id" :article="article" />
            </div>
            <p v-else class="rounded-2xl border border-dashed border-[var(--line)] bg-white px-5 py-12 text-center text-sm text-neutral-500">
                {{ t('brands_empty_articles') }}
            </p>
            <SitePagination :links="articleLinks" />
        </section>
    </AppLayout>
</template>
