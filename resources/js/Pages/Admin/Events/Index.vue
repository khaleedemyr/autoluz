<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';

const props = defineProps({
    events: { type: Object, required: true },
    filters: {
        type: Object,
        default: () => ({ q: '', status: '', per_page: 15 }),
    },
});

const q = ref(props.filters.q || '');
const status = ref(props.filters.status || '');
const perPage = ref(Number(props.filters.per_page || 15));

watch([q, status, perPage], () => {
    router.get(
        route('admin.events.index'),
        {
            q: q.value || undefined,
            status: status.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, replace: true },
    );
});

function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

async function destroy(id) {
    const ok = await swalConfirm('Hapus event ini?', {
        title: 'Hapus Event',
        confirmButtonText: 'Hapus',
        icon: 'warning',
    });
    if (!ok) return;
    router.delete(route('admin.events.destroy', id));
}
</script>

<template>
    <AdminLayout title="Event">
        <Head title="Admin Event" />

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <input
                    v-model="q"
                    type="search"
                    placeholder="Cari judul/lokasi..."
                    class="rounded-xl border-black/10 text-sm"
                />
                <select v-model="status" class="rounded-xl border-black/10 text-sm">
                    <option value="">Semua status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
                <select v-model="perPage" class="rounded-xl border-black/10 text-sm">
                    <option :value="10">10 / halaman</option>
                    <option :value="15">15 / halaman</option>
                    <option :value="25">25 / halaman</option>
                </select>
            </div>
            <Link
                :href="route('admin.events.create')"
                class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white"
            >
                + Event Baru
            </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                        <tr>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Lokasi</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="event in events.data"
                            :key="event.id"
                            class="border-t border-black/5 align-top"
                        >
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded-lg bg-mist">
                                        <img
                                            v-if="event.cover_image_url"
                                            :src="event.cover_image_url"
                                            :alt="event.title"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div>
                                        <p class="font-semibold">{{ event.title }}</p>
                                        <p v-if="event.is_featured" class="mt-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-brand">
                                            Featured
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-neutral-600">{{ formatDate(event.starts_at) }}</td>
                            <td class="px-4 py-3 text-neutral-600">
                                {{ event.city || event.location || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase"
                                    :class="event.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ event.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <Link
                                    :href="route('admin.events.edit', event.id)"
                                    class="mr-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand"
                                >
                                    Edit
                                </Link>
                                <button
                                    type="button"
                                    class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600"
                                    @click="destroy(event.id)"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!events.data?.length">
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-neutral-500">
                                Belum ada event.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationBar
                :links="events.links"
                :from="events.from"
                :to="events.to"
                :total="events.total"
            />
        </div>
    </AdminLayout>
</template>
