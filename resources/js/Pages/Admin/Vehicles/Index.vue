<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';

const props = defineProps({
    vehicles: { type: Object, required: true },
    brands: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ q: '', brand_id: '' }) },
});

const q = ref(props.filters.q || '');
const brandId = ref(props.filters.brand_id || '');

watch([q, brandId], () => {
    router.get(route('admin.vehicles.index'), {
        q: q.value || undefined,
        brand_id: brandId.value || undefined,
    }, { preserveState: true, replace: true });
});

async function destroy(id) {
    const ok = await swalConfirm('Hapus kendaraan ini?', { title: 'Hapus Kendaraan', confirmButtonText: 'Hapus', icon: 'warning' });
    if (!ok) return;
    router.delete(route('admin.vehicles.destroy', id));
}
</script>

<template>
    <AdminLayout title="Kendaraan">
        <Head title="Admin Kendaraan" />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <input v-model="q" type="search" placeholder="Cari kendaraan..." class="rounded-xl border-black/10 text-sm" />
                <select v-model="brandId" class="rounded-xl border-black/10 text-sm">
                    <option value="">Semua merek</option>
                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                </select>
            </div>
            <Link :href="route('admin.vehicles.create')" class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white">
                Tambah Kendaraan
            </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Kendaraan</th>
                        <th class="px-4 py-3">Merek</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="vehicle in vehicles.data" :key="vehicle.id" class="border-t border-black/5">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-16 overflow-hidden rounded-lg bg-mist">
                                    <img v-if="vehicle.cover_image_url" :src="vehicle.cover_image_url" :alt="vehicle.name" class="h-full w-full object-cover" />
                                </div>
                                <div>
                                    <p class="font-semibold">{{ vehicle.name }}</p>
                                    <p class="text-xs text-neutral-400">{{ vehicle.model_year || '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ vehicle.brand?.name || '—' }}</td>
                        <td class="px-4 py-3 text-neutral-600">{{ vehicle.body_type || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase" :class="vehicle.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                {{ vehicle.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <Link :href="route('admin.vehicles.edit', vehicle.id)" class="mr-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand">Edit</Link>
                            <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600" @click="destroy(vehicle.id)">Hapus</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <PaginationBar :links="vehicles.links" :from="vehicles.from" :to="vehicles.to" :total="vehicles.total" />
        </div>
    </AdminLayout>
</template>
