<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { COMPARE_MAX, clearCompare, setCompareList } from '@/utils/compareVehicles';

const props = defineProps({
    vehicles: { type: Array, default: () => [] },
    spec_labels: { type: Array, default: () => [] },
    ids: { type: Array, default: () => [] },
    max: { type: Number, default: 3 },
});

const { t } = useI18n();
const query = ref('');
const results = ref([]);
const searching = ref(false);
const activeSlot = ref(null);
let searchTimer = null;

watch(
    () => props.vehicles,
    (list) => {
        setCompareList(list.map((vehicle) => ({
            id: vehicle.id,
            name: vehicle.name,
            brand: vehicle.brand?.name || '',
            cover_image_url: vehicle.cover_image_url,
        })));
    },
    { immediate: true },
);

const slots = computed(() => {
    const items = [...props.vehicles];
    while (items.length < props.max) items.push(null);
    return items.slice(0, props.max);
});

function specValue(vehicle, label) {
    const row = (vehicle?.specs || []).find((item) => item.label === label);
    return row?.value || '—';
}

function openPicker(index) {
    activeSlot.value = index;
    query.value = '';
    results.value = [];
    runSearch('');
}

function closePicker() {
    activeSlot.value = null;
    query.value = '';
    results.value = [];
}

function runSearch(value) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        searching.value = true;
        try {
            const exclude = props.ids.join(',');
            const url = route('vehicles.compare.search', { q: value || undefined, exclude: exclude || undefined });
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            results.value = json.data || [];
        } catch {
            results.value = [];
        } finally {
            searching.value = false;
        }
    }, 220);
}

watch(query, (value) => {
    if (activeSlot.value === null) return;
    runSearch(value);
});

function addVehicle(item) {
    const next = [...props.ids, item.id].slice(0, props.max);
    router.get(route('vehicles.compare'), { ids: next.join(',') }, { preserveScroll: true });
    closePicker();
}

function removeVehicle(id) {
    const next = props.ids.filter((item) => Number(item) !== Number(id));
    if (!next.length) {
        clearCompare();
        router.get(route('vehicles.compare'), {}, { preserveScroll: true });
        return;
    }
    router.get(route('vehicles.compare'), { ids: next.join(',') }, { preserveScroll: true });
}

function emptyAll() {
    clearCompare();
    router.get(route('vehicles.compare'));
}
</script>

<template>
    <AppLayout>
        <Head :title="t('compare_title')" />

        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <p class="section-label">{{ t('compare_label') }}</p>
                <h1 class="font-display mt-3 text-5xl tracking-[-0.04em]">{{ t('compare_title') }}</h1>
                <p class="mt-3 max-w-2xl text-neutral-600">{{ t('compare_desc', { max: max || COMPARE_MAX }) }}</p>
            </div>
        </section>

        <section class="container-editorial py-8 lg:py-10">
            <div class="grid gap-4" :class="max === 3 ? 'md:grid-cols-3' : 'md:grid-cols-2'">
                <div
                    v-for="(vehicle, index) in slots"
                    :key="vehicle?.id || `slot-${index}`"
                    class="relative overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft"
                >
                    <template v-if="vehicle">
                        <div class="aspect-[16/10] bg-neutral-100">
                            <img
                                v-if="vehicle.cover_image_url"
                                :src="vehicle.cover_image_url"
                                :alt="vehicle.name"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div class="p-4">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                                {{ vehicle.brand?.name }}
                                <span v-if="vehicle.model_year"> · {{ vehicle.model_year }}</span>
                            </p>
                            <h2 class="mt-1 font-display text-2xl tracking-[-0.03em]">{{ vehicle.name }}</h2>
                            <p v-if="vehicle.body_type" class="mt-1 text-sm text-neutral-500">{{ vehicle.body_type }}</p>
                            <p v-if="vehicle.price_label" class="mt-3 text-sm font-semibold">
                                {{ t('vehicles_from') }} {{ vehicle.price_label }}
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <Link
                                    :href="vehicle.url"
                                    class="rounded-full border border-[var(--line)] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-neutral-600 hover:border-brand hover:text-brand"
                                >
                                    {{ t('vehicles_detail') }}
                                </Link>
                                <button
                                    type="button"
                                    class="rounded-full border border-[var(--line)] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-red-600"
                                    @click="removeVehicle(vehicle.id)"
                                >
                                    {{ t('compare_remove') }}
                                </button>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <button
                            type="button"
                            class="flex min-h-[16rem] w-full flex-col items-center justify-center gap-2 px-4 py-10 text-center"
                            @click="openPicker(index)"
                        >
                            <span class="text-3xl text-neutral-300">+</span>
                            <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                                {{ t('compare_pick') }}
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <div v-if="vehicles.length >= 2" class="mt-10 overflow-x-auto rounded-2xl border border-[var(--line)] bg-white shadow-soft">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--line)] bg-mist/50">
                            <th class="sticky left-0 bg-mist/80 px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                                {{ t('vehicles_specs') }}
                            </th>
                            <th
                                v-for="vehicle in vehicles"
                                :key="`head-${vehicle.id}`"
                                class="min-w-[10rem] px-4 py-3 text-left font-display text-lg tracking-[-0.03em]"
                            >
                                {{ vehicle.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-[var(--line)]">
                            <td class="sticky left-0 bg-white px-4 py-3 text-neutral-500">{{ t('compare_row_price') }}</td>
                            <td v-for="vehicle in vehicles" :key="`price-${vehicle.id}`" class="px-4 py-3 font-semibold">
                                {{ vehicle.price_label || '—' }}
                            </td>
                        </tr>
                        <tr class="border-b border-[var(--line)]">
                            <td class="sticky left-0 bg-white px-4 py-3 text-neutral-500">{{ t('compare_row_type') }}</td>
                            <td v-for="vehicle in vehicles" :key="`type-${vehicle.id}`" class="px-4 py-3 font-semibold">
                                {{ vehicle.body_type || '—' }}
                            </td>
                        </tr>
                        <tr class="border-b border-[var(--line)]">
                            <td class="sticky left-0 bg-white px-4 py-3 text-neutral-500">{{ t('compare_row_year') }}</td>
                            <td v-for="vehicle in vehicles" :key="`year-${vehicle.id}`" class="px-4 py-3 font-semibold">
                                {{ vehicle.model_year || '—' }}
                            </td>
                        </tr>
                        <tr
                            v-for="label in spec_labels"
                            :key="label"
                            class="border-b border-[var(--line)] last:border-0"
                        >
                            <td class="sticky left-0 bg-white px-4 py-3 text-neutral-500">{{ label }}</td>
                            <td
                                v-for="vehicle in vehicles"
                                :key="`${vehicle.id}-${label}`"
                                class="px-4 py-3 font-semibold leading-snug"
                            >
                                {{ specValue(vehicle, label) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="mt-10 rounded-2xl border border-dashed border-[var(--line)] bg-white px-5 py-12 text-center">
                <p class="text-sm text-neutral-500">{{ t('compare_need_two') }}</p>
                <button
                    type="button"
                    class="mt-4 rounded-full bg-brand px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-white"
                    @click="openPicker(vehicles.length)"
                >
                    {{ t('compare_pick') }}
                </button>
            </div>

            <div v-if="vehicles.length" class="mt-6 flex justify-end">
                <button
                    type="button"
                    class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400 hover:text-brand"
                    @click="emptyAll"
                >
                    {{ t('compare_clear') }}
                </button>
            </div>
        </section>

        <div
            v-if="activeSlot !== null"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center"
            @click.self="closePicker"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-lift">
                <div class="border-b border-[var(--line)] p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-xl tracking-[-0.03em]">{{ t('compare_pick') }}</h3>
                        <button type="button" class="text-sm text-neutral-400" @click="closePicker">{{ t('compare_close') }}</button>
                    </div>
                    <input
                        v-model="query"
                        type="search"
                        :placeholder="t('compare_search_ph')"
                        class="mt-3 w-full rounded-xl border-[var(--line)] text-sm"
                        autofocus
                    />
                </div>
                <div class="max-h-80 overflow-y-auto p-2">
                    <p v-if="searching" class="px-3 py-4 text-sm text-neutral-400">{{ t('compare_searching') }}</p>
                    <button
                        v-for="item in results"
                        :key="item.id"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left hover:bg-mist"
                        @click="addVehicle(item)"
                    >
                        <div class="h-12 w-16 overflow-hidden rounded-lg bg-mist">
                            <img v-if="item.cover_image_url" :src="item.cover_image_url" :alt="item.name" class="h-full w-full object-cover" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold">{{ item.brand?.name }} {{ item.name }}</p>
                            <p class="text-xs text-neutral-400">
                                {{ item.body_type || '—' }}
                                <span v-if="item.price_label"> · {{ item.price_label }}</span>
                            </p>
                        </div>
                    </button>
                    <p v-if="!searching && !results.length" class="px-3 py-4 text-sm text-neutral-400">
                        {{ t('compare_search_empty') }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
