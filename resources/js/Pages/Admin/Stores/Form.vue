<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    store: { type: Object, default: null },
    users: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    districts: { type: Array, default: () => [] },
    courierOptions: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    rajaongkir_error: { type: String, default: null },
});

const isEdit = computed(() => !!props.store?.id);
const slugManual = ref(!!props.store?.slug);
const cityOptions = ref([...(props.cities || [])]);
const districtOptions = ref([...(props.districts || [])]);
const logoPreview = ref(props.store?.logo_url || null);

const form = useForm({
    user_id: props.store?.user_id || '',
    name: props.store?.name || '',
    slug: props.store?.slug || '',
    tagline: props.store?.tagline || '',
    description: props.store?.description || '',
    contact_phone: props.store?.contact_phone || '',
    pickup_address: props.store?.pickup_address || '',
    origin_province_id: props.store?.origin_province_id || '',
    origin_province_name: props.store?.origin_province_name || '',
    origin_city_id: props.store?.origin_city_id || '',
    origin_city_name: props.store?.origin_city_name || '',
    origin_district_id: props.store?.origin_district_id || '',
    origin_district_name: props.store?.origin_district_name || '',
    couriers: [...(props.store?.couriers || [])],
    status: props.store?.status || 'pending',
    is_official: !!props.store?.is_official,
    logo: null,
    remove_logo: false,
});

watch(() => form.name, (value) => {
    if (!slugManual.value) form.slug = slugify(value);
});

watch(() => form.origin_province_id, async (id) => {
    const province = props.provinces.find((row) => String(row.id) === String(id));
    form.origin_province_name = province?.name || '';
    form.origin_city_id = '';
    form.origin_city_name = '';
    form.origin_district_id = '';
    form.origin_district_name = '';
    districtOptions.value = [];
    if (!id) {
        cityOptions.value = [];
        return;
    }
    const { data } = await axios.get(route('admin.shop-settings.cities'), { params: { province_id: id } });
    cityOptions.value = data.data || [];
});

watch(() => form.origin_city_id, async (id) => {
    const city = cityOptions.value.find((row) => String(row.id) === String(id));
    form.origin_city_name = city?.name || form.origin_city_name;
    form.origin_district_id = '';
    form.origin_district_name = '';
    if (!id) {
        districtOptions.value = [];
        return;
    }
    const { data } = await axios.get(route('admin.shop-settings.districts'), { params: { city_id: id } });
    districtOptions.value = data.data || [];
});

watch(() => form.origin_district_id, (id) => {
    const district = districtOptions.value.find((row) => String(row.id) === String(id));
    form.origin_district_name = district?.name || form.origin_district_name;
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
    logoPreview.value = file ? URL.createObjectURL(file) : props.store?.logo_url || null;
}

function submit() {
    const payload = (data) => ({
        ...data,
        user_id: data.user_id || null,
        is_official: data.is_official ? 1 : 0,
    });

    if (isEdit.value) {
        form.transform((data) => ({ ...payload(data), _method: 'put' })).post(route('admin.stores.update', props.store.id), { forceFormData: true });
        return;
    }

    form.transform(payload).post(route('admin.stores.store'), { forceFormData: true });
}
</script>

<template>
    <AdminLayout :title="isEdit ? 'Edit Toko Partner' : 'Toko Partner Baru'">
        <Head :title="isEdit ? 'Edit Toko' : 'Toko Baru'" />

        <p v-if="rajaongkir_error" class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ rajaongkir_error }}</p>

        <form class="max-w-2xl space-y-4 rounded-2xl border border-black/5 bg-white p-5 shadow-sm" @submit.prevent="submit">
            <input v-model="form.name" type="text" required placeholder="Nama toko" class="w-full rounded-xl border-black/10" />
            <input v-model="form.slug" type="text" class="w-full rounded-xl border-black/10" @input="slugManual = true" />
            <select v-model="form.user_id" class="w-full rounded-xl border-black/10">
                <option value="">— Owner (user) —</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
            </select>
            <input v-model="form.tagline" type="text" placeholder="Tagline" class="w-full rounded-xl border-black/10" />
            <textarea v-model="form.description" rows="3" placeholder="Deskripsi" class="w-full rounded-xl border-black/10" />
            <input v-model="form.contact_phone" type="text" placeholder="Telepon" class="w-full rounded-xl border-black/10" />
            <textarea v-model="form.pickup_address" rows="3" placeholder="Alamat pickup" class="w-full rounded-xl border-black/10" />

            <div class="grid gap-3 sm:grid-cols-3">
                <select v-model="form.origin_province_id" class="rounded-xl border-black/10">
                    <option value="">Provinsi asal</option>
                    <option v-for="row in provinces" :key="row.id" :value="row.id">{{ row.name }}</option>
                </select>
                <select v-model="form.origin_city_id" class="rounded-xl border-black/10">
                    <option value="">Kota / kabupaten</option>
                    <option v-for="row in cityOptions" :key="row.id" :value="row.id">{{ row.name }}</option>
                </select>
                <select v-model="form.origin_district_id" class="rounded-xl border-black/10">
                    <option value="">Kecamatan</option>
                    <option v-for="row in districtOptions" :key="row.id" :value="row.id">{{ row.name }}</option>
                </select>
            </div>

            <select v-model="form.status" class="w-full rounded-xl border-black/10">
                <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.is_official" type="checkbox" class="rounded text-brand" :disabled="store?.is_official" />
                Toko resmi Autoluz
            </label>

            <div v-if="logoPreview" class="overflow-hidden rounded-xl border border-black/10">
                <img :src="logoPreview" alt="" class="h-24 w-24 object-cover" />
            </div>
            <input type="file" accept="image/*" class="w-full text-sm" @change="onLogo" />

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

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white" :disabled="form.processing">Simpan</button>
                <Link :href="route('admin.stores.index')" class="rounded-full border border-black/10 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em]">Kembali</Link>
            </div>
        </form>
    </AdminLayout>
</template>
