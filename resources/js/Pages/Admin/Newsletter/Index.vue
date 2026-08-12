<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';

const props = defineProps({
    subscribers: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '', status: '' }) },
    stats: { type: Object, default: () => ({ total: 0, active: 0 }) },
});

const q = ref(props.filters.q || '');
const status = ref(props.filters.status || '');

watch([q, status], () => {
    router.get(route('admin.newsletter.index'), {
        q: q.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
});

async function destroy(id) {
    const ok = await swalConfirm('Hapus subscriber ini?', { title: 'Hapus', confirmButtonText: 'Hapus', icon: 'warning' });
    if (!ok) return;
    router.delete(route('admin.newsletter.destroy', id));
}

function toggle(id) {
    router.patch(route('admin.newsletter.toggle', id), {}, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout title="Newsletter">
        <Head title="Admin Newsletter" />

        <div class="mb-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">Total</p>
                <p class="mt-1 font-display text-3xl tracking-[-0.04em]">{{ stats.total }}</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">Aktif</p>
                <p class="mt-1 font-display text-3xl tracking-[-0.04em] text-emerald-600">{{ stats.active }}</p>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <input v-model="q" type="search" placeholder="Cari email/nama..." class="rounded-xl border-black/10 text-sm" />
            <select v-model="status" class="rounded-xl border-black/10 text-sm">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Subscribe</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in subscribers.data" :key="item.id" class="border-t border-black/5">
                        <td class="px-4 py-3 font-semibold">{{ item.email }}</td>
                        <td class="px-4 py-3 text-neutral-600">{{ item.name || '—' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase"
                                :class="item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                            >
                                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-neutral-500">{{ item.subscribed_at || '—' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" class="mr-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand" @click="toggle(item.id)">
                                {{ item.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                            <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600" @click="destroy(item.id)">
                                Hapus
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!subscribers.data?.length">
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-neutral-500">Belum ada subscriber.</td>
                    </tr>
                </tbody>
            </table>
            <PaginationBar :links="subscribers.links" :from="subscribers.from" :to="subscribers.to" :total="subscribers.total" />
        </div>
    </AdminLayout>
</template>
