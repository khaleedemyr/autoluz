<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { useI18n } from '@/composables/useI18n';
import { swalError } from '@/utils/swal';

const props = defineProps({
    cart: { type: Object, required: true },
    addresses: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
    shipping_error: { type: String, default: null },
    midtrans: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const cities = ref([]);
const districts = ref([]);
const quoting = ref(false);
const useNew = ref(!props.addresses.length);
const quotesByStore = reactive({});
const selectedByStore = reactive({});

const form = useForm({
    address_id: props.addresses.find((row) => row.is_default)?.id || props.addresses[0]?.id || '',
    recipient_name: '',
    phone: '',
    address: '',
    province_id: '',
    province_name: '',
    city_id: '',
    city_name: '',
    district_id: '',
    district_name: '',
    postal_code: '',
    save_address: true,
    shippings: [],
    notes: '',
});

const selectedAddress = computed(() => props.addresses.find((row) => Number(row.id) === Number(form.address_id)));
const destCityId = computed(() => (useNew.value ? (form.district_id || form.city_id) : (selectedAddress.value?.district_id || selectedAddress.value?.city_id)));
const groups = computed(() => props.cart.groups || []);
const shippingTotal = computed(() => Object.values(selectedByStore).reduce((sum, row) => sum + Number(row?.cost || 0), 0));
const grand = computed(() => Number(props.cart.subtotal || 0) + shippingTotal.value);
const grandLabel = computed(() => 'Rp ' + Number(grand.value).toLocaleString('id-ID'));
const allQuoted = computed(() => groups.value.every((group) => selectedByStore[group.id]));
const originBlocked = computed(() => groups.value.some((group) => !group.origin_ready));

watch(() => form.province_id, async (id) => {
    const province = props.provinces.find((row) => String(row.id) === String(id));
    form.province_name = province?.name || '';
    form.city_id = '';
    form.city_name = '';
    form.district_id = '';
    form.district_name = '';
    districts.value = [];
    resetQuotes();
    if (!id) {
        cities.value = [];
        return;
    }
    const { data } = await axios.get(route('shop.checkout.cities'), { params: { province_id: id } });
    cities.value = data.data || [];
});

watch(() => form.city_id, async (id) => {
    const city = cities.value.find((row) => String(row.id) === String(id));
    form.city_name = city?.name || '';
    form.postal_code = city?.postal_code || form.postal_code;
    form.district_id = '';
    form.district_name = '';
    resetQuotes();
    if (!id) {
        districts.value = [];
        return;
    }
    const { data } = await axios.get(route('shop.checkout.districts'), { params: { city_id: id } });
    districts.value = data.data || [];
});

watch(() => form.district_id, (id) => {
    const district = districts.value.find((row) => String(row.id) === String(id));
    form.district_name = district?.name || '';
    if (district?.postal_code) {
        form.postal_code = district.postal_code;
    }
});

watch([destCityId, useNew, () => form.address_id], () => {
    resetQuotes();
});

function resetQuotes() {
    Object.keys(quotesByStore).forEach((key) => delete quotesByStore[key]);
    Object.keys(selectedByStore).forEach((key) => delete selectedByStore[key]);
}

async function loadQuotes() {
    if (!destCityId.value) {
        swalError(t('shop_need_city'));
        return;
    }
    quoting.value = true;
    try {
        const { data } = await axios.post(route('shop.checkout.quote'), {
            city_id: useNew.value ? form.city_id : selectedAddress.value?.city_id,
            district_id: useNew.value ? form.district_id : selectedAddress.value?.district_id,
        });
        (data.data || []).forEach((row) => {
            quotesByStore[row.store_id] = row;
            delete selectedByStore[row.store_id];
        });
        if (!(data.data || []).some((row) => row.options?.length)) {
            swalError(t('shop_no_shipping'));
        }
    } catch (e) {
        swalError(e.response?.data?.message || t('shop_no_shipping'));
    } finally {
        quoting.value = false;
    }
}

function pickQuote(storeId, row) {
    selectedByStore[storeId] = row;
}

function submit() {
    const shippings = groups.value.map((group) => {
        const picked = selectedByStore[group.id];
        return {
            store_id: group.id,
            courier: picked?.courier,
            service: picked?.service,
            description: picked?.description,
            cost: picked?.cost || 0,
            etd: picked?.etd,
        };
    });
    const payload = useNew.value ? { ...form.data(), address_id: null, shippings } : { ...form.data(), shippings };
    form.transform(() => payload).post(route('shop.checkout.store'));
}
</script>

<template>
    <AppLayout>
        <Head :title="t('shop_checkout')" />

        <section class="container-editorial py-10 lg:py-14">
            <p class="section-label">{{ t('shop_label') }}</p>
            <h1 class="font-display mt-3 text-5xl tracking-[-0.04em]">{{ t('shop_checkout') }}</h1>
            <p v-if="shipping_error" class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ shipping_error }}</p>
            <p v-if="!midtrans.configured" class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ t('shop_midtrans_missing') }}</p>
            <p v-if="originBlocked" class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ t('shop_store_origin_missing') }}</p>
            <p v-if="form.errors.payment" class="mt-4 text-sm text-red-600">{{ form.errors.payment }}</p>
            <p v-if="form.errors.shipping" class="mt-4 text-sm text-red-600">{{ form.errors.shipping }}</p>
            <p v-if="form.errors.cart" class="mt-4 text-sm text-red-600">{{ form.errors.cart }}</p>

            <form class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]" @submit.prevent="submit">
                <div class="space-y-6">
                    <div class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                        <h2 class="font-semibold">{{ t('shop_address') }}</h2>
                        <div v-if="addresses.length" class="mt-4 space-y-2">
                            <label v-for="row in addresses" :key="row.id" class="flex cursor-pointer gap-3 rounded-xl border p-3" :class="!useNew && Number(form.address_id) === Number(row.id) ? 'border-brand' : 'border-[var(--line)]'">
                                <input v-model="form.address_id" type="radio" :value="row.id" class="mt-1 text-brand" @change="useNew = false" />
                                <span>
                                    <strong>{{ row.recipient_name }}</strong>
                                    <span class="block text-sm text-neutral-500">{{ row.summary }}</span>
                                </span>
                            </label>
                            <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-brand" @click="useNew = true">
                                {{ t('shop_new_address') }}
                            </button>
                        </div>

                        <div v-if="useNew || !addresses.length" class="mt-4 grid gap-3">
                            <input v-model="form.recipient_name" type="text" :placeholder="t('shop_recipient')" class="rounded-xl border-black/10" :required="useNew" />
                            <input v-model="form.phone" type="text" :placeholder="t('shop_phone')" class="rounded-xl border-black/10" :required="useNew" />
                            <textarea v-model="form.address" rows="3" :placeholder="t('shop_street')" class="rounded-xl border-black/10" :required="useNew" />
                            <div class="grid gap-3 sm:grid-cols-3">
                                <select v-model="form.province_id" class="rounded-xl border-black/10" :required="useNew">
                                    <option value="">{{ t('shop_province') }}</option>
                                    <option v-for="row in provinces" :key="row.id" :value="row.id">{{ row.name }}</option>
                                </select>
                                <select v-model="form.city_id" class="rounded-xl border-black/10" :required="useNew">
                                    <option value="">{{ t('shop_city') }}</option>
                                    <option v-for="row in cities" :key="row.id" :value="row.id">{{ row.name }}</option>
                                </select>
                                <select v-model="form.district_id" class="rounded-xl border-black/10" :required="useNew && districts.length > 0">
                                    <option value="">{{ t('shop_district') }}</option>
                                    <option v-for="row in districts" :key="row.id" :value="row.id">{{ row.name }}</option>
                                </select>
                            </div>
                            <input v-model="form.postal_code" type="text" :placeholder="t('shop_postal')" class="rounded-xl border-black/10" />
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.save_address" type="checkbox" class="rounded text-brand" />
                                {{ t('shop_save_address') }}
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-semibold">{{ t('shop_shipping') }}</h2>
                            <button type="button" class="rounded-full border border-charcoal px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.12em]" :disabled="quoting || originBlocked" @click="loadQuotes">
                                {{ quoting ? t('shop_calculating') : t('shop_calc_shipping') }}
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-neutral-500">{{ t('shop_split_hint') }}</p>

                        <div v-for="group in groups" :key="group.id" class="mt-5 border-t border-[var(--line)] pt-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold">{{ group.store?.name }}</p>
                                <p class="text-xs text-neutral-500">{{ group.subtotal_label }}</p>
                            </div>
                            <p v-if="!group.origin_ready" class="mt-2 text-xs text-amber-700">{{ t('shop_store_origin_missing') }}</p>
                            <p v-else-if="quotesByStore[group.id]?.error" class="mt-2 text-xs text-red-600">{{ quotesByStore[group.id].error }}</p>
                            <div v-if="quotesByStore[group.id]?.options?.length" class="mt-3 space-y-2">
                                <label
                                    v-for="row in quotesByStore[group.id].options"
                                    :key="row.courier + row.service"
                                    class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-3"
                                    :class="selectedByStore[group.id]?.service === row.service && selectedByStore[group.id]?.courier === row.courier ? 'border-brand' : 'border-[var(--line)]'"
                                >
                                    <span class="flex items-center gap-3">
                                        <input type="radio" class="text-brand" :checked="selectedByStore[group.id]?.service === row.service && selectedByStore[group.id]?.courier === row.courier" @change="pickQuote(group.id, row)" />
                                        <span>
                                            <strong class="uppercase">{{ row.courier }} {{ row.service }}</strong>
                                            <span class="block text-xs text-neutral-500">{{ row.description }} · {{ row.etd }} hari</span>
                                        </span>
                                    </span>
                                    <span class="font-semibold">Rp {{ Number(row.cost).toLocaleString('id-ID') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="h-fit rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                    <h2 class="font-semibold">{{ t('shop_summary') }}</h2>
                    <div v-for="group in groups" :key="group.id" class="mt-4 border-t border-[var(--line)] pt-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-neutral-400">{{ group.store?.name }}</p>
                        <div v-for="item in group.items" :key="item.id" class="mt-2 flex justify-between gap-3 text-sm">
                            <span>{{ item.name }} ×{{ item.qty }}</span>
                            <span>{{ item.line_total_label }}</span>
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-neutral-500">
                            <span>{{ t('shop_shipping') }}</span>
                            <span>Rp {{ Number(selectedByStore[group.id]?.cost || 0).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-between text-sm">
                        <span>{{ t('shop_subtotal') }}</span>
                        <span>{{ cart.subtotal_label }}</span>
                    </div>
                    <div class="mt-2 flex justify-between text-sm">
                        <span>{{ t('shop_shipping') }}</span>
                        <span>Rp {{ Number(shippingTotal).toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="mt-4 flex justify-between font-display text-2xl">
                        <span>{{ t('shop_total') }}</span>
                        <span>{{ grandLabel }}</span>
                    </div>
                    <textarea v-model="form.notes" rows="2" :placeholder="t('shop_notes')" class="mt-4 w-full rounded-xl border-black/10 text-sm" />
                    <button type="submit" class="mt-5 w-full rounded-full bg-brand px-5 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-white disabled:opacity-50" :disabled="form.processing || !allQuoted || !midtrans.configured || originBlocked">
                        {{ t('shop_pay') }}
                    </button>
                    <Link :href="route('shop.cart')" class="mt-3 block text-center text-xs uppercase tracking-[0.12em] text-neutral-500">{{ t('shop_back_cart') }}</Link>
                </aside>
            </form>
        </section>
    </AppLayout>
</template>
