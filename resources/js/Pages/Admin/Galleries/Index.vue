<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';

const props = defineProps({
    galleries: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '' }) },
});

const q = ref(props.filters.q || '');

watch(q, () => {
    router.get(route('admin.galleries.index'), { q: q.value || undefined }, { preserveState: true, replace: true });
});

async function destroy(id) {
    const ok = await swalConfirm('Hapus galeri ini?', { title: 'Hapus Galeri', confirmButtonText: 'Hapus', icon: 'warning' });
    if (!ok) return;
    router.delete(route('admin.galleries.destroy', id));
}
</script>

<template>
    <AdminLayout title="Galeri">
        <Head title="Admin Galeri" />

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <input v-model="q" type="search" placeholder="Cari galeri..." class="rounded-xl border-black/10 text-sm" />
            <Link :href="route('admin.galleries.create')" class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white">
                + Galeri Baru
            </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Galeri</th>
                        <th class="px-4 py-3">Foto</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="gallery in galleries.data" :key="gallery.id" class="border-t border-black/5">
                        <td class="px-4 py-3">
                            <div class="flex gap-3">
                                <div class="h-14 w-20 overflow-hidden rounded-lg bg-mist">
                                    <img v-if="gallery.cover_image_url" :src="gallery.cover_image_url" :alt="gallery.title" class="h-full w-full object-cover" />
                                </div>
                                <p class="font-semibold">{{ gallery.title }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-neutral-500">{{ gallery.images_count || 0 }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase" :class="gallery.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                {{ gallery.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <Link :href="route('admin.galleries.edit', gallery.id)" class="mr-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand">Edit</Link>
                            <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600" @click="destroy(gallery.id)">Hapus</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <PaginationBar :links="galleries.links" :from="galleries.from" :to="galleries.to" :total="galleries.total" />
        </div>
    </AdminLayout>
</template>
