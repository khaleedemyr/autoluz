<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    settings: { type: Object, required: true },
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

function submit() {
    form.put(route('admin.shop-settings.update'));
}
</script>

<template>
    <AdminLayout title="Pengaturan Toko">
        <Head title="Pengaturan Toko" />

        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-black/5 bg-white p-4 text-sm">
                RajaOngkir: <strong :class="rajaongkir_configured ? 'text-emerald-700' : 'text-amber-700'">{{ rajaongkir_configured ? 'siap' : 'belum dikonfigurasi' }}</strong>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-4 text-sm">
                Midtrans: <strong :class="midtrans_configured ? 'text-emerald-700' : 'text-amber-700'">{{ midtrans_configured ? 'siap' : 'belum dikonfigurasi' }}</strong>
            </div>
        </div>
        <p v-if="rajaongkir_error" class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ rajaongkir_error }}</p>

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
    </AdminLayout>
</template>
