<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import {
    dpFromPercent,
    percentFromDp,
    simulateCredit,
    simulateTenorTable,
} from '@/utils/creditMath';

const props = defineProps({
    initialPrice: { type: Number, default: 0 },
    vehicleName: { type: String, default: '' },
    vehicleMeta: { type: String, default: '' },
    vehicleUrl: { type: String, default: null },
    vehicleId: { type: Number, default: null },
    coverImageUrl: { type: String, default: null },
    showPicker: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
    defaults: {
        type: Object,
        default: () => ({
            dp_percent: 20,
            tenor: 36,
            rate: 5.5,
            method: 'flat',
            tenor_options: [12, 24, 36, 48, 60],
        }),
    },
});

const emit = defineEmits(['vehicle-change']);

const { t, formatNumber } = useI18n();

const price = ref(Number(props.initialPrice) || 0);
const dpPercent = ref(Number(props.defaults?.dp_percent) || 20);
const downPayment = ref(dpFromPercent(price.value, dpPercent.value));
const tenor = ref(Number(props.defaults?.tenor) || 36);
const rate = ref(Number(props.defaults?.rate) || 5.5);
const method = ref(props.defaults?.method === 'annuity' ? 'annuity' : 'flat');
const syncingDp = ref(false);

const tenorOptions = computed(() => {
    const opts = props.defaults?.tenor_options || [12, 24, 36, 48, 60];
    return Array.isArray(opts) && opts.length ? opts : [12, 24, 36, 48, 60];
});

const selectedVehicle = ref(
    props.vehicleId
        ? {
              id: props.vehicleId,
              name: props.vehicleName,
              meta: props.vehicleMeta,
              url: props.vehicleUrl,
              cover_image_url: props.coverImageUrl,
              price_from: props.initialPrice,
          }
        : null,
);

const query = ref('');
const results = ref([]);
const searching = ref(false);
const pickerOpen = ref(false);
let searchTimer = null;

watch(
    () => props.initialPrice,
    (value) => {
        const next = Number(value) || 0;
        if (next !== price.value) {
            price.value = next;
            syncingDp.value = true;
            downPayment.value = dpFromPercent(next, dpPercent.value);
            syncingDp.value = false;
        }
    },
);

watch(
    () => [props.vehicleId, props.vehicleName, props.vehicleMeta, props.coverImageUrl, props.vehicleUrl],
    () => {
        if (props.vehicleId) {
            selectedVehicle.value = {
                id: props.vehicleId,
                name: props.vehicleName,
                meta: props.vehicleMeta,
                url: props.vehicleUrl,
                cover_image_url: props.coverImageUrl,
                price_from: props.initialPrice,
            };
        }
    },
);

watch(price, (value) => {
    if (syncingDp.value) return;
    syncingDp.value = true;
    downPayment.value = dpFromPercent(value, dpPercent.value);
    syncingDp.value = false;
});

watch(dpPercent, (value) => {
    if (syncingDp.value) return;
    syncingDp.value = true;
    downPayment.value = dpFromPercent(price.value, value);
    syncingDp.value = false;
});

watch(downPayment, (value) => {
    if (syncingDp.value) return;
    syncingDp.value = true;
    dpPercent.value = percentFromDp(price.value, value);
    syncingDp.value = false;
});

const result = computed(() =>
    simulateCredit({
        price: price.value,
        downPayment: downPayment.value,
        tenor: tenor.value,
        annualRate: rate.value,
        method: method.value,
    }),
);

const tenorTable = computed(() =>
    simulateTenorTable({
        price: price.value,
        downPayment: downPayment.value,
        annualRate: rate.value,
        method: method.value,
        tenors: tenorOptions.value,
    }),
);

const canCalculate = computed(() => price.value > 0 && downPayment.value < price.value);
const errorMessage = computed(() => {
    if (price.value <= 0) return t('credit_err_price');
    if (downPayment.value >= price.value) return t('credit_err_dp');
    if (tenor.value < 1) return t('credit_err_tenor');
    return null;
});

function formatRp(value) {
    return `Rp ${formatNumber(Math.round(Number(value) || 0))}`;
}

function onPriceInput(event) {
    const raw = String(event.target.value || '').replace(/\D/g, '');
    price.value = raw ? Number(raw) : 0;
}

function onDpAmountInput(event) {
    const raw = String(event.target.value || '').replace(/\D/g, '');
    downPayment.value = raw ? Number(raw) : 0;
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        const value = query.value.trim();
        searching.value = true;
        pickerOpen.value = true;
        try {
            const url = route('vehicles.compare.search', { q: value || undefined });
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

function pickVehicle(item) {
    selectedVehicle.value = {
        id: item.id,
        name: item.name,
        meta: [item.brand?.name, item.body_type, item.model_year].filter(Boolean).join(' · '),
        url: item.url,
        cover_image_url: item.cover_image_url,
        price_from: item.price_from,
    };
    price.value = Number(item.price_from) || 0;
    syncingDp.value = true;
    downPayment.value = dpFromPercent(price.value, dpPercent.value);
    syncingDp.value = false;
    query.value = '';
    results.value = [];
    pickerOpen.value = false;
    emit('vehicle-change', item);

    if (props.showPicker) {
        router.get(
            route('credit.simulate'),
            { vehicle: item.id },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }
}

function clearVehicle() {
    selectedVehicle.value = null;
    emit('vehicle-change', null);
    if (props.showPicker) {
        router.get(route('credit.simulate'), {}, { preserveState: true, preserveScroll: true, replace: true });
    }
}

function setTenor(months) {
    tenor.value = months;
}

function setMethod(next) {
    method.value = next;
}
</script>

<template>
    <div :class="compact ? 'space-y-5' : 'grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:gap-8'">
        <!-- Inputs -->
        <div class="space-y-5">
            <div
                v-if="showPicker"
                class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft"
            >
                <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                    {{ t('credit_pick_vehicle') }}
                </label>

                <div
                    v-if="selectedVehicle"
                    class="mt-3 flex items-center gap-3 rounded-xl border border-[var(--line)] bg-mist/60 p-3"
                >
                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded-lg bg-neutral-200">
                        <img
                            v-if="selectedVehicle.cover_image_url"
                            :src="selectedVehicle.cover_image_url"
                            :alt="selectedVehicle.name"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-charcoal">{{ selectedVehicle.name }}</p>
                        <p class="truncate text-xs text-neutral-500">{{ selectedVehicle.meta }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 text-[11px] font-semibold uppercase tracking-[0.12em] text-neutral-400 hover:text-brand"
                        @click="clearVehicle"
                    >
                        {{ t('credit_clear') }}
                    </button>
                </div>

                <div class="relative mt-3">
                    <input
                        v-model="query"
                        type="search"
                        class="w-full rounded-xl border border-[var(--line)] bg-white px-4 py-3 text-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15"
                        :placeholder="t('credit_search_ph')"
                        @input="onSearchInput"
                        @focus="pickerOpen = true"
                    />
                    <div
                        v-if="pickerOpen && (searching || results.length || query)"
                        class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-[var(--line)] bg-white shadow-lift"
                    >
                        <p v-if="searching" class="px-4 py-3 text-sm text-neutral-400">{{ t('credit_searching') }}</p>
                        <button
                            v-for="item in results"
                            :key="item.id"
                            type="button"
                            class="flex w-full items-center gap-3 border-b border-[var(--line)] px-3 py-2.5 text-left last:border-0 hover:bg-mist/80"
                            @click="pickVehicle(item)"
                        >
                            <div class="h-11 w-16 shrink-0 overflow-hidden rounded-lg bg-neutral-100">
                                <img
                                    v-if="item.cover_image_url"
                                    :src="item.cover_image_url"
                                    :alt="item.name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ item.name }}</p>
                                <p class="truncate text-xs text-neutral-500">
                                    {{ [item.brand?.name, item.price_label].filter(Boolean).join(' · ') }}
                                </p>
                            </div>
                        </button>
                        <p
                            v-if="!searching && query && !results.length"
                            class="px-4 py-3 text-sm text-neutral-400"
                        >
                            {{ t('credit_search_empty') }}
                        </p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-neutral-400">{{ t('credit_or_manual') }}</p>
            </div>

            <div
                class="space-y-5"
                :class="compact ? '' : 'rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft'"
            >
                <div v-if="!showPicker && vehicleName && !compact" class="mb-4 border-b border-[var(--line)] pb-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                        {{ t('credit_for_vehicle') }}
                    </p>
                    <p class="mt-1 font-semibold text-charcoal">{{ vehicleName }}</p>
                    <p v-if="vehicleMeta" class="text-xs text-neutral-500">{{ vehicleMeta }}</p>
                </div>

                <div>
                <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                    {{ t('credit_price') }}
                </label>
                <div class="mt-2 flex items-center gap-2 rounded-xl border border-[var(--line)] bg-mist/40 px-4 py-3 focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/15">
                    <span class="text-sm font-semibold text-neutral-400">Rp</span>
                    <input
                        :value="price ? formatNumber(price) : ''"
                        type="text"
                        inputmode="numeric"
                        class="w-full bg-transparent text-lg font-semibold tracking-[-0.02em] text-charcoal outline-none"
                        :placeholder="t('credit_price_ph')"
                        @input="onPriceInput"
                    />
                </div>
                </div>

                <div>
                    <div class="flex items-end justify-between gap-3">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                            {{ t('credit_dp') }}
                        </label>
                        <span class="font-display text-xl tracking-[-0.03em] text-brand">{{ dpPercent }}%</span>
                    </div>
                    <input
                        v-model.number="dpPercent"
                        type="range"
                        min="0"
                        max="90"
                        step="1"
                        class="credit-range mt-3 w-full"
                    />
                    <div class="mt-3 flex items-center gap-2 rounded-xl border border-[var(--line)] px-4 py-2.5 focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/15">
                        <span class="text-xs font-semibold text-neutral-400">Rp</span>
                        <input
                            :value="downPayment ? formatNumber(downPayment) : ''"
                            type="text"
                            inputmode="numeric"
                            class="w-full bg-transparent text-sm font-semibold outline-none"
                            @input="onDpAmountInput"
                        />
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                        {{ t('credit_tenor') }}
                    </label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="months in tenorOptions"
                            :key="months"
                            type="button"
                            class="rounded-full border px-3.5 py-2 text-[11px] font-semibold uppercase tracking-[0.12em] transition"
                            :class="tenor === months
                                ? 'border-brand bg-brand text-white shadow-glow'
                                : 'border-[var(--line)] bg-white text-charcoal/70 hover:border-charcoal/25'"
                            @click="setTenor(months)"
                        >
                            {{ months }} {{ t('credit_months') }}
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                            {{ t('credit_rate') }}
                        </label>
                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-[var(--line)] px-4 py-2.5 focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/15">
                            <input
                                v-model.number="rate"
                                type="number"
                                min="0"
                                max="40"
                                step="0.1"
                                class="w-full bg-transparent text-sm font-semibold outline-none"
                            />
                            <span class="text-xs font-semibold text-neutral-400">%/thn</span>
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                            {{ t('credit_method') }}
                        </label>
                        <div class="mt-2 grid grid-cols-2 gap-1 rounded-xl border border-[var(--line)] bg-mist/50 p-1">
                            <button
                                type="button"
                                class="rounded-lg px-2 py-2 text-[11px] font-semibold uppercase tracking-[0.1em] transition"
                                :class="method === 'flat' ? 'bg-white text-charcoal shadow-soft' : 'text-neutral-500 hover:text-charcoal'"
                                @click="setMethod('flat')"
                            >
                                {{ t('credit_method_flat') }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-2 py-2 text-[11px] font-semibold uppercase tracking-[0.1em] transition"
                                :class="method === 'annuity' ? 'bg-white text-charcoal shadow-soft' : 'text-neutral-500 hover:text-charcoal'"
                                @click="setMethod('annuity')"
                            >
                                {{ t('credit_method_annuity') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div :class="compact ? '' : 'lg:sticky lg:top-28 lg:self-start'">
            <div class="overflow-hidden rounded-2xl border border-[var(--line)] bg-[#0a0b0d] text-white shadow-lift">
                <div class="relative p-5 sm:p-6">
                    <div
                        class="pointer-events-none absolute inset-0 opacity-80"
                        style="background: radial-gradient(ellipse 70% 60% at 100% 0%, rgba(255,30,45,0.28), transparent 55%);"
                    />
                    <div class="relative">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/45">
                            {{ t('credit_result_label') }}
                        </p>
                        <p class="mt-3 text-sm text-white/55">{{ t('credit_monthly') }}</p>
                        <p
                            class="font-display mt-1 text-4xl tracking-[-0.04em] transition-all duration-300 sm:text-5xl"
                            :class="canCalculate ? 'text-white' : 'text-white/35'"
                        >
                            {{ canCalculate ? formatRp(result.monthly) : '—' }}
                        </p>
                        <p class="mt-2 text-xs text-white/40">
                            {{ t('credit_for_tenor', { months: String(tenor) }) }}
                            · {{ method === 'annuity' ? t('credit_method_annuity') : t('credit_method_flat') }}
                        </p>

                        <p v-if="errorMessage" class="mt-4 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-amber-200/90">
                            {{ errorMessage }}
                        </p>

                        <dl v-else class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-white/10 bg-white/5 px-3.5 py-3">
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/40">{{ t('credit_dp') }}</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums">{{ formatRp(result.downPayment) }}</dd>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 px-3.5 py-3">
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/40">{{ t('credit_principal') }}</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums">{{ formatRp(result.principal) }}</dd>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 px-3.5 py-3">
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/40">{{ t('credit_interest_total') }}</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums">{{ formatRp(result.totalInterest) }}</dd>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 px-3.5 py-3">
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/40">{{ t('credit_total') }}</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums">{{ formatRp(result.totalWithDp) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div v-if="!compact" class="border-t border-white/10 bg-black/40 px-5 py-4 sm:px-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/40">
                        {{ t('credit_tenor_table') }}
                    </p>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[18rem] text-left text-sm">
                            <thead>
                                <tr class="text-[10px] uppercase tracking-[0.14em] text-white/35">
                                    <th class="pb-2 font-semibold">{{ t('credit_tenor') }}</th>
                                    <th class="pb-2 text-right font-semibold">{{ t('credit_monthly') }}</th>
                                    <th class="pb-2 text-right font-semibold">{{ t('credit_interest_total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in tenorTable"
                                    :key="row.tenor"
                                    class="border-t border-white/8 transition"
                                    :class="row.tenor === tenor ? 'bg-brand/15' : ''"
                                >
                                    <td class="py-2.5 font-semibold">
                                        <button type="button" class="hover:text-brand" @click="setTenor(row.tenor)">
                                            {{ row.tenor }} {{ t('credit_months') }}
                                        </button>
                                    </td>
                                    <td class="py-2.5 text-right tabular-nums font-semibold">
                                        {{ canCalculate ? formatRp(row.monthly) : '—' }}
                                    </td>
                                    <td class="py-2.5 text-right tabular-nums text-white/55">
                                        {{ canCalculate ? formatRp(row.totalInterest) : '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <p class="mt-3 text-xs leading-relaxed text-neutral-500">
                {{ t('credit_disclaimer') }}
            </p>

            <a
                v-if="compact && vehicleId"
                :href="route('credit.simulate', { vehicle: vehicleId })"
                class="mt-4 inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-brand hover:underline"
            >
                {{ t('credit_open_full') }}
            </a>
        </div>
    </div>
</template>

<style scoped>
.credit-range {
    appearance: none;
    height: 0.35rem;
    border-radius: 999px;
    background: linear-gradient(90deg, #ff1e2d, rgba(10, 11, 13, 0.12));
    outline: none;
}
.credit-range::-webkit-slider-thumb {
    appearance: none;
    width: 1.15rem;
    height: 1.15rem;
    border-radius: 999px;
    background: #fff;
    border: 2px solid #ff1e2d;
    box-shadow: 0 4px 12px rgba(10, 11, 13, 0.18);
    cursor: pointer;
}
.credit-range::-moz-range-thumb {
    width: 1.15rem;
    height: 1.15rem;
    border-radius: 999px;
    background: #fff;
    border: 2px solid #ff1e2d;
    cursor: pointer;
}
</style>
