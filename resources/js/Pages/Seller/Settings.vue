<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import axios from 'axios';

const props = defineProps({
    store: { type: Object, required: true },
    provinces: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    courierOptions: { type: Array, default: () => [] },
    rajaongkir_error: { type: String, default: null },
    rajaongkir_configured: { type: Boolean, default: false },
});

const cityOptions = ref([...(props.cities || [])]);
const logoPreview = ref(props.store.logo_url || null);

const form = useForm({
    name: props.store.name || '',
    tagline: props.store.tagline || '',
    description: props.store.description || '',
    contact_phone: props.store.contact_phone || '',
    pickup_address: props.store.pickup_address || '',
    origin_province_id: props.store.origin_province_id || '',
    origin_province_name: props.store.origin_province_name || '',
    origin_city_id: props.store.origin_city_id || '',
    origin_city_name: props.store.origin_city_name || '',
    couriers: [...(props.store.couriers || [])],
    logo: null,
    remove_logo: false,
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
    const { data } = await axios.get(route('seller.settings.cities'), { params: { province_id: id } });
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

function onLogo(event) {
    const file = event.target.files?.[0];
    form.logo = file || null;
    form.remove_logo = false;
    logoPreview.value = file ? URL.createObjectURL(file) : props.store.logo_url || null;
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('seller.settings.update'), { forceFormData: true });
}
</script>

<template>
    <SellerLayout title="Pengaturan Toko">
        <Head title="Pengaturan Toko" />

        <p class="mb-4 text-sm text-neutral-500">Status: <strong>{{ store.status_label }}</strong>. Status hanya bisa diubah admin Autoluz.</p>
        <p v-if="rajaongkir_error" class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ rajaongkir_error }}</p>

        <form class="max-w-2xl space-y-4 rounded-2xl border border-black/5 bg-white p-5 shadow-sm" @submit.prevent="submit">
            <input v-model="form.name" type="text" required placeholder="Nama toko" class="w-full rounded-xl border-black/10" />
            <input v-model="form.tagline" type="text" placeholder="Tagline" class="w-full rounded-xl border-black/10" />
            <textarea v-model="form.description" rows="3" placeholder="Deskripsi toko" class="w-full rounded-xl border-black/10" />
            <input v-model="form.contact_phone" type="text" placeholder="Telepon" class="w-full rounded-xl border-black/10" />
            <textarea v-model="form.pickup_address" rows="3" placeholder="Alamat pickup" class="w-full rounded-xl border-black/10" />

            <div v-if="logoPreview" class="overflow-hidden rounded-xl border border-black/10">
                <img :src="logoPreview" alt="" class="h-24 w-24 object-cover" />
            </div>
            <input type="file" accept="image/*" class="w-full text-sm" @change="onLogo" />
            <label v-if="logoPreview" class="flex items-center gap-2 text-sm">
                <input v-model="form.remove_logo" type="checkbox" class="rounded text-brand" />
                Hapus logo
            </label>

            <div class="grid gap-3 sm:grid-cols-2">
                <select v-model="form.origin_province_id" class="rounded-xl border-black/10">
                    <option value="">Provinsi asal</option>
                    <option v-for="row in provinces" :key="row.id" :value="row.id">{{ row.name }}</option>
                </select>
                <select v-model="form.origin_city_id" class="rounded-xl border-black/10">
                    <option value="">Kota asal</option>
                    <option v-for="row in cityOptions" :key="row.id" :value="row.id">{{ row.name }}</option>
                </select>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="item in courierOptions"
                    :key="item.value"
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em]"
                    :class="form.couriers.includes(item.value) ? 'border-brand bg-brand text-white' : 'border-black/10'"
                    @click="toggleCourier(item.value)"
                >
                    {{ item.label }}
                </button>
            </div>

            <button type="submit" class="rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white" :disabled="form.processing">
                Simpan
            </button>
        </form>
    </SellerLayout>
</template>
