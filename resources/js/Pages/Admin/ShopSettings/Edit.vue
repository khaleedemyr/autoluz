<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    settings: { type: Object, required: true },
    stores: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    courierOptions: { type: Array, default: () => [] },
    rajaongkir_error: { type: String, default: null },
    rajaongkir_configured: { type: Boolean, default: false },
    midtrans_configured: { type: Boolean, default: false },
});

const cityOptions = ref([...(props.cities || [])]);

const form = useForm({
    store_name: props.settings.store_name || 'Autoluz Shop',
    contact_phone: props.settings.contact_phone || '',
    pickup_address: props.settings.pickup_address || '',
    origin_province_id: props.settings.origin_province_id || '',
    origin_province_name: props.settings.origin_province_name || '',
    origin_city_id: props.settings.origin_city_id || '',
    origin_city_name: props.settings.origin_city_name || '',
    couriers: [...(props.settings.couriers || [])],
});

watch(() => form.origin_province_id, async (id) => {
    const province = props.provinces.find((row) => String(row.id) === String(id));
    form.origin_province_name = province?.name || '';
    form.origin_city_id = '';
    form.origin_city_name = '';
    if (!id) {
        cityOptions.value = [];
        return;
    }
    const { data } = await axios.get(route('admin.shop-settings.cities'), { params: { province_id: id } });
    cityOptions.value = data.data || [];
});

watch(() => form.origin_city_id, (id) => {
    const city = cityOptions.value.find((row) => String(row.id) === String(id));
    form.origin_city_name = city?.name || form.origin_city_name;
});

function toggleCourier(value) {
    if (form.couriers.includes(value)) {
        form.couriers = form.couriers.filter((item) => item !== value);
        return;
    }
    form.couriers = [...form.couriers, value];
}

function courierLabel(value) {
    return props.courierOptions.find((row) => row.value === value)?.label || String(value).toUpperCase();
}

function submit() {
    form.put(route('admin.shop-settings.update'));
}
</script>

<template>
    <AdminLayout title="Asal Pengiriman">
        <Head title="Asal Pengiriman" />

        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-black/5 bg-white p-4 text-sm">
                RajaOngkir: <strong :class="rajaongkir_configured ? 'text-emerald-700' : 'text-amber-700'">{{ rajaongkir_configured ? 'siap' : 'belum dikonfigurasi' }}</strong>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-4 text-sm">
                Midtrans: <strong :class="midtrans_configured ? 'text-emerald-700' : 'text-amber-700'">{{ midtrans_configured ? 'siap' : 'belum dikonfigurasi' }}</strong>
            </div>
        </div>
        <p v-if="rajaongkir_error" class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ rajaongkir_error }}</p>

        <section class="mb-8">
            <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">Multi toko</p>
                    <h2 class="mt-1 text-lg font-semibold">Kota asal tiap toko</h2>
                    <p class="mt-1 text-sm text-neutral-500">RajaOngkir memakai kota asal toko yang mengirim, bukan satu alamat untuk semua.</p>
                </div>
                <Link :href="route('admin.stores.index')" class="text-xs font-semibold uppercase tracking-[0.14em] text-brand">
                    Kelola toko partner
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                        <tr>
                            <th class="px-4 py-3">Toko</th>
                            <th class="px-4 py-3">Asal</th>
                            <th class="px-4 py-3">Kurir</th>
                            <th class="px-4 py-3">Ongkir</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!stores.length">
                            <td colspan="5" class="px-4 py-6 text-sm text-neutral-500">Belum ada toko.</td>
                        </tr>
                        <tr v-for="store in stores" :key="store.id" class="border-t border-black/5">
                            <td class="px-4 py-3">
                                <p class="font-semibold">{{ store.name }}</p>
                                <p class="text-xs text-neutral-400">
                                    {{ store.slug }}
                                    <span v-if="store.is_official">· Resmi Autoluz</span>
                                    <span v-else-if="store.owner">· {{ store.owner.name }}</span>
                                </p>
                            </td>
                            <td class="px-4 py-3 text-neutral-600">{{ store.origin_city_name || 'Belum diisi' }}</td>
                            <td class="px-4 py-3 text-neutral-600">{{ (store.couriers || []).map(courierLabel).join(', ') || '—' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase"
                                    :class="store.origin_ready ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ store.origin_ready ? 'Siap' : 'Belum' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('admin.stores.edit', store.id)" class="text-xs font-semibold uppercase tracking-[0.12em] text-brand">
                                    Atur alamat
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">Toko resmi</p>
            <h2 class="mt-1 mb-3 text-lg font-semibold">Autoluz Shop</h2>
            <p class="mb-4 text-sm text-neutral-500">Form ini hanya untuk toko resmi Autoluz. Toko partner diatur lewat tombol Atur alamat di atas, atau seller di /seller/settings.</p>

            <form class="max-w-2xl space-y-4 rounded-2xl border border-black/5 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <input v-model="form.store_name" type="text" required placeholder="Nama toko" class="w-full rounded-xl border-black/10" />
                <input v-model="form.contact_phone" type="text" placeholder="Telepon" class="w-full rounded-xl border-black/10" />
                <textarea v-model="form.pickup_address" rows="3" placeholder="Alamat pickup" class="w-full rounded-xl border-black/10" />

                <div class="grid gap-3 sm:grid-cols-2">
                    <select v-model="form.origin_province_id" class="w-full rounded-xl border-black/10">
                        <option value="">— Provinsi asal —</option>
                        <option v-for="row in provinces" :key="row.id" :value="row.id">{{ row.name }}</option>
                    </select>
                    <select v-model="form.origin_city_id" class="w-full rounded-xl border-black/10">
                        <option value="">— Kota asal —</option>
                        <option v-for="row in cityOptions" :key="row.id" :value="row.id">{{ row.name }}</option>
                    </select>
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Kurir aktif</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="opt in courierOptions"
                            :key="opt.value"
                            type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em]"
                            :class="form.couriers.includes(opt.value) ? 'bg-brand text-white' : 'border border-black/10'"
                            @click="toggleCourier(opt.value)"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <button type="submit" class="rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white" :disabled="form.processing">
                    Simpan
                </button>
            </form>
        </section>
    </AdminLayout>
</template>
